"use client";
import React, { useEffect, useMemo, useState } from "react";
import { useParams, usePathname, useRouter } from "next/navigation";
import http from "@/lib/api";

type Txn = {
  transaction: {
    funding_ref: string;
    escrow_state: string;
    amount_ngn_minor: number;
    fee_ngn_minor?: number | null;
    receiver_bank_code: string;
    receiver_bank_name?: string | null;
    receiver_account_number: string;
    receiver_account_name?: string | null;
    auto_release_at?: string | null;
    payout_status?: string | null;
  };
  timeline: Array<any>;
  share?: { requestReleaseLink?: string };
  va?: { bank_code?: string|null; account_number?: string|null; reference?: string|null };
};

function formatNgnMinor(minor?: number | null) {
  if (!minor && minor !== 0) return "-";
  return (minor / 100).toLocaleString(undefined, { style: "currency", currency: "NGN" });
}

function Countdown({ to }: { to?: string | null }) {
  const [remain, setRemain] = useState<string>("");
  useEffect(() => {
    if (!to) return;
    const target = new Date(to).getTime();
    const t = setInterval(() => {
      const now = Date.now();
      const delta = Math.max(0, target - now);
      const mins = Math.floor(delta / 60000);
      const secs = Math.floor((delta % 60000) / 1000);
      setRemain(`${mins}m ${secs}s`);
    }, 1000);
    return () => clearInterval(t);
  }, [to]);
  if (!to) return null;
  return <span className="text-sm text-gray-600">Auto-release in {remain}</span>;
}

