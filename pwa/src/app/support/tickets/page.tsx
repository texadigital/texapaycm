"use client";
import React from "react";
import { useQuery } from "@tanstack/react-query";
import RequireAuth from "@/components/guards/require-auth";
import http from "@/lib/api";
import PageHeader from "@/components/ui/page-header";
import { CardSkeleton } from "@/components/ui/skeleton";

type Ticket = { id: number|string; subject?: string; status?: string; updatedAt?: string };

type ListRes = { data?: Ticket[] } | { tickets?: Ticket[] } | any;

export default function TicketsListPage() {
  const q = useQuery<ListRes>({
    queryKey: ["tickets"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/support/tickets");
      return res.data as any;
    },
  });

  const items: Ticket[] = q.data?.data || q.data?.tickets || [];

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-4">
        <PageHeader title="Support tickets" />

        {q.isLoading ? (
          <div className="space-y-2"><CardSkeleton lines={3} /><CardSkeleton lines={3} /></div>
        ) : items.length === 0 ? (
          <div className="text-sm text-gray-600 border rounded p-3">You have no tickets.</div>
        ) : (
          <div className="border rounded divide-y">
            {items.map((t) => (
              <a key={String(t.id)} href={`/support/tickets/${t.id}`} className="block p-3 text-sm hover:bg-gray-50">
                <div className="flex items-center justify-between">
                  <div className="font-medium">{t.subject || `Ticket ${t.id}`}</div>
                  <div className="text-xs text-gray-600">{t.updatedAt ? new Date(t.updatedAt).toLocaleString() : null}</div>
                </div>
                <div className="text-xs text-gray-600 capitalize">{t.status || "open"}</div>
              </a>
            ))}
          </div>
        )}
      </div>
    </RequireAuth>
  );
}
