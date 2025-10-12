"use client";
import React from "react";
import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import http from "@/lib/api";

export default function ProtectedSuccessPage() {
  return (
    <React.Suspense fallback={<div className="min-h-dvh grid place-items-center p-6 text-sm text-gray-600">Loading…</div>}>
      <ProtectedSuccessInner />
    </React.Suspense>
  );
}

function ProtectedSuccessInner() {
  const { ref } = useParams<{ ref: string }>();
  const router = useRouter();
  const [link, setLink] = React.useState<string>("");
  const [copyMsg, setCopyMsg] = React.useState<string>("");
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  React.useEffect(() => {
    let cancelled = false;
    (async () => {
      setLoading(true); setError(null);
      try {
        const res = await http.get(`/api/mobile/protected/${ref}`);
        const d = res.data || {};
        const l = d?.share?.requestReleaseLink || "";
        if (!cancelled) setLink(l);
      } catch (e: any) {
        if (!cancelled) setError(e?.response?.data?.message || e?.message || "Failed to load");
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => { cancelled = true; };
  }, [ref]);

  async function copyLink() {
    if (!link) return;
    try { await navigator.clipboard.writeText(link); setCopyMsg("Link copied"); setTimeout(()=>setCopyMsg(""), 2000); }
    catch { setCopyMsg("Copy failed"); setTimeout(()=>setCopyMsg(""), 2000); }
  }

  return (
    <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <button onClick={() => router.back()} aria-label="Back" className="text-xl">‹</button>
          <h1 className="text-lg font-semibold">Funds Locked</h1>
        </div>
        <Link href={`/protected/${ref}`} className="text-sm underline">View details</Link>
      </div>

      {loading && <div className="text-sm text-gray-600">Loading…</div>}
      {error && <div className="text-sm text-red-600">{error}</div>}

      {!loading && !error && (
        <section className="border rounded p-4 space-y-3">
          <div className="text-sm text-gray-700">Your funds are held in escrow. Share the link with the receiver to request release.</div>
          <div className="flex items-center gap-2">
            <button onClick={copyLink} className="bg-black text-white rounded px-3 py-2">Copy link</button>
            {copyMsg && <span className="text-sm text-gray-700">{copyMsg}</span>}
          </div>
          {link ? (<p className="text-xs text-gray-500 break-all">{link}</p>) : (
            <p className="text-xs text-gray-500">Link not ready yet. Refresh or check details.</p>
          )}
          <div>
            <Link href={`/protected/${ref}`} className="inline-block bg-emerald-600 text-white rounded px-3 py-2">Approve now</Link>
          </div>
        </section>
      )}
    </div>
  );
}
