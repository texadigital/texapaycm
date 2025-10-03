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
              <div className="text-xl font-semibold">{d.dailyCap?.toLocaleString() ?? "—"} XAF</div>
              <div className="text-xs text-gray-600">Used today: {d.usedToday?.toLocaleString() ?? 0} • Remaining: {d.remainingXafDay?.toLocaleString() ?? 0}</div>
            </div>
            <div className="border rounded p-3">
              <div className="text-gray-600">Monthly cap</div>
              <div className="text-xl font-semibold">{d.monthlyCap?.toLocaleString() ?? "—"} XAF</div>
              <div className="text-xs text-gray-600">Used this month: {d.usedMonth?.toLocaleString() ?? 0} • Remaining: {d.remainingXafMonth?.toLocaleString() ?? 0}</div>
            </div>
          </div>
        )}
      </div>
    </RequireAuth>
  );
}
