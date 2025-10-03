"use client";
import React from "react";
import { useParams } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import http from "@/lib/api";
import PageHeader from "@/components/ui/page-header";
import { CardSkeleton } from "@/components/ui/skeleton";

type TimelineItem = { at: string; message: string };

type TimelineRes = {
  success: boolean;
  timeline: TimelineItem[];
  status: string;
  payinStatus?: string;
  payoutStatus?: string;
};

export default function TransferTimelinePage() {
  const params = useParams<{ id: string }>();
  const id = params?.id;

  const { data, isLoading, error, refetch, isFetching } = useQuery<TimelineRes>({
    queryKey: ["transfer-timeline", id],
    queryFn: async () => {
      const res = await http.get(`/api/mobile/transfers/${id}/timeline`);
      return res.data;
    },
    enabled: !!id,
  });

  return (
    <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-4">
      <PageHeader title="Transfer timeline">
        <button className="border rounded px-3 py-1" onClick={() => refetch()} disabled={isFetching}>
          {isFetching ? "Refreshing..." : "Refresh"}
        </button>
      </PageHeader>

      {isLoading && (
        <div className="space-y-3">
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
        <div className="space-y-3">
          <div className="text-sm">Status: <span className="font-medium capitalize">{data.status}</span></div>
          <div className="text-sm">Pay‑in: <span className="capitalize">{data.payinStatus || "—"}</span>; Payout: <span className="capitalize">{data.payoutStatus || "—"}</span></div>
          <div className="border rounded divide-y">
            {data.timeline.length === 0 ? (
              <div className="p-3 text-sm text-gray-600">No events yet.</div>
            ) : (
              data.timeline.map((t, i) => (
                <div key={i} className="p-3 text-sm flex items-start justify-between gap-3">
                  <div className="font-medium">
                    {t.message?.trim() || (data.payinStatus === 'success' && i === 0 ? 'Pay‑in confirmed' : data.status?.replaceAll('_', ' ') || 'Update')}
                  </div>
                  <div className="text-xs text-gray-600 whitespace-nowrap">{new Date(t.at).toLocaleString()}</div>
                </div>
              ))
            )}
          </div>
        </div>
      )}
    </div>
  );
}
