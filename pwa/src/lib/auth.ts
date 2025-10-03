// Simple in-memory access token store with refresh support
let accessToken: string | null = null;

// Rehydrate from sessionStorage on first load (browser only)
if (typeof window !== 'undefined') {
  try {
    const saved = window.sessionStorage.getItem('accessToken');
    if (saved) accessToken = saved;
  } catch {}
}

export function getAccessToken(): string | null {
  return accessToken;
}

export function setAccessToken(token: string | null) {
  accessToken = token;
  try {
    if (typeof window !== 'undefined') {
      if (token) window.sessionStorage.setItem('accessToken', token);
      else window.sessionStorage.removeItem('accessToken');
    }
  } catch {}
}

export function clearAccessToken() {
  setAccessToken(null);
}

// Call server refresh endpoint which uses HttpOnly refresh cookie
export async function refreshAccessToken(): Promise<string | null> {
  try {
    const res = await fetch('/api/mobile/auth/refresh', {
      method: 'POST',
      credentials: 'include',
      headers: {
        Accept: 'application/json',
      },
    });
    if (!res.ok) return null;
    const data = await res.json();
    return (data && data.accessToken) || null;
  } catch {
    return null;
  }
}
