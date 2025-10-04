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

  const initials = React.useMemo(() => {
    const n = (data?.name || '').trim();
    if (!n) return 'U';
    const parts = n.split(' ').filter(Boolean);
    return (parts[0]?.[0] || 'U').toUpperCase() + (parts[1]?.[0] || '').toUpperCase();
  }, [data?.name]);

  return (
    <RequireAuth>
      <div className="min-h-dvh py-6 space-y-5">
        <PageHeader title="Me">
          <button className="border rounded px-3 py-1" onClick={() => refetch()} disabled={isFetching}>
            {isFetching ? "Refreshing..." : "Refresh"}
          </button>
        </PageHeader>

        {isLoading && (
          <div className="space-y-3"><CardSkeleton lines={3} /></div>
        )}
        {error && (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">
            {(error as any)?.response?.data?.message || (error as Error).message}
          </div>
        )}

        {data && (
          <div className="space-y-5">
            {/* Header card */}
            <section className="border rounded-xl p-4 flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-lg font-semibold">{initials}</div>
                <div>
                  <div className="font-medium">{data.name}</div>
                  <div className="text-sm text-gray-600">{data.phone}</div>
                </div>
              </div>
              <Link href="/profile/personal-info" aria-label="Settings" className="text-sm underline">Settings</Link>
            </section>

            {/* Transfers group */}
            <section className="border rounded-xl overflow-hidden">
              <div className="px-4 py-2 text-xs text-gray-500">Transfers</div>
              <div className="divide-y">
                <Link href="/transfers" className="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                  <div>
                    <div className="font-medium">Transaction History</div>
                  </div>
                  <span aria-hidden>›</span>
                </Link>
                <Link href="/profile/limits" className="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                  <div>
                    <div className="font-medium">Limits</div>
                  </div>
                  <span aria-hidden>›</span>
                </Link>
              </div>
            </section>

            {/* Security & Privacy group */}
            <section className="border rounded-xl overflow-hidden">
              <div className="px-4 py-2 text-xs text-gray-500">Security & Privacy</div>
              <div className="divide-y">
                <Link href="/profile/security" className="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                  <div className="font-medium">Security Center</div>
                  <span aria-hidden>›</span>
                </Link>
                <Link href="/kyc" className="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                  <div className="font-medium">KYC / Identity</div>
                  <span aria-hidden>›</span>
                </Link>
                <Link href="/settings/devices" className="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                  <div className="font-medium">Devices</div>
                  <span aria-hidden>›</span>
                </Link>
              </div>
            </section>

            {/* Support group */}
            <section className="border rounded-xl overflow-hidden">
              <div className="px-4 py-2 text-xs text-gray-500">Support</div>
              <div className="divide-y">
                <Link href="/support" className="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                  <div className="font-medium">Help & FAQ</div>
                  <span aria-hidden>›</span>
                </Link>
                <Link href="/support/contact" className="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                  <div className="font-medium">Contact Support</div>
                  <span aria-hidden>›</span>
                </Link>
                <Link href="/support/tickets" className="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                  <div className="font-medium">Support Tickets</div>
                  <span aria-hidden>›</span>
                </Link>
              </div>
            </section>

            {/* App & Legal group */}
            <section className="border rounded-xl overflow-hidden">
              <div className="px-4 py-2 text-xs text-gray-500">App & Legal</div>
              <div className="divide-y">
                <Link href="/notifications/preferences" className="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                  <div className="font-medium">Notification Preferences</div>
                  <span aria-hidden>›</span>
                </Link>
                <Link href="/policies" className="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                  <div className="font-medium">Policies</div>
                  <span aria-hidden>›</span>
                </Link>
              </div>
            </section>

            {/* Danger zone minimal link retained if policy allows */}
            <section>
              <button
                className="text-sm text-red-600 underline"
                onClick={async () => {
                  if (!confirm('Are you sure you want to delete your account? This cannot be undone.')) return;
                  try {
                    await http.post('/api/mobile/account/delete');
                    try { sessionStorage.removeItem('access_token'); } catch{}
                    window.location.href = '/auth/login';
                  } catch (e: any) {
                    alert(e?.response?.data?.message || e.message || 'Failed to delete account');
                  }
                }}
              >
                Delete my account
              </button>
            </section>
          </div>
        )}
      </div>
    </RequireAuth>
  );
}
