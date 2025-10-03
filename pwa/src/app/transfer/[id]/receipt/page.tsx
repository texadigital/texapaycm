"use client";
import React from "react";
import { useParams } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import http from "@/lib/api";
import PageHeader from "@/components/ui/page-header";
import { CardSkeleton } from "@/components/ui/skeleton";

type ReceiptUrlRes = { success: boolean; url: string; expires_at?: string };

export default function ReceiptPage() {
  const params = useParams<{ id: string }>();
  const id = params?.id;

  const { data, isLoading, error, refetch, isFetching } = useQuery<ReceiptUrlRes>({
    queryKey: ["receipt-url", id],
    queryFn: async () => {
      const res = await http.get(`/api/mobile/transfers/${id}/receipt-url`);
      return res.data;
    },
    enabled: !!id,
  });

  return (
    <div className="min-h-dvh p-6 max-w-xl mx-auto space-y-4">
      <PageHeader title="Receipt">
        <button className="border rounded px-3 py-1" onClick={() => refetch()} disabled={isFetching}>
          {isFetching ? "Refreshing..." : "Refresh"}
        </button>
      </PageHeader>

      {isLoading && (
        <div className="space-y-3">
          <CardSkeleton lines={2} />
        </div>
      )}
      {error && (
        <div className="text-sm text-red-600 border border-red-200 rounded p-2">
          {(error as any)?.response?.data?.message || (error as Error).message}
        </div>
      )}

      {data && (
        <div className="space-y-2 text-sm">
          <a className="underline text-blue-600" href={data.url} target="_blank" rel="noreferrer">
            Open receipt
          </a>
          {data.expires_at ? (
            <div className="text-gray-600">Expires: {new Date(data.expires_at).toLocaleString()}</div>
          ) : null}
        </div>
      )}
    </div>
  );
}
