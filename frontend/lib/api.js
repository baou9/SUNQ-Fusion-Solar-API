export const apiFetch = (path, options) => {
  const base = process.env.NEXT_PUBLIC_API_BASE || '';
  return fetch(`${base}${path}`, options);
};

export const fetcher = (path, options) =>
  apiFetch(path, options).then(res => res.json());
