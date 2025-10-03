"use client";
import React from "react";
import { getAccessToken, refreshAccessToken, setAccessToken } from "@/lib/auth";

// Global session watcher: warns before expiry, tries proactive refresh,
// and surfaces a banner when refresh fails.
export default function SessionWatcher() {
  const [expAt, setExpAt] = React.useState<number | null>(null);
  const [warning, setWarning] = React.useState(false);
  const [expired, setExpired] = React.useState(false);

  React.useEffect(() => {
    function readExp() {
      const tok = getAccessToken();
      if (!tok) { setExpAt(null); return; }
      try {
        const [, payloadB64] = tok.split(".");
        const json = JSON.parse(atob(payloadB64.replace(/-/g, "+").replace(/_/g, "/")));
        const expSec = Number(json?.exp || 0);
        if (expSec > 0) setExpAt(expSec * 1000); else setExpAt(null);
      } catch { setExpAt(null); }
    }
    readExp();
    const onStorage = () => readExp();
    const onUnauthorized = () => setExpired(true);
    window.addEventListener('storage', onStorage);
    window.addEventListener('auth:unauthorized', onUnauthorized as any);
    return () => {
      window.removeEventListener('storage', onStorage);
      window.removeEventListener('auth:unauthorized', onUnauthorized as any);
    };
  }, []);

  React.useEffect(() => {
    if (!expAt) return;
    const warnAt = expAt - 120_000; // 2m before expiry
    const autoAt = expAt - 60_000;  // 1m before expiry
    const now = Date.now();

    let warnTimer: any; let autoTimer: any; let expiryTimer: any;
    if (warnAt > now) warnTimer = setTimeout(() => setWarning(true), warnAt - now);
    else setWarning(true);
    if (autoAt > now) autoTimer = setTimeout(async () => {
      try {
        const newTok = await refreshAccessToken();
        setAccessToken(newTok || null);
        setWarning(false);
      } catch {
        setExpired(true);
      }
    }, Math.max(0, autoAt - now));
    // hard expiry guard
    expiryTimer = setTimeout(() => setExpired(true), Math.max(0, expAt - now + 5000));
    return () => { clearTimeout(warnTimer); clearTimeout(autoTimer); clearTimeout(expiryTimer); };
  }, [expAt]);

  if (!warning && !expired) return null;
  return (
    <div className={`px-4 py-2 text-sm ${expired ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-800'}`}>
      <div className="max-w-4xl mx-auto flex items-center gap-3">
        <span className="font-medium">{expired ? 'Session expired' : 'Session expiring soon'}</span>
        <span className="hidden sm:inline">{expired ? 'Please log in again to continue.' : 'We will refresh automatically. If this fails, you will be asked to log in again.'}</span>
        <div className="ml-auto flex items-center gap-2">
          {!expired ? (
            <button
              className="underline"
              onClick={async () => {
                try {
                  const t = await refreshAccessToken();
                  setAccessToken(t || null);
                  setWarning(false);
                } catch { setExpired(true); }
              }}
            >Refresh now</button>
          ) : (
            <a className="underline" href="/auth/login">Login</a>
          )}
        </div>
      </div>
    </div>
  );
}
