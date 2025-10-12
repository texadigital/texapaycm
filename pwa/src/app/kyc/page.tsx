"use client";
import React from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import http from "@/lib/api";
import RequireAuth from "@/components/guards/require-auth";

type KycStatusRes = { kyc_status?: string; kyc_level?: number; status?: string; level?: number };

type StartRes = any; // backend returns session payload

type WebTokenRes = { token?: string; expiresAt?: string } & Record<string, any>;

export default function KycPage() {
  const { data, isLoading, error, refetch, isFetching } = useQuery<KycStatusRes>({
    queryKey: ["kyc-status"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/kyc/status");
      return res.data;
    },
    staleTime: 30_000,
    gcTime: 5 * 60_000,
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
  });

  const start = useMutation({
    mutationFn: async () => {
      const res = await http.post("/api/mobile/kyc/smileid/start");
      return res.data as StartRes;
    },
  });

  const webToken = useMutation({
    mutationFn: async () => {
      const res = await http.post("/api/mobile/kyc/smileid/web-token");
      return res.data as WebTokenRes;
    },
    onSuccess: (data: any) => {
      // If backend returns a direct token+url, prefer that
      if (data?.url) {
        window.open(data.url, "_blank");
        return;
      }
      // Fallback: construct a Smile Web URL from the session payload
      const session = data?.session || data;
      if (session?.smile_client_id && session?.timestamp && session?.signature) {
        const params = new URLSearchParams({
          client_id: String(session.smile_client_id ?? ""),
          timestamp: String(session.timestamp ?? ""),
          signature: String(session.signature ?? ""),
          callback_url: String(session.callback_url ?? ""),
          user_id: String(session.partner_params?.user_id ?? ""),
          job_id: String(session.partner_params?.job_id ?? ""),
          job_type: String(session.partner_params?.job_type ?? "6"),
          country: String(session.country ?? "CM"),
          source_sdk: String(session.source_sdk ?? "web"),
          source_sdk_version: String(session.source_sdk_version ?? "1.0.0"),
        });
        const base = process.env.NEXT_PUBLIC_SMILE_WEB_BASE_URL || "https://web.smileidentity.com";
        const url = `${base}?${params.toString()}`;
        window.open(url, "_blank");
      }
    },
  });

  const status = data?.kyc_status || data?.status || "—";
  const level = data?.kyc_level ?? data?.level ?? "—";

  // Light polling while status is pending
  React.useEffect(() => {
    if (status === 'pending' || status === 'in_progress') {
      const t = setInterval(() => refetch(), 10_000);
      return () => clearInterval(t);
    }
  }, [status, refetch]);

  return (
    <RequireAuth>
    <div className="min-h-dvh p-6 max-w-xl mx-auto space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold">KYC</h1>
        <button className="border rounded px-3 py-1" onClick={() => refetch()} disabled={isFetching}>
          {isFetching ? "Refreshing..." : "Refresh"}
        </button>
      </div>

      {isLoading && <p>Loading...</p>}
      {error && (
        <div className="text-sm text-red-600 border border-red-200 rounded p-2">
          {(error as any)?.response?.data?.message || (error as Error).message}
        </div>
      )}

      <div className="border rounded p-4 text-sm space-y-1">
        <div><span className="text-gray-600">Status:</span> <span className="capitalize">{status}</span></div>
        <div><span className="text-gray-600">Level:</span> {level}</div>
      </div>

      <section className="space-y-3">
        <h2 className="text-lg font-semibold">Smile ID</h2>
        <div className="flex items-center gap-2">
          <button className="border rounded px-3 py-2" onClick={() => start.mutate()} disabled={start.isPending}>
            {start.isPending ? "Starting..." : "Start KYC"}
          </button>
          <button className="border rounded px-3 py-2" onClick={() => webToken.mutate()} disabled={webToken.isPending}>
            {webToken.isPending ? "Requesting..." : "Get web token"}
          </button>
        </div>
        {(start.isError || webToken.isError) && (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">
            {(start.error as any)?.response?.data?.message || (webToken.error as any)?.response?.data?.message || (start.error as any)?.message || (webToken.error as any)?.message}
          </div>
        )}
        {webToken.data && (
          <div className="border rounded p-3 text-sm space-y-1">
            {webToken.data.token ? (
              <div>Token: <span className="break-all font-mono">{webToken.data.token}</span></div>
            ) : (
              <pre className="text-xs whitespace-pre-wrap">{JSON.stringify(webToken.data, null, 2)}</pre>
            )}
            {webToken.data.expiresAt ? (
              <div>Expires: {new Date(webToken.data.expiresAt).toLocaleString()}</div>
            ) : null}
            {webToken.data?.url ? (
              <div className="pt-2">
                <a className="underline text-blue-600" href={webToken.data.url}>Open Smile ID</a>
              </div>
            ) : null}
          </div>
        )}
      </section>
    </div>
    </RequireAuth>
  );
}
