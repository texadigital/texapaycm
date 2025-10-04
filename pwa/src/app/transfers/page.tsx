"use client";
import React from "react";
import { useQuery } from "@tanstack/react-query";
import RequireAuth from "@/components/guards/require-auth";
import Link from "next/link";
import http from "@/lib/api";
import PageHeader from "@/components/ui/page-header";
import { CardSkeleton } from "@/components/ui/skeleton";

type FeedItem = {
  id: string;
  transferId: number;
  kind: "transfer" | "fee" | "interest";
  direction: "in" | "out";
  label: string; // backend provided, do not hardcode
  at: string; // ISO
  status: string;
  statusLabel: string; // e.g., Successful, Pending, Failed
  currency: string; // e.g., NGN, XAF
  amountMinor: number; // signed is implied by sign
  sign: -1 | 1;
  meta?: Record<string, any>;
};
type FeedMonth = {
  key: string; // YYYY-MM
  label: string; // e.g., Sep 2025
  items: FeedItem[];
  totals: { inMinor: number; outMinor: number; currency: string };
};
type FeedRes = { months: FeedMonth[]; meta?: { page?: number; perPage?: number; lastPage?: number } } & Record<string, any>;

export default function TransfersListPage() {
  const [page, setPage] = React.useState(1);
  const perPage = 20;

  const q = useQuery<FeedRes>({
    queryKey: ["transactions-feed", page],
    queryFn: async () => {
      const res = await http.get("/api/mobile/transactions/feed", { params: { page, perPage } });
      return res.data as any;
    },
    staleTime: 30_000,
    gcTime: 5 * 60_000,
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
  });

  const months: FeedMonth[] = Array.isArray((q.data as any)?.months) ? ((q.data as any).months as FeedMonth[]) : [];
  const meta: { lastPage?: number } = (q.data?.meta as any) || {};

  const formatMoney = (minor: number, currency: string) => {
    // NGN minor to major; XAF is not minor in backend fees mapping (we sent minor = xaf*100)
    const divisors: Record<string, number> = { NGN: 100, XAF: 100 };
    const divisor = divisors[currency] ?? 100;
    const major = (minor / divisor);
    const symbol = currency === "NGN" ? "₦" : currency === "XAF" ? "XAF " : `${currency} `;
    return `${symbol}${major.toLocaleString(undefined, { maximumFractionDigits: 2 })}`;
  };

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-4">
        <PageHeader title="Transfers" />

        {q.isLoading ? (
          <div className="space-y-2">
            <CardSkeleton lines={3} />
            <CardSkeleton lines={3} />
          </div>
        ) : months.length === 0 ? (
          <div className="text-sm text-gray-600 border rounded p-3">No transfers yet.</div>
        ) : (
          <div className="space-y-4">
            {months.map((m) => (
              <div key={m.key} className="border rounded">
                <div className="flex items-center justify-between p-3">
                  <div className="font-semibold">{m.label}</div>
                  <div className="flex items-center gap-4 text-xs">
                    <div>In {formatMoney(m.totals.inMinor || 0, m.totals.currency)}</div>
                    <div>Out {formatMoney(m.totals.outMinor || 0, m.totals.currency)}</div>
                  </div>
                </div>
                <div className="divide-y">
                  {m.items.map((it) => {
                    const isOut = it.direction === "out" || it.sign === -1;
                    const color = isOut ? "text-red-600" : "text-green-600";
                    const arrow = isOut ? "↑" : "↓";
                    const when = it.at ? new Date(it.at).toLocaleString() : "";
                    const signedMinor = (it.sign === -1 ? -1 : 1) * it.amountMinor;
                    return (
                      <Link key={it.id} href={`/transfer/${it.transferId}/timeline`} className="flex items-center justify-between p-3 hover:bg-gray-50">
                        <div className="flex items-start gap-3">
                          <div className={`w-6 h-6 flex items-center justify-center rounded-full ${isOut ? "bg-red-50" : "bg-green-50"} ${color} text-sm`}>{arrow}</div>
                          <div className="text-sm">
                            <div className="font-medium line-clamp-1" title={it.label}>{it.label}</div>
                            <div className="text-xs text-gray-600" suppressHydrationWarning>{when}</div>
                          </div>
                        </div>
                        <div className="text-right">
                          <div className={`text-sm font-semibold ${color}`}>{(isOut ? "-" : "+")}{formatMoney(Math.abs(signedMinor), it.currency)}</div>
                          <div className={`inline-block text-xs px-2 py-0.5 rounded mt-1 ${it.statusLabel === "Successful" ? "bg-emerald-50 text-emerald-700" : it.statusLabel === "Pending" ? "bg-amber-50 text-amber-700" : "bg-rose-50 text-rose-700"}`}>{it.statusLabel}</div>
                        </div>
                      </Link>
                    );
                  })}
                </div>
              </div>
            ))}
          </div>
        )}

        <div className="flex items-center justify-between pt-2">
          <button className="border rounded px-3 py-1 disabled:opacity-50" disabled={page <= 1 || q.isFetching} onClick={() => setPage((p) => Math.max(1, p - 1))}>Prev</button>
          <div className="text-xs text-gray-600">Page {page}{meta?.lastPage ? ` / ${meta.lastPage}` : ""}</div>
          <button className="border rounded px-3 py-1 disabled:opacity-50" disabled={!!meta?.lastPage && page >= meta.lastPage || q.isFetching} onClick={() => setPage((p) => p + 1)}>Next</button>
        </div>
      </div>
    </RequireAuth>
  );
}
