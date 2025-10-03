"use client";
import React from "react";
import { useQuery } from "@tanstack/react-query";
import Link from "next/link";
import http from "@/lib/api";
import RequireAuth from "@/components/guards/require-auth";
import PageHeader from "@/components/ui/page-header";
import { CardSkeleton } from "@/components/ui/skeleton";

type ProfileRes = {
  id: number;
  name: string;
  phone: string;
  email?: string | null;
  kyc?: { status: string; level: number };
};

export default function ProfilePage() {
  const { data, isLoading, error, refetch, isFetching } = useQuery<ProfileRes>({
    queryKey: ["profile"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/profile");
      return res.data;
    },
    staleTime: 60_000,
  });

  return (
    <RequireAuth>
    <div className="min-h-dvh p-6 max-w-xl mx-auto space-y-4">
      <PageHeader title="Profile">
        <button className="border rounded px-3 py-1" onClick={() => refetch()} disabled={isFetching}>
          {isFetching ? "Refreshing..." : "Refresh"}
        </button>
      </PageHeader>

      {isLoading && (
        <div className="space-y-3">
          <CardSkeleton lines={3} />
        </div>
      )}
      {error && (
        <div className="text-sm text-red-600 border border-red-200 rounded p-2">
          {(error as any)?.response?.data?.message || (error as Error).message}
        </div>
      )}

      {data && (
        <div className="border rounded p-4 space-y-2 text-sm">
          <div><span className="text-gray-600">Name:</span> {data.name}</div>
          <div><span className="text-gray-600">Phone:</span> {data.phone}</div>
          {data.email ? <div><span className="text-gray-600">Email:</span> {data.email}</div> : null}
          {data.kyc ? (
            <div><span className="text-gray-600">KYC:</span> {data.kyc.status} (Level {data.kyc.level})</div>
          ) : null}
          <div className="pt-2">
            <Link className="underline" href="/profile/personal-info">Personal info</Link>
            <span className="mx-2 text-gray-400">•</span>
            <Link className="underline" href="/profile/security">Security</Link>
            <span className="mx-2 text-gray-400">•</span>
            <Link className="underline" href="/profile/limits">Limits</Link>
          </div>
          <div className="pt-2">
            <button
              className="text-sm text-red-600 underline"
              onClick={async () => {
                if (!confirm('Are you sure you want to delete your account? This cannot be undone.')) return;
                try {
                  await http.post('/api/mobile/account/delete');
                  // Best-effort sign-out: clear token and redirect to login
                  try { sessionStorage.removeItem('access_token'); } catch{}
                  window.location.href = '/auth/login';
                } catch (e: any) {
                  alert(e?.response?.data?.message || e.message || 'Failed to delete account');
                }
              }}
            >
              Delete my account
            </button>
          </div>
        </div>
      )}
    </div>
    </RequireAuth>
  );
}
