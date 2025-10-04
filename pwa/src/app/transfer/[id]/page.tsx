"use client";
import React from "react";
import { useParams } from "next/navigation";
import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import RequireAuth from "@/components/guards/require-auth";
import PageHeader from "@/components/ui/page-header";
import http from "@/lib/api";
import { CardSkeleton } from "@/components/ui/skeleton";

 type TransferDetails = {
  id: number;
  reference?: string;
  status: string;
  createdAt?: string;
  amountXaf?: number;
  feeTotalXaf?: number;
  totalPayXaf?: number;
  receiveNgnMinor?: number;
  adjustedRate?: number;
  payerMsisdn?: string;
  bankName?: string;
  accountNumber?: string;
  accountName?: string;
};

export default function TransferDetailsPage() {
  const params = useParams<{ id: string }>();
  const id = params?.id;

  const q = useQuery<TransferDetails>({
    queryKey: ["transfer-details", id],
    queryFn: async () => {
      const res = await http.get(`/api/mobile/transfers/${id}`);
      return res.data as any;
    },
    enabled: !!id,
  });

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-4">
        <PageHeader title={`Transfer #${id}`} />

        {q.isLoading && (
          <div className="space-y-3">
            <CardSkeleton lines={2} />
            <CardSkeleton lines={4} />
          </div>
        )}
        {q.error && (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">
            {(q.error as any)?.response?.data?.message || (q.error as Error).message}
          </div>
        )}

        {q.data && (
          <div className="space-y-3 text-sm">
            <div className="border rounded p-3 space-y-1">
              <div className="flex items-center justify-between">
                <div className="font-medium">Ref: {q.data.reference || `#${id}`}</div>
                <div className="text-xs text-gray-600"><span suppressHydrationWarning>{q.data.createdAt ? new Date(q.data.createdAt).toLocaleString() : null}</span></div>
              </div>
              <div>Status: <span className="font-medium capitalize">{q.data.status}</span></div>
              <div className="grid grid-cols-2 gap-2">
                <div>Amount (XAF): <span className="font-medium">{q.data.amountXaf?.toLocaleString() ?? "—"}</span></div>
                <div>Fees (XAF): <span className="font-medium">{q.data.feeTotalXaf?.toLocaleString() ?? 0}</span></div>
                <div>Total pay (XAF): <span className="font-medium">{q.data.totalPayXaf?.toLocaleString() ?? "—"}</span></div>
                <div>Receiver (NGN): <span className="font-medium">{q.data.receiveNgnMinor ? (q.data.receiveNgnMinor/100).toFixed(2) : "—"}</span></div>
                <div className="col-span-2">Rate: <span className="font-medium">1 XAF → NGN {q.data.adjustedRate ?? "—"}</span></div>
              </div>
              <div className="pt-1">Recipient: <span className="font-medium">{q.data.accountName || "—"}</span></div>
              <div className="text-gray-700">Bank: {q.data.bankName || "—"} • {q.data.accountNumber || "—"}</div>
              <div className="text-gray-700">Payer: {q.data.payerMsisdn || "—"}</div>
            </div>

            <div className="flex items-center gap-2">
              <Link className="border rounded px-3 py-1" href={`/transfer/${id}/timeline`}>Timeline</Link>
              <Link className="border rounded px-3 py-1" href={`/transfer/${id}/receipt`}>Receipt</Link>
              <Link className="border rounded px-3 py-1" href={`/transfer/${id}/payout`}>Payout</Link>
            </div>
          </div>
        )}
      </div>
    </RequireAuth>
  );
}