export default function ProtectedDetails() {
  const { ref } = useParams<{ ref: string }>();
  const router = useRouter();
  const pathname = usePathname() || "";
  const [data, setData] = useState<Txn | null>(null);
  const [loading, setLoading] = useState(true);
  const [err, setErr] = useState<string | null>(null);
  const [actionMsg, setActionMsg] = useState<string | null>(null);
  const [copyMsg, setCopyMsg] = useState<string | null>(null);

  const escrowState = data?.transaction.escrow_state;
  const canApprove = escrowState === "awaiting_approval";
  const canDispute = escrowState === "awaiting_approval";
  const isCreated = escrowState === "created";
  const shouldPoll = escrowState === "created" || escrowState === "awaiting_approval";

  async function load() {
    setErr(null);
    setLoading(true);
    try {
      const res = await http.get(`/api/mobile/protected/${ref}`);
      setData(res.data as Txn);
    } catch (e: any) {
      const msg = e?.response?.data?.message || e?.message || "Failed to load";
      setErr(msg);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [ref]);

  // Auto-refresh while awaiting important transitions
  useEffect(() => {
    if (!shouldPoll) return;
    const id = setInterval(load, 5000);
    return () => clearInterval(id);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [shouldPoll, ref]);

  // Redirect to success page once funds are locked (awaiting_approval)
  useEffect(() => {
    if (escrowState === "awaiting_approval" && !pathname.endsWith('/success')) {
      router.replace(`/protected/${ref}/success`);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [escrowState, pathname]);

  async function approve() {
    setActionMsg(null);
    try {
      const res = await http.post(`/api/mobile/protected/${ref}/approve`);
      setActionMsg("Approve successful. Updating status...");
    } catch (e: any) {
      setActionMsg(e?.response?.data?.error || e?.message || "Approve failed");
    }
    load();
  }

  async function dispute() {
    const reason = prompt("Enter reason for dispute (required)");
    if (!reason) return;
    setActionMsg(null);
    try {
      await http.post(`/api/mobile/protected/${ref}/dispute`, { reason });
      setActionMsg("Dispute opened");
    } catch (e: any) {
      setActionMsg(e?.response?.data?.error || e?.message || "Dispute failed");
    }
    load();
  }

  const amount = data?.transaction.amount_ngn_minor ?? null;
  const fee = data?.transaction.fee_ngn_minor ?? null;
  const net = useMemo(() => (amount != null && fee != null ? amount - fee : null), [amount, fee]);
  const maskedAcct = useMemo(() => {
    const acct = data?.transaction.receiver_account_number || '';
    if (acct.length <= 4) return acct;
    return acct.slice(0, 2) + "******" + acct.slice(-2);
  }, [data?.transaction.receiver_account_number]);
  const shareLink = data?.share?.requestReleaseLink || '';
  const vaInfo = useMemo(() => {
    const fromResp = data?.va || {};
    if (fromResp?.account_number || fromResp?.reference) return {
      bank_code: fromResp.bank_code || '', account_number: fromResp.account_number || '', reference: fromResp.reference || ''
    };
    const t = data?.timeline || [];
    const vaEvt = t.find((e: any) => e.event === 'va_created' || (e.va && e.event));
    const va = vaEvt?.va || {};
    return { bank_code: va.bank_code || '', account_number: va.account_number || '', reference: va.reference || '' };
  }, [data?.va, data?.timeline]);

  async function copyShare() {
    if (!shareLink) return;
    try {
      await navigator.clipboard.writeText(shareLink);
      setCopyMsg("Receiver link copied");
      setTimeout(() => setCopyMsg(null), 2500);
    } catch {
      setCopyMsg("Copy failed. Long-press to copy link.");
      setTimeout(() => setCopyMsg(null), 2500);
    }
  }

  return (
    <main className="max-w-2xl mx-auto p-4 space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold">Protected Details</h1>
        <span className="text-sm px-2 py-1 rounded bg-gray-200 capitalize">{escrowState || "-"}</span>
      </div>

      {loading && <div className="animate-pulse text-gray-500">Loading...</div>}
      {err && <div className="text-sm text-red-600">{err}</div>}

      {data && (
        <div className="space-y-4">
          <div className="grid grid-cols-2 gap-3 text-sm">
            <div>
              <div className="text-gray-500">Reference</div>
              <div className="font-mono">{data.transaction.funding_ref}</div>
            </div>
            <div>
              <div className="text-gray-500">Receiver</div>
              <div>{data.transaction.receiver_account_name || "-"}</div>
              <div className="font-mono text-xs">{data.transaction.receiver_bank_code} / {maskedAcct}</div>
            </div>
            <div>
              <div className="text-gray-500">Amount</div>
              <div>{formatNgnMinor(amount)}</div>
            </div>
            <div>
              <div className="text-gray-500">Fee</div>
              <div>{formatNgnMinor(fee)}</div>
            </div>
            <div>
              <div className="text-gray-500">Net</div>
              <div>{formatNgnMinor(net)}</div>
            </div>
            <div>
              <div className="text-gray-500">Payout Status</div>
              <div>{data.transaction.payout_status || "-"}</div>
            </div>
          </div>

          {isCreated && (
            <div className="p-3 rounded border bg-yellow-50">
              <div className="font-medium">Awaiting Pay-in</div>
              <div className="text-sm text-gray-700 space-y-1">
                <p>Transfer the amount to the Virtual Account generated for this transaction. Once credited, funds will be held in escrow.</p>
                <ul className="list-disc pl-5">
                  <li>Use bank app/USSD to make the transfer.</li>
                  <li>Reference: <span className="font-mono">{data.transaction.funding_ref}</span></li>
                </ul>
                <p>This page auto-refreshes every 5s and will update when the payment is received.</p>
              </div>
              {(vaInfo.bank_code || vaInfo.account_number || vaInfo.reference) && (
                <div className="mt-3 grid grid-cols-2 gap-3 text-sm">
                  {vaInfo.bank_code && (
                    <div>
                      <div className="text-gray-500">VA Bank Code</div>
                      <div className="flex items-center gap-2">
                        <span className="font-mono">{vaInfo.bank_code}</span>
                        <button className="text-xs px-2 py-1 rounded border" onClick={async ()=>{ try { await navigator.clipboard.writeText(vaInfo.bank_code!); } catch {} }}>Copy</button>
                      </div>
                    </div>
                  )}
                  {vaInfo.account_number && (
                    <div>
                      <div className="text-gray-500">VA Account Number</div>
                      <div className="flex items-center gap-2">
                        <span className="font-mono">{vaInfo.account_number}</span>
                        <button className="text-xs px-2 py-1 rounded border" onClick={async ()=>{ try { await navigator.clipboard.writeText(vaInfo.account_number!); } catch {} }}>Copy</button>
                      </div>
                    </div>
                  )}
                  {vaInfo.reference && (
                    <div className="col-span-2">
                      <div className="text-gray-500">VA Reference</div>
                      <div className="flex items-center gap-2">
                        <span className="font-mono break-all">{vaInfo.reference}</span>
                        <button className="text-xs px-2 py-1 rounded border" onClick={async ()=>{ try { await navigator.clipboard.writeText(vaInfo.reference!); } catch {} }}>Copy</button>
                      </div>
                    </div>
                  )}
                </div>
              )}
            </div>
          )}

          {escrowState === "awaiting_approval" && (
            <div className="p-3 rounded border bg-blue-50 space-y-2">
              <div className="font-medium">Funds Locked</div>
              <Countdown to={data.transaction.auto_release_at} />
              <div className="text-sm text-gray-700">You can Approve to release funds to the receiver bank now, or Dispute to hold.</div>
            </div>
          )}

          <div className="flex gap-3">
            <button
              className="bg-green-600 text-white rounded px-4 py-2 disabled:opacity-60"
              disabled={!canApprove}
              onClick={approve}
            >
              Approve
            </button>
            <button
              className="bg-red-600 text-white rounded px-4 py-2 disabled:opacity-60"
              disabled={!canDispute}
              onClick={dispute}
            >
              Dispute
            </button>
          </div>

          {actionMsg && <div className="text-sm text-gray-700">{actionMsg}</div>}

          <div className="space-y-2">
            <h2 className="font-medium">Timeline</h2>
            <ul className="space-y-1 text-sm">
              {data.timeline?.map((t, idx) => (
                <li key={idx} className="flex items-start gap-2">
                  <span className="text-gray-500 w-40 shrink-0">{t.at || ""}</span>
                  <span className="font-mono bg-gray-100 rounded px-2 py-0.5">{t.event || ""}</span>
                </li>
              ))}
            </ul>
          </div>

          {shareLink && escrowState === "awaiting_approval" && (
            <div className="space-y-2">
              <h2 className="font-medium">Receiver Link</h2>
              <div className="flex items-center gap-2">
                <button onClick={copyShare} className="bg-gray-900 text-white rounded px-3 py-2">Copy link</button>
                {copyMsg && <span className="text-sm text-gray-700">{copyMsg}</span>}
              </div>
              <p className="text-xs text-gray-500 break-all">{shareLink}</p>
              <p className="text-xs text-gray-500">Share this link with the receiver to request release. It expires in 24 hours.</p>
            </div>
          )}
        </div>
      )}
    </main>
  );
}
