"use client";
import React from "react";
import { useQuery } from "@tanstack/react-query";
import RequireAuth from "@/components/guards/require-auth";
import http from "@/lib/api";
import PageHeader from "@/components/ui/page-header";
import { CardSkeleton } from "@/components/ui/skeleton";

type PoliciesRes = { title?: string; content?: string; sections?: Array<{ title?: string; body?: string }>; updated_at?: string } & Record<string, any>;

export default function PoliciesPage() {
  const q = useQuery<PoliciesRes>({
    queryKey: ["policies"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/policies");
      return res.data as any;
    },
    staleTime: 60 * 60_000,
  });

  const d = q.data;

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-4">
        <PageHeader title={d?.title || "Policies"} />
        {q.isLoading ? (
          <div className="space-y-2"><CardSkeleton lines={3} /><CardSkeleton lines={4} /></div>
        ) : q.error ? (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">{(q.error as any)?.response?.data?.message || (q.error as Error).message}</div>
        ) : d ? (
          <div className="space-y-4 text-sm">
            {d.updated_at ? <div className="text-xs text-gray-600">Last updated: {new Date(d.updated_at).toLocaleString()}</div> : null}
            {d.content ? (
              <div className="prose max-w-none whitespace-pre-wrap">{d.content}</div>
            ) : null}
            {Array.isArray(d.sections) && d.sections.length > 0 ? (
              <div className="space-y-3">
                {d.sections.map((s, i) => (
                  <div key={i} className="border rounded p-3">
                    <div className="font-medium mb-1">{s.title || `Section ${i+1}`}</div>
                    <div className="text-gray-700 whitespace-pre-wrap">{s.body || ""}</div>
                  </div>
                ))}
              </div>
            ) : null}
            {!d.content && (!d.sections || d.sections.length === 0) ? (
              <div className="text-gray-600">No policy content found.</div>
            ) : null}
          </div>
        ) : null}
      </div>
    </RequireAuth>
  );
}
