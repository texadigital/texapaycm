"use client";
import React from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import http from "@/lib/api";
import PageHeader from "@/components/ui/page-header";
import { CardSkeleton } from "@/components/ui/skeleton";

type SecurityRes = { pinEnabled?: boolean; twoFactorEnabled?: boolean; lastSecurityUpdate?: string };

type PinReq = { currentPin?: string; newPin: string };

type PasswordReq = { currentPassword: string; newPassword: string };

export default function SecurityPage() {
  const { data, isLoading, error } = useQuery<SecurityRes>({
    queryKey: ["security"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/profile/security");
      return res.data;
    },
    staleTime: 60_000,
    gcTime: 5 * 60_000,
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
  });

  const pin = useMutation({
    mutationFn: async (vars: PinReq) => {
      const res = await http.post("/api/mobile/profile/security/pin", vars);
      return res.data;
    },
  });

  const password = useMutation({
    mutationFn: async (vars: PasswordReq) => {
      const res = await http.post("/api/mobile/profile/security/password", vars);
      return res.data;
    },
  });

  const [currentPin, setCurrentPin] = React.useState("");
  const [newPin, setNewPin] = React.useState("");
  const [currentPassword, setCurrentPassword] = React.useState("");
  const [newPassword, setNewPassword] = React.useState("");
  const [topError, setTopError] = React.useState<string | null>(null);
  const [topSuccess, setTopSuccess] = React.useState<string | null>(null);

  const toggles = useMutation({
    mutationFn: async (vars: { pinEnabled?: boolean; twoFactorEnabled?: boolean }) => {
      const res = await http.post("/api/mobile/profile/security/toggles", vars);
      return res.data;
    },
  });

  return (
    <div className="min-h-dvh p-6 max-w-xl mx-auto space-y-6">
      <PageHeader title="Security" />
      {isLoading && (
        <div className="space-y-3">
          <CardSkeleton lines={3} />
          <CardSkeleton lines={4} />
        </div>
      )}
      {error && (
        <div className="text-sm text-red-600 border border-red-200 rounded p-2">
          {(error as any)?.response?.data?.message || (error as Error).message}
        </div>
      )}
      {topError ? (
        <div className="text-sm text-red-600 border border-red-200 rounded p-2">{topError}</div>
      ) : null}
      {topSuccess ? (
        <div className="text-sm text-green-700 border border-green-200 rounded p-2">{topSuccess}</div>
      ) : null}

      {/* Toggles */}
      <section className="space-y-3">
        <h2 className="text-lg font-semibold">Security toggles</h2>
        <div className="border rounded p-3 text-sm space-y-2">
          <label className="flex items-center gap-2">
            <input type="checkbox" defaultChecked={!!data?.pinEnabled} onChange={(e) => toggles.mutate({ pinEnabled: e.currentTarget.checked })} />
            <span>Enable PIN</span>
          </label>
          <label className="flex items-center gap-2">
            <input type="checkbox" defaultChecked={!!data?.twoFactorEnabled} onChange={(e) => toggles.mutate({ twoFactorEnabled: e.currentTarget.checked })} />
            <span>Enable 2FA</span>
          </label>
          {data?.lastSecurityUpdate ? (
            <div className="text-xs text-gray-600">Last update: {new Date(data.lastSecurityUpdate).toLocaleString()}</div>
          ) : null}
        </div>
      </section>

      {/* Update PIN */}
      <section className="space-y-3">
        <h2 className="text-lg font-semibold">Change PIN</h2>
        <form
          className="space-y-3"
          onSubmit={async (e) => {
            e.preventDefault();
            setTopError(null);
            setTopSuccess(null);
            try {
              await pin.mutateAsync({
                currentPin: currentPin || undefined,
                newPin,
              });
              setTopSuccess("PIN updated");
              setCurrentPin("");
              setNewPin("");
            } catch (e: any) {
              setTopError(e?.response?.data?.message || e.message);
            }
          }}
        >
          <div>
            <label className="block text-sm mb-1">Current PIN (optional)</label>
            <input
              className="w-full border rounded px-3 py-2"
              value={currentPin}
              onChange={(e) => setCurrentPin(e.target.value)}
              type="password"
              inputMode="numeric"
              pattern="[0-9]{4,6}"
            />
          </div>
          <div>
            <label className="block text-sm mb-1">New PIN</label>
            <input
              className="w-full border rounded px-3 py-2"
              value={newPin}
              onChange={(e) => setNewPin(e.target.value)}
              type="password"
              inputMode="numeric"
              pattern="[0-9]{4,6}"
              required
            />
          </div>
          <button className="bg-black text-white px-4 py-2 rounded" disabled={pin.isPending}>
            {pin.isPending ? "Updating..." : "Update PIN"}
          </button>
        </form>
      </section>

      {/* Update Password */}
      <section className="space-y-3">
        <h2 className="text-lg font-semibold">Change password</h2>
        <form
          className="space-y-3"
          onSubmit={async (e) => {
            e.preventDefault();
            setTopError(null);
            setTopSuccess(null);
            try {
              await password.mutateAsync({
                currentPassword,
                newPassword,
              });
              setTopSuccess("Password updated");
              setCurrentPassword("");
              setNewPassword("");
            } catch (e: any) {
              setTopError(e?.response?.data?.message || e.message);
            }
          }}
        >
          <div>
            <label className="block text-sm mb-1">Current password</label>
            <input
              className="w-full border rounded px-3 py-2"
              type="password"
              value={currentPassword}
              onChange={(e) => setCurrentPassword(e.target.value)}
              required
            />
          </div>
          <div>
            <label className="block text-sm mb-1">New password</label>
            <input
              className="w-full border rounded px-3 py-2"
              type="password"
              value={newPassword}
              onChange={(e) => setNewPassword(e.target.value)}
              minLength={6}
              required
            />
          </div>
          <button className="bg-black text-white px-4 py-2 rounded" disabled={password.isPending}>
            {password.isPending ? "Updating..." : "Update password"}
          </button>
        </form>
      </section>
    </div>
  );
}
