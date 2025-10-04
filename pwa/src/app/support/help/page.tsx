"use client";
import React from "react";
import { useQuery } from "@tanstack/react-query";
import RequireAuth from "@/components/guards/require-auth";
import http from "@/lib/api";
import PageHeader from "@/components/ui/page-header";
import { CardSkeleton } from "@/components/ui/skeleton";

// Flexible types to cope with varying backend shapes
type HelpArticle = { id?: number|string; title?: string; body?: string };
type HelpGroup = { id?: number|string; title?: string; articles?: HelpArticle[] };

type HelpResponse = {
  groups?: HelpGroup[];
  articles?: HelpArticle[];
  success?: boolean;
  [k: string]: any;
};

export default function SupportHelpPage() {
  const q = useQuery<HelpResponse>({
    queryKey: ["support-help"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/support/help");
      return res.data as any;
    },
    staleTime: 5 * 60_000,
  });

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-4">
        <PageHeader title="Help" />

        {q.isLoading ? (
          <div className="space-y-2">
            <CardSkeleton lines={3} />
            <CardSkeleton lines={4} />
          </div>
        ) : q.error ? (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">
            {(q.error as any)?.response?.data?.message || (q.error as Error).message}
          </div>
        ) : (
          <div className="space-y-4">
            {/* Render groups if present */}
            {q.data?.groups?.length ? (
              q.data.groups.map((g, i) => (
                <div key={String(g.id ?? i)} className="border rounded p-3">
                  <div className="font-medium mb-2">{g.title || "Help group"}</div>
                  <div className="space-y-2">
                    {(g.articles || []).map((a, j) => (
                      <details key={String(a.id ?? j)} className="rounded">
                        <summary className="cursor-pointer text-sm font-medium">{a.title || "Article"}</summary>
                        <div className="mt-2 text-sm text-gray-700 whitespace-pre-wrap">{a.body || ""}</div>
                      </details>
                    ))}
                  </div>
                </div>
              ))
            ) : null}

            {/* Fallback: articles at root */}
            {q.data?.articles?.length ? (
              <div className="border rounded p-3">
                <div className="font-medium mb-2">Articles</div>
                <div className="space-y-2">
                  {q.data.articles.map((a, j) => (
                    <details key={String(a.id ?? j)} className="rounded">
                      <summary className="cursor-pointer text-sm font-medium">{a.title || "Article"}</summary>
                      <div className="mt-2 text-sm text-gray-700 whitespace-pre-wrap">{a.body || ""}</div>
                    </details>
                  ))}
                </div>
              </div>
            ) : null}

            {!q.data?.groups?.length && !q.data?.articles?.length ? (
              <div className="text-sm text-gray-600 border rounded p-3">No help topics found.</div>
            ) : null}
          </div>
        )}
      </div>
    </RequireAuth>
  );
}
