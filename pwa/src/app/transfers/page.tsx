"use client";
import React from "react";
import { useQuery } from "@tanstack/react-query";
import RequireAuth from "@/components/guards/require-auth";
import http from "@/lib/api";
import PageHeader from "@/components/ui/page-header";
import { CardSkeleton } from "@/components/ui/skeleton";

export default function TransfersListPage() {
  const [page, setPage] = React.useState(1);
  const perPage = 20;

  const q = useQuery<{ data: Array<{ id: number; status: string; amountXaf?: number; createdAt?: string; reference?: string }>; meta?: any}>({
    queryKey: ["transfers", page],
    queryFn: async () => {
      const res = await http.get("/api/mobile/transfers", { params: { page, perPage } });
      return res.data as any;
    },
    keepPreviousData: true,
  });

  const items = q.data?.data || [];
  const meta = q.data?.meta || {};

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-4">
        <PageHeader title="Transfers" />

        {q.isLoading ? (
          <div className="space-y-2">
            <CardSkeleton lines={3} />
            <CardSkeleton lines={3} />
          </div>
        ) : items.length === 0 ? (
          <div className="text-sm text-gray-600 border rounded p-3">No transfers yet.</div>
        ) : (
          <div className="border rounded divide-y">
            {items.map((t) => (
              <a key={t.id} href={`/transfer/${t.id}/timeline`} className="block p-3 text-sm hover:bg-gray-50">
                <div className="flex items-center justify-between">
                  <div className="font-medium capitalize">{t.status?.replaceAll("_"," ") || "Transfer"}</div>
                  <div className="text-xs text-gray-600">{t.createdAt ? new Date(t.createdAt).toLocaleString() : null}</div>
                </div>
                <div className="text-xs text-gray-600">{t.reference ? `Ref: ${t.reference}` : null}</div>
                {t.amountXaf ? <div className="text-xs">Amount: {t.amountXaf.toLocaleString()} XAF</div> : null}
              </a>
            ))}
          </div>
        )}

        <div className="flex items-center justify-between pt-2">
          <button className="border rounded px-3 py-1 disabled:opacity-50" disabled={page <= 1 || q.isFetching} onClick={() => setPage((p) => Math.max(1, p - 1))}>Prev</button>
          <div className="text-xs text-gray-600">Page {page}{meta?.last_page ? ` / ${meta.last_page}` : ""}</div>
          <button className="border rounded px-3 py-1 disabled:opacity-50" disabled={!!meta?.last_page && page >= meta.last_page || q.isFetching} onClick={() => setPage((p) => p + 1)}>Next</button>
        </div>
      </div>
    </RequireAuth>
  );
}
