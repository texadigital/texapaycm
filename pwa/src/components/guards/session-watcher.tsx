"use client";
import React from "react";
import { getAccessToken, refreshAccessToken, setAccessToken } from "@/lib/auth";

// Session watcher: keep session alive while user is active; on expiry, auto-redirect to login without UI prompts.
export default function SessionWatcher() {
  const [expAt, setExpAt] = React.useState<number | null>(null);
  const lastActive = React.useRef<number>(Date.now());
  // 20 minutes inactivity window (can be overridden via NEXT_PUBLIC_INACTIVITY_MS)
  const INACTIVITY_MS = (typeof process !== 'undefined' && (process as any).env?.NEXT_PUBLIC_INACTIVITY_MS)
    ? Number((process as any).env.NEXT_PUBLIC_INACTIVITY_MS)
    : 20 * 60 * 1000;

  // Track activity
  React.useEffect(() => {
    const bump = () => { lastActive.current = Date.now(); };
    ['click','keydown','mousemove','touchstart','scroll','visibilitychange'].forEach((evt) => window.addEventListener(evt, bump, { passive: true } as any));
    return () => {
      ['click','keydown','mousemove','touchstart','scroll','visibilitychange'].forEach((evt) => window.removeEventListener(evt, bump));
    };
  }, []);

  // Read token exp whenever it changes
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
    const onUnauthorized = () => redirectToLogin();
    window.addEventListener('storage', onStorage);
    window.addEventListener('auth:unauthorized', onUnauthorized as any);
    return () => {
      window.removeEventListener('storage', onStorage);
      window.removeEventListener('auth:unauthorized', onUnauthorized as any);
    };
  }, []);

  // Timers for proactive refresh and expiry handling
  React.useEffect(() => {
    if (!expAt) return;
    const now = Date.now();
    const warnAt = expAt - 120_000; // 2m before expiry
    const autoAt = expAt - 60_000;  // 1m before expiry

    let autoTimer: any; let expiryTimer: any; let activityCheck: any;

    // Proactive refresh if recently active
    const scheduleAuto = () => {
      const delay = Math.max(0, autoAt - Date.now());
      autoTimer = setTimeout(async () => {
        // Only refresh if user was active in the inactivity window
        if (Date.now() - lastActive.current <= INACTIVITY_MS) {
          try {
            const newTok = await refreshAccessToken();
            setAccessToken(newTok || null);
          } catch {
            redirectToLogin();
          }
        }
      }, delay);
    };

    // Hard expiry -> redirect
    const scheduleExpiry = () => {
      const delay = Math.max(0, expAt - Date.now() + 1000);
      expiryTimer = setTimeout(() => redirectToLogin(), delay);
    };

    // Periodically check activity and reschedule auto-refresh if needed
    scheduleAuto();
    scheduleExpiry();
    activityCheck = setInterval(() => {
      // If user becomes active again before autoAt, ensure auto refresh is still scheduled
      if (Date.now() < autoAt && Date.now() - lastActive.current <= INACTIVITY_MS) {
        // no-op; timer already set
      }
    }, 15000);

    return () => { clearTimeout(autoTimer); clearTimeout(expiryTimer); clearInterval(activityCheck); };
  }, [expAt]);

  function redirectToLogin() {
    try {
      setAccessToken(null);
    } catch {}
    const next = encodeURIComponent(window.location.pathname + window.location.search);
    window.location.replace(`/auth/login?next=${next}`);
  }

  return null;
}
