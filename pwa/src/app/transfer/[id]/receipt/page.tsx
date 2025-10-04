"use client";
import React from "react";
import { useParams } from "next/navigation";
import { useMutation, useQuery } from "@tanstack/react-query";
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

  const pdf = useMutation({
    mutationFn: async () => {
      // Using GET to receipt.pdf; just open in new tab
      window.open(`/api/mobile/transfers/${id}/receipt.pdf`, "_blank");
      return true;
    },
  });

  const [shareUrl, setShareUrl] = React.useState<string | null>(null);
  const share = useMutation({
    mutationFn: async () => {
      const res = await http.post(`/api/mobile/transfers/${id}/share-url`);
      return res.data as { url?: string; message?: string };
    },
    onSuccess: (d) => {
      const u = d?.url || "";
      setShareUrl(u);
      try {
        if (u && navigator.clipboard) navigator.clipboard.writeText(u);
      } catch {}
    },
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
            <div className="text-gray-600">Expires: <span suppressHydrationWarning>{new Date(data.expires_at).toLocaleString()}</span></div>
          ) : null}
          <div className="pt-2 flex gap-2">
            <button className="border rounded px-3 py-1" onClick={() => pdf.mutate()} disabled={pdf.isPending}>Download PDF</button>
            <button className="border rounded px-3 py-1" onClick={() => share.mutate()} disabled={share.isPending}>{share.isPending ? 'Generating…' : 'Generate share link'}</button>
          </div>
          {shareUrl ? (
            <div className="text-xs text-gray-700 break-all">Share URL: <a className="underline" href={shareUrl} target="_blank" rel="noreferrer">{shareUrl}</a></div>
          ) : null}
        </div>
      )}
    </div>
  );
}
