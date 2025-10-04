"use client";
import React from "react";
import http from "@/lib/api";
import { loadBankDirectory } from "@/lib/banks";

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
  const [qDebounced, setQDebounced] = React.useState("");
  const [banks, setBanks] = React.useState<Bank[]>([]);
  const [favorites, setFavorites] = React.useState<Bank[]>([]);
  const [loading, setLoading] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const scrollRef = React.useRef<HTMLDivElement | null>(null);
  const savedScroll = React.useRef<number>(0);

  // Debounce search term 250ms
  React.useEffect(() => {
    const id = setTimeout(() => setQDebounced(q), 250);
    return () => clearTimeout(id);
  }, [q]);

  React.useEffect(() => {
    if (!open) return;
    let cancelled = false;
    async function load() {
      try {
        setError(null);
        setLoading(true);
        // Prefetch bank directory cache for client map
        loadBankDirectory().catch(() => undefined);
        // Fetch list first (critical)
        let ok = false;
        const listRes = await http.get("/api/mobile/banks").catch(() => null);
        if (!cancelled && listRes?.data?.banks) {
          setBanks((listRes.data.banks as Bank[]) || []);
          ok = true;
        }
        // Fallback: SafeHaven banks
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
        // Fetch favorites (frequently used)
        http.get("/api/mobile/banks/favorites").then((favRes) => {
          if (!cancelled) setFavorites((favRes.data?.banks as Bank[]) || []);
        }).catch(() => undefined);
      } catch (e: any) {
        if (!cancelled) setError(e?.response?.data?.message || e.message);
      } finally {
        if (!cancelled) setLoading(false);
      }
    }
    load();
    return () => { cancelled = true; };
  }, [open]);

  const filtered = React.useMemo(() => {
    const term = qDebounced.trim().toLowerCase();
    if (!term) return banks;
    return banks.filter((b) =>
      [b.name, ...(b.aliases || [])]
        .filter(Boolean)
        .some((s) => String(s).toLowerCase().includes(term))
    );
  }, [banks, qDebounced]);

  const grouped = React.useMemo(() => {
    const map = new Map<string, Bank[]>();
    for (const b of filtered) {
      const key = (b.name?.[0] || '#').toUpperCase();
      if (!map.has(key)) map.set(key, []);
      map.get(key)!.push(b);
    }
    const keys = Array.from(map.keys()).sort();
    return keys.map((k) => ({ key: k, items: map.get(k)! }));
  }, [filtered]);

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50" onClick={() => { savedScroll.current = scrollRef.current?.scrollTop || 0; onClose(); }}>
      <div className="absolute inset-0 bg-black/30" />
      <div className="absolute inset-x-0 bottom-0 bg-white rounded-t-2xl shadow-2xl p-4 max-h-[85vh]" onClick={(e) => e.stopPropagation()}>
        <div className="flex items-center justify-between mb-3">
          <button aria-label="Close" onClick={() => { savedScroll.current = scrollRef.current?.scrollTop || 0; onClose(); }}>✕</button>
          <h3 className="text-base font-semibold">Select Bank</h3>
          <div className="w-6" />
        </div>
        <div className="mb-3">
          <input
            className="w-full border rounded-full px-4 py-2 text-sm"
            placeholder="Search Bank Name"
            value={q}
            onChange={(e) => setQ(e.target.value)}
          />
        </div>
        {favorites.length > 0 && (
          <div className="mb-2">
            <div className="text-xs text-gray-600 mb-2">Frequently Used Banks</div>
            <div className="grid grid-cols-3 sm:grid-cols-4 gap-2">
              {favorites.slice(0,8).map((b) => (
                <button key={`fav-${b.bankCode}`} className="border rounded-xl p-3 flex flex-col items-center gap-2 hover:bg-gray-50"
                  onClick={() => { onSelect(b); onClose(); }}>
                  <div className="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center text-xs">
                    {/* logo slot could be improved by directory hints */}
                    {b.name?.[0]?.toUpperCase()}
                  </div>
                  <div className="text-xs text-center line-clamp-2">{b.name}</div>
                </button>
              ))}
            </div>
          </div>
        )}
        <div ref={scrollRef} className="overflow-auto max-h-[55vh] border rounded">
          {loading ? (
            <div className="p-3 text-sm text-gray-600">Loading…</div>
          ) : error ? (
            <div className="p-3 text-sm text-red-600">{error}</div>
          ) : (
            <div>
              {grouped.map((g) => (
                <div key={g.key}>
                  <div className="px-3 py-1 text-xs text-gray-500 bg-gray-50 sticky top-0">{g.key}</div>
                  {g.items.map((b) => (
                    <button key={b.bankCode} className="w-full flex items-center justify-between px-3 py-3 hover:bg-gray-50"
                      onClick={() => { onSelect(b); onClose(); }}>
                      <div className="flex items-center gap-3">
                        <div className="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center text-sm">
                          {b.name?.[0]?.toUpperCase()}
                        </div>
                        <div className="text-sm">{b.name}</div>
                      </div>
                      <div className="text-gray-400">›</div>
                    </button>
                  ))}
                </div>
              ))}
              {grouped.length === 0 && (
                <div className="p-3 text-sm text-gray-600">No banks found.</div>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
