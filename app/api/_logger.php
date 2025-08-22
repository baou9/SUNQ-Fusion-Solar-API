<?php
declare(strict_types=1);

use Psr\Log\AbstractLogger;

class JsonLogger extends AbstractLogger
{
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = []): void
    {
        $record = array_merge([
            'level' => $level,
            'message' => $message,
            'ts' => date('c'),
        ], $context);
        error_log(json_encode($record));
    }
}
