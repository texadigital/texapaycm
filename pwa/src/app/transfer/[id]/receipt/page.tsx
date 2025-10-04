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
      // Fetch PDF as blob and trigger download without new tab
      const res = await fetch(`/api/mobile/transfers/${id}/receipt.pdf`, { credentials: 'include' });
      const blob = await res.blob();
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `receipt-${id}.pdf`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
      return true;
    },
  });

  const [shareUrl, setShareUrl] = React.useState<string | null>(null);
  const [showViewer, setShowViewer] = React.useState<boolean>(true);
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
        <div className="space-y-3 text-sm">
          {data.expires_at ? (
            <div className="text-gray-600">Expires: <span suppressHydrationWarning>{new Date(data.expires_at).toLocaleString()}</span></div>
          ) : null}

          {/* Inline viewer */}
          {showViewer && data.url ? (
            <div className="border rounded overflow-hidden" style={{height: 600}}>
              <iframe src={data.url} title="Receipt" className="w-full h-full" />
            </div>
          ) : null}
          {data.url ? (
            <div className="flex items-center gap-2">
              <button className="border rounded px-3 py-1" onClick={() => setShowViewer((v) => !v)}>
                {showViewer ? 'Hide viewer' : 'Show viewer'}
              </button>
              <button className="border rounded px-3 py-1" onClick={() => pdf.mutate()} disabled={pdf.isPending}>
                {pdf.isPending ? 'Preparing…' : 'Download PDF'}
              </button>
              <button className="border rounded px-3 py-1" onClick={() => share.mutate()} disabled={share.isPending}>
                {share.isPending ? 'Generating…' : 'Generate share link'}
              </button>
            </div>
          ) : null}

          {shareUrl ? (
            <div className="text-xs text-gray-700 break-all">
              Share URL:
              <span className="ml-1 select-all">{shareUrl}</span>
              <button
                className="ml-2 border rounded px-2 py-0.5"
                onClick={() => { try { if (navigator.clipboard) navigator.clipboard.writeText(shareUrl); } catch {} }}
              >Copy</button>
            </div>
          ) : null}
        </div>
      )}
    </div>
  );
}
