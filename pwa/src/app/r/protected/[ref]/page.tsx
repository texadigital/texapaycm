"use client";
import React, { useEffect, useState } from "react";
import { useParams, useSearchParams } from "next/navigation";

export default function ReceiverRequestReleasePage() {
  const { ref } = useParams<{ ref: string }>();
  const search = useSearchParams();
  const sig = search.get("sig");
  const exp = search.get("exp");

  const [status, setStatus] = useState<"idle" | "submitting" | "ok" | "error">("idle");
  const [message, setMessage] = useState<string>("");
  const [phone, setPhone] = useState<string>("");
  const [name, setName] = useState<string>("");

  useEffect(() => {
    async function submit() {
      if (!ref || !sig) {
        setStatus("error");
        setMessage("Invalid or missing link details.");
        return;
      }
      setStatus("submitting");
      try {
        const url = `/api/mobile/protected/${encodeURIComponent(ref)}/request-release?sig=${encodeURIComponent(sig)}${exp ? `&exp=${encodeURIComponent(exp)}` : ""}`;
        const res = await fetch(url, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ phone: phone || undefined, name: name || undefined }) });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          setStatus("error");
          setMessage(data?.error || "This link is invalid or expired.");
          return;
        }
        setStatus("ok");
        setMessage(data?.note === 'ALREADY_SENT_RECENTLY' ? "Request already sent recently. We've notified the sender." : "Thanks! We've notified the sender to approve.");
      } catch (e: any) {
        setStatus("error");
        setMessage("Something went wrong. Please try again later.");
      }
    }
    submit();
  }, [ref, sig, exp]);

  return (
    <main className="max-w-md mx-auto p-6 space-y-4">
      <h1 className="text-2xl font-semibold">Texa Protect</h1>
      {status !== "ok" && (
        <div className="space-y-3 border rounded p-3">
          <div className="text-sm">Optionally provide your details for the sender:</div>
          <div>
            <label className="block text-sm text-gray-600">Your Name (optional)</label>
            <input className="mt-1 w-full border rounded px-3 py-2" value={name} onChange={(e)=>setName(e.target.value)} placeholder="e.g. John Doe" />
          </div>
          <div>
            <label className="block text-sm text-gray-600">Phone (optional)</label>
            <input className="mt-1 w-full border rounded px-3 py-2" value={phone} onChange={(e)=>setPhone(e.target.value.replace(/\D+/g,''))} placeholder="e.g. 0803..." inputMode="tel" />
          </div>
          <button
            className="bg-black text-white rounded px-3 py-2"
            onClick={() => {
              // Re-run submission with body
              setStatus("idle");
              (async () => {
                const url = `/api/mobile/protected/${encodeURIComponent(ref as string)}/request-release?sig=${encodeURIComponent(sig as string)}${exp ? `&exp=${encodeURIComponent(exp)}` : ""}`;
                setStatus("submitting");
                try {
                  const res = await fetch(url, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ phone: phone || undefined, name: name || undefined }) });
                  const data = await res.json().catch(() => ({}));
                  if (!res.ok) { setStatus("error"); setMessage(data?.error || "This link is invalid or expired."); return; }
                  setStatus("ok"); setMessage("Thanks! We've notified the sender to approve.");
                } catch {
                  setStatus("error"); setMessage("Something went wrong. Please try again.");
                }
              })();
            }}
          >Send request</button>
        </div>
      )}
      {status === "ok" && (
        <div className="p-3 rounded border bg-green-50 text-green-800">{message}</div>
      )}
      {status === "error" && (
        <div className="p-3 rounded border bg-red-50 text-red-800">{message}</div>
      )}
      <p className="text-sm text-gray-600">
        You can close this page now. The sender will review and approve to release the funds.
      </p>
    </main>
  );
}
