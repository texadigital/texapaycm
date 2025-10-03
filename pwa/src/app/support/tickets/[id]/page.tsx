"use client";
import React from "react";
import { useParams } from "next/navigation";
import { useMutation, useQuery } from "@tanstack/react-query";
import RequireAuth from "@/components/guards/require-auth";
import http from "@/lib/api";
import PageHeader from "@/components/ui/page-header";
import { CardSkeleton } from "@/components/ui/skeleton";

type TicketMessage = { id: string|number; body?: string; author?: string; createdAt?: string };

type Ticket = { id: string|number; subject?: string; status?: string; messages?: TicketMessage[] };

type ShowRes = { ticket?: Ticket } | any;

export default function TicketDetailPage() {
  const params = useParams<{ id: string }>();
  const id = params?.id;

  const q = useQuery<ShowRes>({
    queryKey: ["ticket", id],
    queryFn: async () => {
      const res = await http.get(`/api/mobile/support/tickets/${id}`);
      return res.data as any;
    },
    enabled: !!id,
  });

  const [reply, setReply] = React.useState("");
  const [topInfo, setTopInfo] = React.useState<string | null>(null);
  const [topErr, setTopErr] = React.useState<string | null>(null);

  const send = useMutation({
    mutationFn: async () => {
      setTopInfo(null); setTopErr(null);
      const res = await http.post(`/api/mobile/support/tickets/${id}/reply`, { body: reply });
      return res.data as any;
    },
    onSuccess: async () => {
      setTopInfo("Reply sent");
      setReply("");
      await q.refetch();
    },
    onError: (e: any) => setTopErr(e?.response?.data?.message || e.message || "Failed"),
  });

  const ticket: Ticket | undefined = q.data?.ticket || q.data?.data || q.data;

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-4">
        <PageHeader title={`Ticket ${id}`} />

        {q.isLoading ? (
          <div className="space-y-2"><CardSkeleton lines={3} /><CardSkeleton lines={4} /></div>
        ) : q.error ? (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">{(q.error as any)?.response?.data?.message || (q.error as Error).message}</div>
        ) : !ticket ? (
          <div className="text-sm text-gray-600 border rounded p-3">Ticket not found.</div>
        ) : (
          <div className="space-y-4">
            <div className="border rounded p-3 text-sm">
              <div className="font-medium">{ticket.subject || `Ticket ${ticket.id}`}</div>
              <div className="text-xs text-gray-600 capitalize">{ticket.status || "open"}</div>
            </div>
            <div className="border rounded divide-y">
              {(ticket.messages || []).length === 0 ? (
                <div className="p-3 text-sm text-gray-600">No messages yet.</div>
              ) : (
                (ticket.messages || []).map((m) => (
                  <div key={String(m.id)} className="p-3 text-sm">
                    <div className="flex items-center justify-between">
                      <div className="font-medium">{m.author || "You"}</div>
                      <div className="text-xs text-gray-600">{m.createdAt ? new Date(m.createdAt).toLocaleString() : null}</div>
                    </div>
                    <div className="mt-1 whitespace-pre-wrap">{m.body || ""}</div>
                  </div>
                ))
              )}
            </div>

            {topInfo ? <div className="text-sm text-green-700 border border-green-200 rounded p-2">{topInfo}</div> : null}
            {topErr ? <div className="text-sm text-red-600 border border-red-200 rounded p-2">{topErr}</div> : null}

            <form className="space-y-2" onSubmit={(e) => { e.preventDefault(); send.mutate(); }}>
              <label className="block text-sm">Reply</label>
              <textarea className="w-full border rounded px-3 py-2 min-h-28" value={reply} onChange={(e) => setReply(e.target.value)} required />
              <button className="bg-black text-white px-4 py-2 rounded disabled:opacity-50" disabled={send.isPending}>
                {send.isPending ? "Sending…" : "Send reply"}
              </button>
            </form>
          </div>
        )}
      </div>
    </RequireAuth>
  );
}
