"use client";
import React from "react";
import http from "@/lib/api";

export type Bank = { bankCode: string; name: string; aliases?: string[] };

export default function BankPicker({
  open,
  onClose,
  onSelect,
}: {
  open: boolean;
  onClose: () => void;
  onSelect: (bank: Bank) => void;
}) {
  const [q, setQ] = React.useState("");
  const [banks, setBanks] = React.useState<Bank[]>([]);
  const [favorites, setFavorites] = React.useState<Bank[]>([]);
  const [loading, setLoading] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);

  React.useEffect(() => {
    if (!open) return;
    let cancelled = false;
    async function load() {
      try {
        setError(null);
        setLoading(true);
        // Fetch list first (critical)
        let ok = false;
        const listRes = await http.get("/api/mobile/banks").catch(() => null);
        if (!cancelled && listRes?.data?.banks) {
          setBanks((listRes.data.banks as Bank[]) || []);
          ok = true;
        }
        // Fallback: hit SafeHaven raw banks if primary fails due to session middleware, then map to Bank shape
        if (!ok) {
          const raw = await http.get('/api/mobile/health/safehaven/banks').catch(() => null);
          if (!cancelled && raw?.data) {
            const items: any[] = Array.isArray(raw.data) ? raw.data : (raw.data?.banks || []);
            const mapped: Bank[] = items.map((it: any) => ({
              bankCode: String(it.bankCode || it.code || it.bank_code || it.id || ''),
              name: String(it.name || it.bankName || it.label || it.title || ''),
              aliases: it.aliases || [],
            })).filter(b => b.bankCode && b.name);
            if (mapped.length > 0) {
              setBanks(mapped);
              ok = true;
            }
          }
        }
        if (!ok && !cancelled) {
          setError('Unable to load banks. Please try again.');
        }
        // Fetch favorites (best-effort; may require session). Ignore errors.
        http.get("/api/mobile/banks/favorites").then((favRes) => {
          if (!cancelled) setFavorites((favRes.data?.banks as Bank[]) || []);
        }).catch(() => undefined);
      } catch (e: any) {
        // Shouldn't get here often; already handled per-call
        if (!cancelled) setError(e?.response?.data?.message || e.message);
      } finally {
        if (!cancelled) setLoading(false);
      }
    }
    load();
    return () => {
      cancelled = true;
    };
  }, [open]);

  const filtered = React.useMemo(() => {
    const term = q.trim().toLowerCase();
    if (!term) return banks;
    return banks.filter((b) =>
      [b.name, b.bankCode, ...(b.aliases || [])]
        .filter(Boolean)
        .some((s) => String(s).toLowerCase().includes(term))
    );
  }, [banks, q]);

  if (!open) return null;

  return (
    <div className="fixed inset-0 bg-black/30 z-50 flex items-center justify-center" onClick={onClose}>
      <div className="bg-white w-full max-w-lg rounded shadow p-4" onClick={(e) => e.stopPropagation()}>
        <div className="flex items-center justify-between mb-3">
          <h3 className="text-lg font-semibold">Select bank</h3>
          <button className="text-sm underline" onClick={onClose}>Close</button>
        </div>
        <input
          className="w-full border rounded px-3 py-2 mb-3"
          placeholder="Search by name or code"
          value={q}
          onChange={(e) => setQ(e.target.value)}
        />
        {error ? (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2 mb-3">{error}</div>
        ) : null}
        {loading ? (
          <div className="text-sm text-gray-600">Loading…</div>
        ) : (
          <div className="max-h-80 overflow-auto divide-y border rounded">
            {favorites.length > 0 && (
              <div className="p-2 bg-gray-50 text-xs text-gray-600">Favorites</div>
            )}
            {favorites.map((b) => (
              <button key={`fav-${b.bankCode}`} className="w-full text-left p-3 hover:bg-gray-50 flex items-center justify-between"
                onClick={() => { onSelect(b); onClose(); }}>
                <span>{b.name}</span>
                <span className="text-xs text-gray-500">{b.bankCode}</span>
              </button>
            ))}
            {filtered.length > 0 && (
              <div className="p-2 bg-gray-50 text-xs text-gray-600">All banks</div>
            )}
            {filtered.map((b) => (
              <button key={b.bankCode} className="w-full text-left p-3 hover:bg-gray-50 flex items-center justify-between"
                onClick={() => { onSelect(b); onClose(); }}>
                <span>{b.name}</span>
                <span className="text-xs text-gray-500">{b.bankCode}</span>
              </button>
            ))}
            {favorites.length === 0 && filtered.length === 0 && (
              <div className="p-3 text-sm text-gray-600">No banks found.</div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
