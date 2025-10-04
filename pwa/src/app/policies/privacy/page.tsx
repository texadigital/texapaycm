"use client";
import React from "react";
import { useQuery } from "@tanstack/react-query";
import RequireAuth from "@/components/guards/require-auth";
import PageHeader from "@/components/ui/page-header";
import http from "@/lib/api";

export default function PrivacyPage() {
  const q = useQuery<{ privacy?: { url?: string; version?: string } }>({
    queryKey: ["policies-index"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/policies");
      return res.data as any;
    },
  });

  const url = q.data?.privacy?.url || "/policies#privacy";

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-5xl mx-auto space-y-4">
        <PageHeader title="Privacy Policy" />
        <div className="text-xs text-gray-600">Version: {q.data?.privacy?.version || "—"}</div>
        <div className="border rounded overflow-hidden" style={{ height: 800 }}>
          <iframe src={url} title="Privacy" className="w-full h-full" />
        </div>
      </div>
    </RequireAuth>
  );
}
