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
  category?: string;
  nextAttemptAt?: number; // epoch ms when we should next try
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

function categorize(url: string): string {
  try {
    if (url.includes('/transfers')) return 'transfers';
    if (url.includes('/profile')) return 'profile';
    if (url.includes('/notifications')) return 'notifications';
    return 'general';
  } catch {
    return 'general';
  }
}

function backoffDelay(attempts: number): number {
  // exponential backoff with jitter: base 2s, cap 60s
  const base = 2000;
  const cap = 60000;
  const exp = Math.min(cap, base * Math.pow(2, Math.max(0, attempts)));
  const jitter = Math.floor(Math.random() * 500);
  return exp + jitter;
}

export function enqueue(req: Omit<QueuedRequest, 'id' | 'enqueuedAt' | 'attempts' | 'nextAttemptAt' | 'category'> & { id?: string }) {
  const list = load();
  const item: QueuedRequest = {
    id: req.id || (typeof crypto !== 'undefined' && 'randomUUID' in crypto ? crypto.randomUUID() : Math.random().toString(36).slice(2)),
    method: req.method,
    url: req.url,
    data: req.data,
    headers: req.headers,
    enqueuedAt: Date.now(),
    attempts: 0,
    category: categorize(req.url),
    nextAttemptAt: Date.now(),
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
  const now = Date.now();

  // Process in category order to avoid starving important queues
  const order = ['transfers', 'profile', 'notifications', 'general'];
  const sorted = [...list].sort((a, b) => order.indexOf(a.category || 'general') - order.indexOf(b.category || 'general'));

  for (const it of sorted) {
    if (it.nextAttemptAt && it.nextAttemptAt > now) {
      // not yet due
      remain.push(it);
      continue;
    }
    try {
      const cfg: any = { headers: it.headers };
      if (it.method === 'POST') await http.post(it.url, it.data, cfg);
      else if (it.method === 'PUT') await http.put(it.url, it.data, cfg);
      else if (it.method === 'DELETE') await http.delete(it.url, { ...cfg, data: it.data });
      ok++;
    } catch (e: any) {
      const status = e?.response?.status;
      const temporarily = !status || (status >= 500) || status === 429;
      if (temporarily && it.attempts < 7) {
        const next = { ...it, attempts: it.attempts + 1 } as QueuedRequest;
        next.nextAttemptAt = now + backoffDelay(next.attempts);
        remain.push(next);
      }
    }
  }
  save(remain);
  return { ok, left: remain.length };
}

export function hasQueue() {
  return load().length > 0;
}
