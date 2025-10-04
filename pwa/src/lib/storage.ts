export function tokenSuffix() {
  if (typeof window === 'undefined') return 'anon';
  try {
    const tok = window.sessionStorage.getItem('accessToken') || '';
    return tok ? tok.slice(-8) : 'anon';
  } catch { return 'anon'; }
}

export function scopedKey(base: string) {
  return `${base}:${tokenSuffix()}`;
}

export function setScopedItem(base: string, value: any) {
  if (typeof window === 'undefined') return;
  try { window.sessionStorage.setItem(scopedKey(base), JSON.stringify(value)); } catch {}
}

export function getScopedItem<T = any>(base: string): T | null {
  if (typeof window === 'undefined') return null;
  try {
    const raw = window.sessionStorage.getItem(scopedKey(base));
    return raw ? JSON.parse(raw) as T : null;
  } catch { return null; }
}

export function removeScopedItem(base: string) {
  if (typeof window === 'undefined') return;
  try { window.sessionStorage.removeItem(scopedKey(base)); } catch {}
}
