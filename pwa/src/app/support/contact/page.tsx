"use client";
import React from "react";
import { useMutation } from "@tanstack/react-query";
import RequireAuth from "@/components/guards/require-auth";
import http from "@/lib/api";
import PageHeader from "@/components/ui/page-header";

export default function SupportContactPage() {
  const [subject, setSubject] = React.useState("");
  const [message, setMessage] = React.useState("");
  const [info, setInfo] = React.useState<string | null>(null);
  const [err, setErr] = React.useState<string | null>(null);

  const submit = useMutation({
    mutationFn: async () => {
      setInfo(null); setErr(null);
      const res = await http.post("/api/mobile/support/contact", { subject, message });
      return res.data as any;
    },
    onSuccess: (d: any) => {
      setInfo(d?.message || "Ticket submitted. Our team will reach you shortly.");
      setSubject(""); setMessage("");
    },
    onError: (e: any) => setErr(e?.response?.data?.message || e.message || "Failed"),
  });

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-4">
        <PageHeader title="Contact support" />
        {info ? <div className="text-sm text-green-700 border border-green-200 rounded p-2">{info}</div> : null}
        {err ? <div className="text-sm text-red-600 border border-red-200 rounded p-2">{err}</div> : null}
        <form className="space-y-3" onSubmit={(e) => { e.preventDefault(); submit.mutate(); }}>
          <div>
            <label className="block text-sm mb-1">Subject</label>
            <input className="w-full border rounded px-3 py-2" value={subject} onChange={(e) => setSubject(e.target.value)} required />
          </div>
          <div>
            <label className="block text-sm mb-1">Message</label>
            <textarea className="w-full border rounded px-3 py-2 min-h-32" value={message} onChange={(e) => setMessage(e.target.value)} required />
          </div>
          <button type="submit" className="bg-black text-white px-4 py-2 rounded disabled:opacity-50" disabled={submit.isPending}>
            {submit.isPending ? "Submitting…" : "Submit"}
          </button>
        </form>
        <div className="text-xs text-gray-600">Or email support@texapay.com</div>
      </div>
    </RequireAuth>
  );
}
