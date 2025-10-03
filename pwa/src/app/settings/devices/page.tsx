"use client";
import React from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import http from "@/lib/api";
import RequireAuth from "@/components/guards/require-auth";
import { registerServiceWorker, subscribePush, unsubscribePush } from "@/lib/push";

type Device = {
  id?: number;
  device_token: string;
  platform: string; // 'web' | 'ios' | 'android'
  device_id?: string | null;
  app_version?: string | null;
  os_version?: string | null;
  active?: boolean;
};

type DevicesRes = { success?: boolean; devices: Device[] };

type RegisterReq = {
  device_token: string;
  platform: string;
  device_id?: string;
  app_version?: string;
  os_version?: string;
};

type UnregisterReq = { device_token: string };

type TestPushReq = { device_token?: string };

export default function DevicesPage() {
  const { data, isLoading, error, refetch, isFetching } = useQuery<DevicesRes>({
    queryKey: ["devices"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/devices");
      return res.data;
    },
    staleTime: 30_000,
  });

  const register = useMutation({
    mutationFn: async (vars: RegisterReq) => {
      const res = await http.post("/api/mobile/devices/register", vars);
      return res.data;
    },
    onSuccess: () => refetch(),
  });

  const unregister = useMutation({
    mutationFn: async (vars: UnregisterReq) => {
      const res = await http.delete("/api/mobile/devices/unregister", { data: vars });
      return res.data;
    },
    onSuccess: () => refetch(),
  });

  const testPush = useMutation({
    mutationFn: async (vars: TestPushReq) => {
      const res = await http.post("/api/mobile/devices/test-push", vars);
      return res.data;
    },
  });

  const [token, setToken] = React.useState("");
  const [platform, setPlatform] = React.useState("web");
  const [wpError, setWpError] = React.useState<string | null>(null);
  const [wpBusy, setWpBusy] = React.useState(false);

  async function handleEnableWebPush() {
    try {
      setWpError(null);
      setWpBusy(true);
      const vapid = process.env.NEXT_PUBLIC_VAPID_PUBLIC_KEY as string | undefined;
      if (!vapid) throw new Error("Missing NEXT_PUBLIC_VAPID_PUBLIC_KEY");
      // Ensure SW (in dev) and subscribe
      await registerServiceWorker();
      const sub = await subscribePush(vapid);
      const key = (b64: ArrayBuffer) => btoa(String.fromCharCode(...new Uint8Array(b64))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
      const p256dh = key(sub.getKey('p256dh')!);
      const auth = key(sub.getKey('auth')!);
      await http.post('/api/mobile/devices/register', {
        platform: 'web',
        endpoint: sub.endpoint,
        p256dh,
        auth,
      });
      await refetch();
    } catch (e: any) {
      setWpError(e?.response?.data?.message || e.message);
    } finally {
      setWpBusy(false);
    }
  }

  async function handleDisableWebPush() {
    try {
      setWpError(null);
      setWpBusy(true);
      // Try to get subscription to get endpoint (optional on backend)
      let endpoint: string | undefined;
      try {
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        endpoint = sub?.endpoint;
      } catch {}
      await unsubscribePush();
      // Inform backend to unregister by endpoint if supported
      await http.delete('/api/mobile/devices/unregister', { data: { endpoint } }).catch(() => undefined);
      await refetch();
    } catch (e: any) {
      setWpError(e?.response?.data?.message || e.message);
    } finally {
      setWpBusy(false);
    }
  }

  return (
    <RequireAuth>
    <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold">Devices</h1>
        <button className="border rounded px-3 py-1" onClick={() => refetch()} disabled={isFetching}>
          {isFetching ? "Refreshing..." : "Refresh"}
        </button>
      </div>

      <section className="space-y-3">
        <h2 className="text-lg font-semibold">Register device</h2>
        {wpError ? (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">{wpError}</div>
        ) : null}
        <div className="flex items-center gap-2">
          <button className="border rounded px-3 py-2" onClick={handleEnableWebPush} disabled={wpBusy}>
            {wpBusy ? 'Working...' : 'Enable web push'}
          </button>
          <button className="border rounded px-3 py-2" onClick={handleDisableWebPush} disabled={wpBusy}>
            {wpBusy ? 'Working...' : 'Disable web push'}
          </button>
        </div>
        <form
          className="grid gap-3 sm:grid-cols-2"
          onSubmit={(e) => {
            e.preventDefault();
            register.mutate({ device_token: token, platform });
          }}
        >
          <div className="sm:col-span-2">
            <label className="block text-sm mb-1">Device token</label>
            <input
              className="w-full border rounded px-3 py-2"
              value={token}
              onChange={(e) => setToken(e.target.value)}
              placeholder="paste your web push token"
              required
            />
          </div>
          <div>
            <label className="block text-sm mb-1">Platform</label>
            <select className="w-full border rounded px-3 py-2" value={platform} onChange={(e) => setPlatform(e.target.value)}>
              <option value="web">web</option>
              <option value="ios">ios</option>
              <option value="android">android</option>
            </select>
          </div>
          <div className="sm:col-span-2">
            <button className="bg-black text-white px-4 py-2 rounded" disabled={register.isPending}>
              {register.isPending ? "Registering..." : "Register"}
            </button>
          </div>
        </form>
      </section>

      <section className="space-y-3">
        <h2 className="text-lg font-semibold">Your devices</h2>
        {isLoading && <p>Loading...</p>}
        {error && (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">
            {(error as any)?.response?.data?.message || (error as Error).message}
          </div>
        )}
        <div className="border rounded divide-y">
          {data?.devices?.length ? (
            data.devices.map((d, idx) => (
              <div key={`${d.device_token}-${idx}`} className="p-3 text-sm flex items-start justify-between gap-3">
                <div>
                  <div className="font-medium">{d.platform} {d.active === false ? "(inactive)" : ""}</div>
                  <div className="text-xs text-gray-600 break-all">{d.device_token}</div>
                </div>
                <div className="flex items-center gap-2">
                  <button className="text-xs underline" onClick={() => testPush.mutate({ device_token: d.device_token })} disabled={testPush.isPending}>
                    Test push
                  </button>
                  <button className="text-xs underline" onClick={() => unregister.mutate({ device_token: d.device_token })} disabled={unregister.isPending}>
                    Unregister
                  </button>
                </div>
              </div>
            ))
          ) : (
            <div className="p-4 text-sm text-gray-600">No devices.</div>
          )}
        </div>
      </section>
    </div>
    </RequireAuth>
  );
}
