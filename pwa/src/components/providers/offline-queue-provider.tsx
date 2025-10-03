"use client";
import React from "react";
import { flushOnce, hasQueue } from "@/lib/offline-queue";

export default function OfflineQueueProvider({ children }: { children: React.ReactNode }) {
  const [queued, setQueued] = React.useState<boolean>(false);
  const [busy, setBusy] = React.useState(false);

  async function flush() {
    try {
      setBusy(true);
      await flushOnce();
    } finally {
      setBusy(false);
      setQueued(hasQueue());
    }
  }

  React.useEffect(() => {
    // Initial state
    setQueued(hasQueue());

    const onOnline = () => flush();
    const onFocus = () => { if (hasQueue()) flush(); };
    const onVisible = () => { if (document.visibilityState === 'visible' && hasQueue()) flush(); };
    window.addEventListener('online', onOnline);
    window.addEventListener('focus', onFocus);
    document.addEventListener('visibilitychange', onVisible);
    const t = setInterval(() => { if (hasQueue() && navigator.onLine) flush(); }, 15000);
    return () => {
      window.removeEventListener('online', onOnline);
      window.removeEventListener('focus', onFocus);
      document.removeEventListener('visibilitychange', onVisible);
      clearInterval(t);
    };
  }, []);

  return (
    <>
      {queued && (
        <div className="fixed bottom-3 left-1/2 -translate-x-1/2 z-50 text-xs px-3 py-1.5 rounded bg-yellow-500 text-black shadow">
          {busy ? 'Syncing pending actions…' : 'Some actions are queued and will sync when online.'}
          {!busy && (
            <button className="ml-2 underline" onClick={flush}>Sync now</button>
          )}
        </div>
      )}
      {children}
    </>
  );
}
