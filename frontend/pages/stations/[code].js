import { useRouter } from 'next/router';
import useSWR from 'swr';

const API_BASE = process.env.NEXT_PUBLIC_API_BASE || '/api';
const fetcher = url => fetch(url).then(r => r.json());

export default function StationDetail() {
  const router = useRouter();
  const { code } = router.query;
  const { data: overview, error: ovErr } = useSWR(
    () => code ? `${API_BASE}/stations/${code}/overview` : null,
    fetcher,
    { refreshInterval: 60000 }
  );
  const { data: devices, error: devErr } = useSWR(
    () => code ? `${API_BASE}/stations/${code}/devices` : null,
    fetcher,
    { refreshInterval: 60000 }
  );
  const { data: alarms, error: alarmErr } = useSWR(
    () => code ? `${API_BASE}/stations/${code}/alarms` : null,
    fetcher,
    { refreshInterval: 60000 }
  );
  if (ovErr || devErr || alarmErr) return <div>Error loading station.</div>;
  if (!overview || !devices || !alarms) return <div>Loading...</div>;

  return (
    <main>
      <h1>{overview.stationName || code}</h1>
      <section>
        <h2>KPIs</h2>
        <ul>
          <li>Current Power: {overview.currentPower}</li>
          <li>Today Energy: {overview.todayEnergy}</li>
          <li>Total Energy: {overview.totalEnergy}</li>
          {overview.performanceRatio !== undefined && <li>PR: {overview.performanceRatio}</li>}
        </ul>
      </section>
      <section>
        <h2>Devices</h2>
        {devices.data?.list?.length ? (
          <table>
            <thead>
              <tr><th>Name</th><th>Type</th></tr>
            </thead>
            <tbody>
              {devices.data.list.map(d => (
                <tr key={d.id || d.deviceId}>
                  <td>{d.deviceName || d.name || d.id}</td>
                  <td>{d.devTypeName || d.type}</td>
                </tr>
              ))}
            </tbody>
          </table>
        ) : <div>No devices.</div>}
      </section>
      <section>
        <h2>Active Alarms</h2>
        {alarms.list?.length ? (
          <ul>
            {alarms.list.map(a => (
              <li key={a.code}>{a.message} ({a.severity})</li>
            ))}
          </ul>
        ) : <div>No active alarms.</div>}
      </section>
    </main>
  );
}
