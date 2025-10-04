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
      <div className="flex items-center justify-between gap-3">
        <h1 className="text-2xl font-semibold">Dashboard</h1>
        <div className="flex items-center gap-2">
          <Link href="/transfer/verify" className="bg-black text-white text-sm px-3 py-1.5 rounded">
            New transfer
          </Link>
          <button
            onClick={() => refetch()}
            className="text-sm border rounded px-3 py-1"
            disabled={isFetching}
          >
            {isFetching ? "Refreshing..." : "Refresh"}
          </button>
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
          <section className="grid grid-cols-2 gap-4">
            <div className="border rounded p-4">
              <div className="text-xs text-gray-500">KYC Status</div>
              <div className="text-lg font-medium capitalize">{data.kyc?.status ?? "—"}</div>
              <div className="text-sm text-gray-600">Level {data.kyc?.level ?? "—"}</div>
            </div>
            <div className="border rounded p-4">
              <div className="text-xs text-gray-500">Today</div>
              <div className="text-lg font-medium">{data.today?.count ?? 0} transfers</div>
              <div className="text-sm text-gray-600">Total {data.today?.totalXaf ?? 0} XAF</div>
            </div>
            <div className="border rounded p-4">
              <div className="text-xs text-gray-500">This Month</div>
              <div className="text-lg font-medium">{data.month?.count ?? 0} transfers</div>
              <div className="text-sm text-gray-600">Total {data.month?.totalXaf ?? 0} XAF</div>
            </div>
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
        </div>
      )}
    </div>
    </RequireAuth>
  );
}
