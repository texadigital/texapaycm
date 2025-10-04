"use client";
import React from "react";
import { useQuery } from "@tanstack/react-query";
import Link from "next/link";
import http from "@/lib/api";
import { getAccessToken } from "@/lib/auth";
import RequireAuth from "@/components/guards/require-auth";
import { CardSkeleton } from "@/components/ui/skeleton";

type DashboardResponse = {
  kyc: { status: string; level: number };
  today: { count: number; totalXaf: number };
  month: { count: number; totalXaf: number };
  recentTransfers: Array<{
    id: number;
    status: string;
    amountXaf: number;
    createdAt: string | null;
  }>;
};

// Feed types (subset) to render Recent Transfers using /transactions/feed
type FeedItem = {
  id: string;
  transferId: number;
  kind: "transfer" | "fee" | "interest";
  direction: "in" | "out";
  label: string;
  at: string;
  status: string;
  statusLabel: string;
  currency: string;
  amountMinor: number;
  sign: -1 | 1;
};
type FeedMonth = { key: string; label: string; items: FeedItem[]; totals: { inMinor: number; outMinor: number; currency: string } };
type FeedRes = { months: FeedMonth[]; meta?: { page?: number; perPage?: number; lastPage?: number } };

export default function DashboardPage() {
  const [enabled, setEnabled] = React.useState<boolean>(() => !!getAccessToken());
  const [showAmount, setShowAmount] = React.useState<boolean>(() => {
    if (typeof window === 'undefined') return true;
    const v = window.localStorage.getItem('dash:showTotalSent');
    return v === null ? true : v === '1';
  });
  const [period, setPeriod] = React.useState<'all'|'month'|'week'>(() => {
    if (typeof window === 'undefined') return 'all';
    return (window.localStorage.getItem('dash:totalSentPeriod') as any) || 'all';
  });

  // Turn on query when a token becomes available
  React.useEffect(() => {
    const onToken = (e: any) => setEnabled(!!getAccessToken());
    if (typeof window !== 'undefined') {
      window.addEventListener('auth:token', onToken as any);
    }
    return () => {
      if (typeof window !== 'undefined') {
        window.removeEventListener('auth:token', onToken as any);
      }
    };
  }, []);

  const { data, isLoading, error, refetch, isFetching } = useQuery<DashboardResponse>({
    queryKey: ["dashboard"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/dashboard");
      return res.data;
    },
    enabled,
    // Keep previous data visible during background refetch to avoid full skeleton flash
    placeholderData: (prev) => prev as any,
    // Don't refetch on window focus/reconnect to reduce surprise refreshes over ngrok
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
    // Reasonable cache and retry behavior
    staleTime: 30_000,
    gcTime: 5 * 60_000,
    retry: 1,
  });

  // Recent feed for dashboard (page 1 only, small perPage)
  const feedQ = useQuery<FeedRes>({
    queryKey: ["dashboard-feed"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/transactions/feed", { params: { page: 1, perPage: 10 } });
      return res.data as FeedRes;
    },
    enabled,
    staleTime: 30_000,
    gcTime: 5 * 60_000,
    retry: 1,
  });

  const recentItems: FeedItem[] = React.useMemo(() => {
    const months = feedQ.data?.months ?? [];
    const all = months.flatMap((m) => m.items);
    // Prefer showing only transfer rows (exclude levy/interest) for dashboard recents
    return all.filter((it) => it.kind === "transfer").slice(0, 5);
  }, [feedQ.data]);

  const formatMoney = (minor: number, currency: string) => {
    const divisor = 100; // NGN and XAF provided in minor for feed
    const symbol = currency === "NGN" ? "₦" : currency === "XAF" ? "XAF " : `${currency} `;
    return `${symbol}${(minor / divisor).toLocaleString(undefined, { maximumFractionDigits: 2 })}`;
  };

  const totalSentDisplay = React.useMemo(() => {
    const ts: any = (data as any)?.totalSent;
    if (!ts) return '₦0.00';
    const minor = period === 'month' ? ts.monthMinor : period === 'week' ? ts.weekMinor : ts.allMinor;
    return formatMoney(minor || 0, ts.currency || 'NGN');
  }, [data, period]);

  const firstName = (data as any)?.firstName || '';

  // Auto-refresh when success page broadcasts completion
  React.useEffect(() => {
    const onRefresh = () => refetch();
    window.addEventListener('transfers:refresh', onRefresh as any);
    // Also refresh when the tab regains focus (but only do a single refetch here)
    const onFocus = () => refetch();
    window.addEventListener('focus', onFocus);
    return () => {
      window.removeEventListener('transfers:refresh', onRefresh as any);
      window.removeEventListener('focus', onFocus);
    };
  }, [refetch]);

  return (
    <RequireAuth>
    <div className="min-h-dvh p-6 max-w-3xl mx-auto space-y-6">
      {/* A) Header Row */}
      <div className="flex items-center justify-between gap-3">
        <div className="text-xl font-semibold">Hi{firstName ? `, ${firstName}` : ''}</div>
        <div className="flex items-center gap-2">
          <Link href="/support" className="border rounded px-2 py-1 text-sm">Help</Link>
          <Link href="/notifications" className="border rounded px-2 py-1 text-sm">Notifications</Link>
          <button onClick={() => refetch()} className="text-sm border rounded px-3 py-1" disabled={isFetching}>{isFetching ? 'Refreshing...' : 'Refresh'}</button>
        </div>
      </div>

      {isLoading && (
        <div className="space-y-4">
          <CardSkeleton lines={2} />
          <CardSkeleton lines={2} />
          <CardSkeleton lines={4} />
        </div>
      )}
      {error && (
        <div className="text-sm text-red-600 border border-red-200 rounded p-2">
          {(error as any)?.response?.data?.message || (error as Error).message}
        </div>
      )}

      {data && (
        <div className="space-y-6">
          {/* B) Total Sent card */}
          <section className="border rounded p-4">
            <div className="flex items-center justify-between mb-1">
              <div className="text-xs text-gray-600">Total Sent</div>
              <Link href="/transfers" className="text-xs text-gray-800 underline">Transaction History ›</Link>
            </div>
            <div className="flex items-center justify-between">
              <div className="text-3xl font-bold">
                {showAmount ? totalSentDisplay : '•••••'}
              </div>
              <button
                className="text-xs border rounded px-2 py-1"
                onClick={() => {
                  setShowAmount((v) => {
                    const nv = !v; window.localStorage.setItem('dash:showTotalSent', nv ? '1' : '0'); return nv;
                  });
                }}
              >{showAmount ? 'Hide' : 'Show'}</button>
            </div>
            <div className="mt-3 flex items-center gap-2 text-xs">
              {(['all','month','week'] as const).map((p) => (
                <button key={p} className={`px-2 py-1 rounded border ${period===p?'bg-black text-white':'bg-white text-black'}`} onClick={() => { setPeriod(p); window.localStorage.setItem('dash:totalSentPeriod', p); }}>{p==='all'?'All-time':p==='month'?'This month':'This week'}</button>
              ))}
            </div>
          </section>

          {/* C) Primary CTA */}
          <section className="border rounded p-4">
            <div className="text-base font-medium">Send Money</div>
            <div className="text-sm text-gray-600 mb-3">To bank · Pay with Mobile Money</div>
            <Link href="/transfer/verify" className="inline-block bg-black text-white text-sm px-3 py-1.5 rounded">Start Transfer</Link>
          </section>

          {/* D) Quick Actions */}
          <section className="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <Link href="/transfer/verify" className="border rounded p-3 text-center">Start Transfer</Link>
            <Link href="/transfers" className="border rounded p-3 text-center">Transfer History</Link>
            {/* Recipients and Rates hidden if not present; placeholders omitted */}
          </section>

          <section>
            <h2 className="text-lg font-semibold mb-2">Recent Transfers</h2>
            {feedQ.isLoading ? (
              <div className="space-y-2">
                <CardSkeleton lines={2} />
              </div>
            ) : (recentItems.length === 0) ? (
              <div className="space-y-2">
                <div className="text-sm text-gray-600">No recent transfers.</div>
                <Link href="/transfer/verify" className="inline-block bg-black text-white text-sm px-3 py-1.5 rounded">Start your first transfer</Link>
              </div>
            ) : (
              <div className="border rounded divide-y">
                {recentItems.map((it) => (
                  <Link key={it.id} href={`/transfer/${it.transferId}/timeline`} className="block p-3">
                    <div className="flex items-center justify-between">
                      <div className="flex items-start gap-3">
                        <div className={`w-5 h-5 flex items-center justify-center rounded-full bg-gray-100 text-black text-xs`}>{it.sign === -1 ? "↑" : "↓"}</div>
                        <div>
                          <div className="font-medium line-clamp-1" title={it.label}>{it.label}</div>
                          <div className="text-xs text-gray-600" suppressHydrationWarning>{it.at ? new Date(it.at).toLocaleString() : ""}</div>
                        </div>
                      </div>
                      <div className="text-right">
                        <div className="text-sm font-semibold text-black">{(it.sign === -1 ? "-" : "+")}{formatMoney(Math.abs(it.amountMinor), it.currency)}</div>
                        <div className="text-xs text-gray-600">{it.statusLabel}</div>
                      </div>
                    </div>
                  </Link>
                ))}
              </div>
            )}
            {(recentItems.length > 0) && (
              <div className="pt-2">
                <Link href="/transfer/verify" className="inline-block border rounded px-3 py-1 text-sm">New transfer</Link>
              </div>
            )}
          </section>

          {/* F) KYC/Security nudge */}
          {(!data.kyc || (data.kyc?.status ?? 'unverified') !== 'verified') && (
            <section className="border rounded p-3 bg-amber-50 text-amber-900 text-sm">
              Verify your identity to increase limits and keep transfers secure. <Link href="/kyc" className="underline">Start KYC</Link>
            </section>
          )}
        </div>
      )}
    </div>
    </RequireAuth>
  );
}
