"use client";
import React from "react";
import { useParams } from "next/navigation";
import { useMutation } from "@tanstack/react-query";
import http from "@/lib/api";

type PayoutRes = {
  status: string; // success|pending|error
  message?: string;
  payout_ref?: string;
};

type PayoutStatusRes = { status: string; transfer_status?: string };

export default function PayoutPage() {
  const params = useParams<{ id: string }>();
  const id = params?.id;

  const [result, setResult] = React.useState<PayoutRes | null>(null);
  const [topError, setTopError] = React.useState<string | null>(null);

  const initiate = useMutation({
    mutationFn: async () => {
      setTopError(null);
      setResult(null);
      const res = await http.post(`/api/mobile/transfers/${id}/payout`);
      return res.data as PayoutRes;
    },
    onSuccess: (d) => setResult(d),
    onError: (e: any) => setTopError(e?.response?.data?.message || e.message),
  });

  const poll = useMutation({
    mutationFn: async () => {
      const res = await http.post(`/api/mobile/transfers/${id}/payout/status`);
      return res.data as PayoutStatusRes;
    },
    onSuccess: (d) => setResult((r) => ({ ...(r || { status: d.status }), status: d.status } as PayoutRes)),
    onError: (e: any) => setTopError(e?.response?.data?.message || e.message),
  });

  return (
    <div className="min-h-dvh p-6 max-w-xl mx-auto space-y-4">
      <h1 className="text-2xl font-semibold">Payout</h1>
      {topError ? (
        <div className="text-sm text-red-600 border border-red-200 rounded p-2">{topError}</div>
      ) : null}

      <div className="space-x-2">
        <button className="border rounded px-3 py-2" onClick={() => initiate.mutate()} disabled={initiate.isPending}>
          {initiate.isPending ? "Starting..." : "Initiate payout"}
        </button>
        <button className="border rounded px-3 py-2" onClick={() => poll.mutate()} disabled={poll.isPending}>
          {poll.isPending ? "Checking..." : "Check payout status"}
        </button>
      </div>

      {result && (
        <div className="border rounded p-3 text-sm space-y-1">
          <div>Status: <span className="capitalize font-medium">{result.status}</span></div>
          {result.payout_ref ? (
            <div>Reference: <span className="font-mono">{result.payout_ref}</span></div>
          ) : null}
          {result.message ? <div className="text-gray-700">{result.message}</div> : null}
        </div>
      )}
    </div>
  );
}
