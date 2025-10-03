export type RecentRecipient = { bankCode: string; accountNumber: string; accountName?: string; bankName?: string };

const KEY = 'texa:recentRecipients:v1';

export function loadRecents(): RecentRecipient[] {
  if (typeof window === 'undefined') return [];
  try {
    const raw = localStorage.getItem(KEY);
    const list = raw ? JSON.parse(raw) as RecentRecipient[] : [];
    return Array.isArray(list) ? list : [];
  } catch { return []; }
}

export function addRecent(r: RecentRecipient) {
  if (typeof window === 'undefined') return;
  try {
    const list = loadRecents();
    const map = new Map<string, RecentRecipient>();
    const key = (x: RecentRecipient) => `${x.bankCode}:${x.accountNumber}`;
    map.set(key(r), r);
    for (const it of list) map.set(key(it), it);
    const merged = Array.from(map.values()).slice(0, 15);
    localStorage.setItem(KEY, JSON.stringify(merged));
  } catch {}
}
