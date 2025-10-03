"use client";
import React from "react";
import { useQuery } from "@tanstack/react-query";
import Link from "next/link";
import http from "@/lib/api";
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

export default function DashboardPage() {
  const { data, isLoading, error, refetch, isFetching } = useQuery<DashboardResponse>({
    queryKey: ["dashboard"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/dashboard");
      return res.data;
    },
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
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold">Dashboard</h1>
        <button
          onClick={() => refetch()}
          className="text-sm border rounded px-3 py-1"
          disabled={isFetching}
        >
          {isFetching ? "Refreshing..." : "Refresh"}
        </button>
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
            {(data.recentTransfers?.length ?? 0) === 0 ? (
              <div className="text-sm text-gray-600">No recent transfers.</div>
            ) : (
              <div className="border rounded divide-y">
                {(data.recentTransfers ?? []).map((t) => (
                  <Link key={t.id} href={`/transfer/${t.id}/timeline`} className="block p-3">
                    <div>
                      <div className="font-medium">#{t.id}</div>
                      <div className="text-xs text-gray-600">
                        {t.createdAt ? new Date(t.createdAt).toLocaleString() : "—"}
                      </div>
                    </div>
                    <div className="text-right">
                      <div className="text-sm">{t.amountXaf} XAF</div>
                      <div className="text-xs capitalize text-gray-600">{t.status}</div>
                    </div>
                  </Link>
                ))}
              </div>
            )}
          </section>
        </div>
      )}
    </div>
    </RequireAuth>
  );
}
