import useSWR from 'swr';

const API_BASE = process.env.NEXT_PUBLIC_API_BASE || '/api';
const fetcher = url => fetch(url).then(r => r.json());

export default function Home() {
  const { data, error } = useSWR(`${API_BASE}/stations`, fetcher, { refreshInterval: 60000 });

  if (error) return <div>Error loading stations.</div>;
  if (!data) return <div>Loading...</div>;
  const list = data.data?.list || [];
  if (list.length === 0) return <div>No stations.</div>;

  return (
    <main>
      <h1>Stations</h1>
      <ul>
        {list.map(s => (
          <li key={s.stationCode}>
            <a href={`/stations/${s.stationCode}`}>{s.stationName}</a>
            {` – ${s.capacity || 0}kW – ${s.city || 'N/A'}`}
          </li>
        ))}
      </ul>
    </main>
  );
}
