export type RecentRecipient = { bankCode: string; accountNumber: string; accountName?: string; bankName?: string };

function scopedKey() {
  if (typeof window === 'undefined') return 'texa:recentRecipients:v1:anon';
  try {
    const tok = window.sessionStorage.getItem('accessToken') || '';
    const suffix = tok ? tok.slice(-8) : 'anon';
    return `texa:recentRecipients:v1:${suffix}`;
  } catch { return 'texa:recentRecipients:v1:anon'; }
}

export function loadRecents(): RecentRecipient[] {
  if (typeof window === 'undefined') return [];
  try {
    const raw = localStorage.getItem(scopedKey());
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
    localStorage.setItem(scopedKey(), JSON.stringify(merged));
  } catch {}
}

export function clearRecentsForCurrentUser() {
  if (typeof window === 'undefined') return;
  try { localStorage.removeItem(scopedKey()); } catch {}
}
