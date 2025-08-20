import useSWR from 'swr';

const fetcher = url => fetch(url).then(r => r.json());

export default function Home() {
  const { data, error } = useSWR('/api/stations', fetcher, { refreshInterval: 60000 });

  if (error) return <div>Error loading stations.</div>;
  if (!data) return <div>Loading...</div>;

  return (
    <main>
      <h1>Stations</h1>
      <ul>
        {data.data?.list?.map(s => (
          <li key={s.stationCode}>
            <a href={`/stations/${s.stationCode}`}>{s.stationName}</a>
          </li>
        ))}
      </ul>
    </main>
  );
}
