"use client";
import React from "react";
import { getAccessToken, setAccessToken, refreshAccessToken } from "@/lib/auth";

export default function RequireAuth({ children }: { children: React.ReactNode }) {
  const [ready, setReady] = React.useState(false);

  React.useEffect(() => {
    let cancelled = false;
    async function ensureAuth() {
      // If we already have a token, allow render
      if (getAccessToken()) {
        if (!cancelled) setReady(true);
        return;
      }
      // If there is no refresh cookie at all, redirect immediately
      const hasRefreshCookie = typeof document !== 'undefined' && document.cookie.split('; ').some(c => c.startsWith('refresh_token='));
      if (!hasRefreshCookie) {
        if (typeof window !== 'undefined') {
          const next = encodeURIComponent(window.location.pathname + window.location.search);
          window.location.href = `/auth/login?next=${next}`;
        }
        return;
      }
      // Try a silent refresh using HttpOnly cookie
      const refreshWithTimeout = Promise.race([
        refreshAccessToken(),
        new Promise<null>((resolve) => setTimeout(() => resolve(null), 3000)),
      ]);
      const newToken = await refreshWithTimeout;
      if (newToken) {
        setAccessToken(newToken);
        if (!cancelled) setReady(true);
      } else {
        // Redirect to login
        if (typeof window !== 'undefined') {
          const next = encodeURIComponent(window.location.pathname + window.location.search);
          window.location.href = `/auth/login?next=${next}`;
        }
      }
    }
    ensureAuth();
    return () => { cancelled = true; };
  }, []);

  if (!ready) return <div className="p-4 text-sm text-gray-600">Loading…</div>;
  return <>{children}</>;
}
