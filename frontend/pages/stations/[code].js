import { useRouter } from 'next/router';
import useSWR from 'swr';

const fetcher = url => fetch(url).then(r => r.json());

export default function StationDetail() {
  const router = useRouter();
  const { code } = router.query;
  const { data, error } = useSWR(() => code ? `/api/stations/${code}/overview` : null, fetcher, { refreshInterval: 60000 });

  if (error) return <div>Error loading station.</div>;
  if (!data) return <div>Loading...</div>;

  return (
    <main>
      <h1>{data.stationName || code}</h1>
      <pre>{JSON.stringify(data, null, 2)}</pre>
    </main>
  );
}
