"use client";
import React from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import http from "@/lib/api";
import PageHeader from "@/components/ui/page-header";
import Link from "next/link";
import RequireAuth from "@/components/guards/require-auth";

// Shape follows NotificationController@preferences contract
type PrefsRes = {
  preferences: Record<string, any>;
  global_settings?: Record<string, any>;
};

type UpdateReq = PrefsRes;

export default function NotificationPreferencesPage() {
  const { data, isLoading, error, refetch, isFetching } = useQuery<PrefsRes>({
    queryKey: ["notifications-preferences"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/notifications/preferences");
      return res.data;
    },
    staleTime: 60_000,
  });

  const [prefs, setPrefs] = React.useState<PrefsRes | null>(null);

  React.useEffect(() => {
    if (data) setPrefs(JSON.parse(JSON.stringify(data)));
  }, [data]);

  const update = useMutation({
    mutationFn: async () => {
      const res = await http.put("/api/mobile/notifications/preferences", prefs as UpdateReq);
      return res.data as any;
    },
    onSuccess: async () => {
      await refetch();
    },
  });

  function setPref(path: string, value: any) {
    setPrefs((prev) => {
      const next = JSON.parse(JSON.stringify(prev || { preferences: {}, global_settings: {} }));
      const segs = path.split(".");
      let obj: any = next;
      for (let i = 0; i < segs.length - 1; i++) {
        const k = segs[i];
        if (!(k in obj)) obj[k] = {};
        obj = obj[k];
      }
      obj[segs[segs.length - 1]] = value;
      return next;
    });
  }

  const prefItems = React.useMemo(() => {
    const p = prefs?.preferences || {};
    const keys = Object.keys(p);
    return keys.map((k) => ({ key: k, value: p[k] }));
  }, [prefs]);

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-xl mx-auto space-y-4">
        <PageHeader title="Notification preferences">
          <Link className="border rounded px-3 py-1" href="/notifications">Back</Link>
          <button className="border rounded px-3 py-1" onClick={() => refetch()} disabled={isFetching}>
            {isFetching ? "Refreshing..." : "Refresh"}
          </button>
          <button
            className="border rounded px-3 py-1 disabled:opacity-50"
            onClick={() => update.mutate()}
            disabled={update.isPending || !prefs}
          >
            {update.isPending ? "Saving..." : "Save"}
          </button>
        </PageHeader>

        {isLoading && <div className="text-sm text-gray-600">Loading...</div>}
        {error && (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">
            {(error as any)?.response?.data?.message || (error as Error).message}
          </div>
        )}

        {/* Simple generic editor based on returned keys */}
        <div className="space-y-3 text-sm">
          {prefItems.length === 0 ? (
            <div className="text-gray-600">No preferences available.</div>
          ) : (
            prefItems.map((it) => (
              <div key={it.key} className="flex items-center justify-between gap-3 border rounded p-2">
                <div>
                  <div className="font-medium">{it.key}</div>
                  <div className="text-xs text-gray-600">Toggle to enable/disable</div>
                </div>
                <label className="inline-flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={!!it.value?.enabled}
                    onChange={(e) => setPref(`preferences.${it.key}.enabled`, e.target.checked)}
                  />
                  <span className="text-xs">Enabled</span>
                </label>
              </div>
            ))
          )}
        </div>
      </div>
    </RequireAuth>
  );
}
