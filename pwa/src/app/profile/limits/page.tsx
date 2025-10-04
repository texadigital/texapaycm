"use client";
import React from "react";
import { useQuery } from "@tanstack/react-query";
import RequireAuth from "@/components/guards/require-auth";
import http from "@/lib/api";
import PageHeader from "@/components/ui/page-header";
import { CardSkeleton } from "@/components/ui/skeleton";

 type LimitsRes = {
  minXaf?: number;
  maxXaf?: number;
  dailyCap?: number;
  monthlyCap?: number;
  usedToday?: number;
  usedMonth?: number;
  remainingXafDay?: number;
  remainingXafMonth?: number;
 } & Record<string, any>;

export default function ProfileLimitsPage() {
  const q = useQuery<LimitsRes>({
    queryKey: ["pricing-limits"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/pricing/limits");
      return res.data as any;
    },
    staleTime: 5 * 60_000,
  });

  const d = q.data;
  // Fallback snapshot from last quote error, if present
  let fallback: Partial<LimitsRes> | null = null;
  try {
    const raw = sessionStorage.getItem('limits:last');
    if (raw) fallback = JSON.parse(raw);
  } catch {}

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-xl mx-auto space-y-4">
        <PageHeader title="Limits" />

        {q.isLoading ? (
          <div className="space-y-2">
            <CardSkeleton lines={3} />
            <CardSkeleton lines={3} />
          </div>
        ) : q.error ? (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">
            {(q.error as any)?.response?.data?.message || (q.error as Error).message}
          </div>
        ) : !d ? (
          <div className="text-sm text-gray-600 border rounded p-3">No limits available.</div>
        ) : (
          <div className="grid gap-3 sm:grid-cols-2 text-sm">
            <div className="sm:col-span-2 flex items-center justify-end">
              <button className="text-xs underline" onClick={() => q.refetch()} disabled={q.isFetching}>{q.isFetching ? 'Refreshing…' : 'Refresh'}</button>
            </div>
            <div className="border rounded p-3">
              <div className="text-gray-600">Minimum per transfer</div>
              <div className="text-xl font-semibold">{d.minXaf?.toLocaleString() ?? "—"} XAF</div>
            </div>
            <div className="border rounded p-3">
              <div className="text-gray-600">Maximum per transfer</div>
              <div className="text-xl font-semibold">{d.maxXaf?.toLocaleString() ?? "—"} XAF</div>
            </div>
            <div className="border rounded p-3">
              <div className="text-gray-600">Daily cap</div>
              <div className="text-xl font-semibold flex items-center gap-2">
                {d.dailyCap != null ? `${d.dailyCap.toLocaleString()} XAF` : '— XAF'}
                {d.dailyCap == null && (
                  <span className="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-700">Unconfigured (defaults may apply)</span>
                )}
              </div>
              <div className="text-xs text-gray-600">Used today: {(d.usedToday ?? fallback?.usedToday ?? 0).toLocaleString?.() || String(d.usedToday ?? fallback?.usedToday ?? 0)} • Remaining: {(d.remainingXafDay ?? fallback?.remainingXafDay ?? 0).toLocaleString?.() || String(d.remainingXafDay ?? fallback?.remainingXafDay ?? 0)}</div>
            </div>
            <div className="border rounded p-3">
              <div className="text-gray-600">Monthly cap</div>
              <div className="text-xl font-semibold flex items-center gap-2">
                {d.monthlyCap != null ? `${d.monthlyCap.toLocaleString()} XAF` : '— XAF'}
                {d.monthlyCap == null && (
                  <span className="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-700">Unconfigured (defaults may apply)</span>
                )}
              </div>
              <div className="text-xs text-gray-600">Used this month: {(d.usedMonth ?? fallback?.usedMonth ?? 0).toLocaleString?.() || String(d.usedMonth ?? fallback?.usedMonth ?? 0)} • Remaining: {(d.remainingXafMonth ?? fallback?.remainingXafMonth ?? 0).toLocaleString?.() || String(d.remainingXafMonth ?? fallback?.remainingXafMonth ?? 0)}</div>
            </div>
            {fallback?.updatedAt && (
              <div className="sm:col-span-2 text-xs text-gray-500 text-right">Fallback from last quote update at {new Date(fallback.updatedAt as any).toLocaleTimeString()}</div>
            )}
          </div>
        )}
      </div>
    </RequireAuth>
  );
}
