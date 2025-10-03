import http from './api';

type Method = 'POST' | 'PUT' | 'DELETE';

export type QueuedRequest = {
  id: string;
  method: Method;
  url: string;
  data?: any;
  headers?: Record<string, any>;
  enqueuedAt: number;
  attempts: number;
};

const KEY = 'offline:queue';

function load(): QueuedRequest[] {
  try {
    const raw = localStorage.getItem(KEY);
    if (!raw) return [];
    return JSON.parse(raw) as QueuedRequest[];
  } catch {
    return [];
  }
}

function save(list: QueuedRequest[]) {
  try { localStorage.setItem(KEY, JSON.stringify(list)); } catch {}
}

export function enqueue(req: Omit<QueuedRequest, 'id' | 'enqueuedAt' | 'attempts'> & { id?: string }) {
  const list = load();
  const item: QueuedRequest = {
    id: req.id || (typeof crypto !== 'undefined' && 'randomUUID' in crypto ? crypto.randomUUID() : Math.random().toString(36).slice(2)),
    method: req.method,
    url: req.url,
    data: req.data,
    headers: req.headers,
    enqueuedAt: Date.now(),
    attempts: 0,
  };
  list.push(item);
  save(list);
  return item.id;
}

export async function flushOnce(): Promise<{ ok: number; left: number }> {
  if (typeof window === 'undefined') return { ok: 0, left: 0 };
  const list = load();
  if (list.length === 0) return { ok: 0, left: 0 };
  let ok = 0;
  const remain: QueuedRequest[] = [];
  for (const it of list) {
    try {
      const cfg: any = { headers: it.headers };
      if (it.method === 'POST') await http.post(it.url, it.data, cfg);
      else if (it.method === 'PUT') await http.put(it.url, it.data, cfg);
      else if (it.method === 'DELETE') await http.delete(it.url, { ...cfg, data: it.data });
      ok++;
    } catch (e: any) {
      const status = e?.response?.status;
      const temporarily = !status || (status >= 500) || status === 429;
      if (temporarily && it.attempts < 5) {
        remain.push({ ...it, attempts: it.attempts + 1 });
      }
    }
  }
  save(remain);
  return { ok, left: remain.length };
}

export function hasQueue() {
  return load().length > 0;
}
