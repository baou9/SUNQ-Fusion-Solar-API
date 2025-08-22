<?php
declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

require_once __DIR__ . '/_util.php';

class FusionSolarException extends \RuntimeException {}

class FusionSolarClient
{
    private Client $http;
    private CookieJar $jar;
    private ?string $xsrf = null;
    private array $config;
    private LoggerInterface $logger;
    /** @var array<string, array{expires: float, data: array}> */
    private static array $cache = [];

    public function __construct(array $config, ?LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger ?? new NullLogger();
        $this->jar = new CookieJar();
        $stack = HandlerStack::create();
        $stack->push(Middleware::retry(function ($retries, $request, $response, $exception) {
            if ($retries >= 1) {
                return false;
            }
            if ($exception instanceof RequestException) {
                $code = $exception->getResponse() ? $exception->getResponse()->getStatusCode() : 0;
            } else {
                $code = $response ? $response->getStatusCode() : 0;
            }
            if ($code >= 500 && $code < 600) {
                usleep(random_int(100, 300) * 1000);
                return true;
            }
            return false;
        }));
        $this->http = new Client([
            'base_uri' => $config['FS_BASE'],
            'proxy' => $config['MA_PROXY'],
            'timeout' => 20,
            'connect_timeout' => 10,
            'allow_redirects' => false,
            'cookies' => $this->jar,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'handler' => $stack,
            'verify' => true,
        ]);
    }

    /** Login to FusionSolar and capture xsrf-token */
    public function login(): void
    {
        $payload = [
            'userName' => $this->config['FS_USER'],
            'systemCode' => $this->config['FS_CODE'],
        ];
        try {
            $res = $this->http->post('/thirdData/login', ['json' => $payload]);
            $this->xsrf = $this->extractXsrfToken($res, $this->jar);
        } catch (RequestException $e) {
            throw new FusionSolarException('login_failed', $e->getCode() ?: 500);
        }
    }

    /**
     * Generic POST request helper with auto login, retry and caching.
     * @param string $path
     * @param array $json
     * @return array
     * @throws FusionSolarException
     */
    public function request(string $path, array $json): array
    {
        $cacheKey = md5($path . '|' . md5(json_encode($json)));
        $now = microtime(true);
        if (isset(self::$cache[$cacheKey]) && self::$cache[$cacheKey]['expires'] > $now) {
            $this->logger->info('fs_request', [
                'requestId' => get_request_id(),
                'method' => 'POST',
                'url' => $path,
                'status' => 200,
                'latencyMs' => 0,
                'cacheHit' => true,
            ]);
            return self::$cache[$cacheKey]['data'];
        }

        if (!$this->xsrf) {
            $this->login();
        }

        $headers = $this->xsrf ? ['XSRF-TOKEN' => $this->xsrf] : [];
        $start = microtime(true);
        $relogin = false;
        try {
            $res = $this->http->post($path, [
                'headers' => $headers,
                'json' => $json,
            ]);
        } catch (RequestException $e) {
            $res = $e->getResponse();
            if ($res && $res->getStatusCode() === 401) {
                $relogin = true;
            } else {
                $this->logRequest($path, $start, $res ? $res->getStatusCode() : 0, false, ['error' => $e->getMessage()]);
                throw new FusionSolarException('upstream_error', $res ? $res->getStatusCode() : 502);
            }
        }

        if ($relogin) {
            $this->login();
            $headers = $this->xsrf ? ['XSRF-TOKEN' => $this->xsrf] : [];
            $start = microtime(true);
            try {
                $res = $this->http->post($path, [
                    'headers' => $headers,
                    'json' => $json,
                ]);
            } catch (RequestException $e) {
                $res = $e->getResponse();
                $this->logRequest($path, $start, $res ? $res->getStatusCode() : 0, false, ['error' => $e->getMessage()]);
                throw new FusionSolarException('upstream_error', $res ? $res->getStatusCode() : 502);
            }
        }

        $body = json_decode((string)$res->getBody(), true);
        $status = $res->getStatusCode();
        if ($status === 401 || (($body['failCode'] ?? 0) !== 0 && $body['failCode'] != '0')) {
            // one re-login attempt already performed above
            $this->logRequest($path, $start, $status, false, ['failCode' => $body['failCode'] ?? null]);
            throw new FusionSolarException('upstream_error', $status);
        }
        if ($status >= 400) {
            $this->logRequest($path, $start, $status, false);
            throw new FusionSolarException('upstream_error', $status);
        }
        self::$cache[$cacheKey] = [
            'expires' => $now + $this->config['CACHE_TTL_SECONDS'],
            'data' => $body,
        ];
        $this->logRequest($path, $start, $status, false, ['failCode' => $body['failCode'] ?? null]);
        return $body;
    }

    private function extractXsrfToken(ResponseInterface $res, CookieJarInterface $jar): ?string {
        $hdr = $res->getHeaderLine("xsrf-token");
        if ($hdr !== "") return $hdr;
        foreach ($jar->toArray() as $c) {
            if (strcasecmp($c["Name"] ?? $c["name"] ?? "", "XSRF-TOKEN") === 0) {
                return (string)($c["Value"] ?? $c["value"] ?? "");
            }
        }
        return null;
    }

    private function logRequest(string $path, float $start, int $status, bool $cacheHit, array $extra = []): void
    {
        $latency = (microtime(true) - $start) * 1000;
        $context = array_merge([
            'requestId' => get_request_id(),
            'method' => 'POST',
            'url' => $path,
            'status' => $status,
            'latencyMs' => (int)round($latency),
            'cacheHit' => $cacheHit,
        ], $extra);
        $this->logger->info('fs_request', $context);
    }

    // Convenience endpoint wrappers
    public function stations(int $page): array
    {
        return $this->request('/thirdData/stations', ['pageNo' => $page]);
    }

    public function getStationRealKpi(string $code): array
    {
        return $this->request('/thirdData/getStationRealKpi', ['stationCodes' => $code]);
    }

    public function getDevList(string $code): array
    {
        return $this->request('/thirdData/getDevList', ['stationCodes' => $code]);
    }

    public function getAlarmList(string $code, int $beginTime, int $endTime, ?string $levels = null): array
    {
        $payload = [
            'stationCodes' => $code,
            'beginTime' => $beginTime,
            'endTime' => $endTime,
            'language' => 'en_US',
        ];
        if ($levels) {
            $payload['levels'] = $levels;
        }
        return $this->request('/thirdData/getAlarmList', $payload);
    }

    public function getKpiStationDay(string $code, int $collectTime): array
    {
        return $this->request('/thirdData/getKpiStationDay', [
            'stationCodes' => $code,
            'collectTime' => $collectTime,
        ]);
    }
}
