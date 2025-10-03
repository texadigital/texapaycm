"use client";
import React from "react";
import { useMutation } from "@tanstack/react-query";
import { useRouter, useSearchParams } from "next/navigation";
import http from "@/lib/api";
import RequireAuth from "@/components/guards/require-auth";
import { validateCameroon, providerMeta, formatForDisplay } from "@/lib/phone";

type ConfirmReq = { quoteId: number; bankCode: string; accountNumber: string; msisdn: string };

type ConfirmRes = {
  success: boolean;
  transfer: { id: number; status: string; payinRef?: string };
};

type StatusRes = { success?: boolean; status?: string; message?: string };

export default function ConfirmPage() {
  const router = useRouter();
  const sp = useSearchParams();
  const quoteId = Number(sp.get("quoteId") || 0);
  const bankCode = sp.get("bankCode") || "";
  const bankName = sp.get("bankName") || "";
  const accountNumber = sp.get("account") || "";
  const accountName = sp.get("accountName") || "";
  const amountXaf = Number(sp.get("amount") || 0);
  const totalPayXaf = Number(sp.get("total") || 0);
  const receiveMinor = Number(sp.get("receiveMinor") || 0);
  const rate = sp.get("rate") || "";

  const [msisdn, setMsisdn] = React.useState("");
  const [transferId, setTransferId] = React.useState<number | null>(null);
  const [topError, setTopError] = React.useState<string | null>(null);
  const [payinStatus, setPayinStatus] = React.useState<string | null>(null);

  const confirm = useMutation({
    mutationFn: async (vars: ConfirmReq) => {
      setTopError(null);
      const res = await http.post("/api/mobile/transfers/confirm", vars);
      return res.data as ConfirmRes;
    },
    onSuccess: (data) => {
      setTransferId(data.transfer.id);
      setPayinStatus(data.transfer.status || "payin_pending");
    },
    onError: (e: any) => setTopError(e?.response?.data?.message || e.message),
  });

  const poll = useMutation({
    mutationFn: async () => {
      if (!transferId) return { status: "" } as StatusRes;
      const res = await http.post(`/api/mobile/transfers/${transferId}/payin/status`);
      return res.data as StatusRes;
    },
    onSuccess: (d) => setPayinStatus(d.status || null),
    onError: (e: any) => setTopError(e?.response?.data?.message || e.message),
  });

  // Auto-poll every 3s when pending, stop on success/failure
  React.useEffect(() => {
    if (!transferId) return;
    if (!payinStatus || payinStatus.includes("pending")) {
      const id = setInterval(() => poll.mutate(), 3000);
      return () => clearInterval(id);
    }
    if (payinStatus === "success" || payinStatus === "completed") {
      const qp = new URLSearchParams({
        bankName,
        accountName,
        account: accountNumber,
        amount: String(amountXaf || 0),
        receiveMinor: String(receiveMinor || 0),
      });
      router.replace(`/transfer/${transferId}/success?${qp.toString()}`);
    }
  }, [transferId, payinStatus]);

  const receiveNgn = (receiveMinor || 0) / 100;

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-xl mx-auto space-y-6">
        <h1 className="text-2xl font-semibold">Payment</h1>
        {topError ? (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">{topError}</div>
        ) : null}

        {/* Summary */}
        <div className="border rounded p-3 text-sm space-y-1">
          <div className="font-medium">{accountName || "Recipient"}</div>
          <div className="text-gray-600">{bankName} • {accountNumber}</div>
          <div className="pt-2 grid grid-cols-2 gap-2">
            <div>Amount (XAF): <span className="font-medium">{amountXaf || "—"}</span></div>
            <div>Receiver (NGN): <span className="font-medium">₦ {receiveNgn.toFixed(2)}</span></div>
            <div>Total pay: {totalPayXaf || amountXaf} XAF</div>
            <div>Rate: {rate || "—"}</div>
          </div>
        </div>

        {/* MSISDN */}
        <form
          className="space-y-3"
          onSubmit={(e) => {
            e.preventDefault();
            const v = validateCameroon(msisdn);
            if (!v.valid) {
              setTopError(v.error || "Invalid phone number");
              return;
            }
            confirm.mutate({ quoteId: Number(quoteId), bankCode, accountNumber, msisdn: v.normalized });
          }}
        >
          <div>
            <label className="block text-sm mb-1">Payer phone (MSISDN)</label>
            <input
              className="w-full border rounded px-3 py-2"
              value={msisdn}
              onChange={(e) => setMsisdn(e.target.value)}
              placeholder="e.g. 2376XXXXXXXX"
              required
            />
            {(() => {
              const v = validateCameroon(msisdn);
              const meta = providerMeta(v.provider);
              return (
                <div className="mt-1 flex items-center gap-2 text-xs">
                  <span className="text-gray-600">{formatForDisplay(msisdn)}</span>
                  {meta ? (
                    <span className={`px-2 py-0.5 rounded ${meta.color}`}>{meta.label}</span>
                  ) : null}
                </div>
              );
            })()}
          </div>
          <button
            type="submit"
            className="w-full bg-black text-white px-4 py-2 rounded disabled:opacity-50"
            disabled={confirm.isPending}
          >
            {confirm.isPending ? "Processing…" : "Pay with Mobile Money"}
          </button>
        </form>

        {/* Processing / USSD guidance */}
        {transferId && (
          <div className="border rounded p-3 text-sm space-y-2">
            <div>Transfer ID: <span className="font-mono">{transferId}</span></div>
            <div>Status: <span className="font-medium capitalize">{payinStatus || "—"}</span></div>
            <div className="text-gray-700">If prompted, dial the USSD to approve:
              <ul className="list-disc pl-5">
                <li>MTN: *126#</li>
                <li>Orange: *150#</li>
              </ul>
            </div>
            <div>
              <button className="border rounded px-3 py-2" onClick={() => poll.mutate()} disabled={poll.isPending}>
                {poll.isPending ? "Checking…" : "Refresh status"}
              </button>
            </div>
          </div>
        )}
      </div>
    </RequireAuth>
  );
}
