"use client";
import React from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import RequireAuth from "@/components/guards/require-auth";
import http from "@/lib/api";
import PageHeader from "@/components/ui/page-header";
import { CardSkeleton } from "@/components/ui/skeleton";

 type InfoRes = { name?: string; email?: string; phone?: string; notification_email?: string } & Record<string, any>;

export default function PersonalInfoPage() {
  const q = useQuery<InfoRes>({
    queryKey: ["profile-personal-info"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/profile/personal-info");
      return res.data as any;
    },
  });

  const [name, setName] = React.useState("");
  const [email, setEmail] = React.useState("");
  const [notificationEmail, setNotificationEmail] = React.useState("");
  const [topInfo, setTopInfo] = React.useState<string | null>(null);
  const [topErr, setTopErr] = React.useState<string | null>(null);

  React.useEffect(() => {
    if (!q.data) return;
    setName(q.data.name || "");
    setEmail(q.data.email || "");
    setNotificationEmail(q.data.notification_email || "");
  }, [q.data]);

  const save = useMutation({
    mutationFn: async () => {
      setTopInfo(null); setTopErr(null);
      const res = await http.post("/api/mobile/profile/personal-info", {
        name: name || null,
        email: email || null,
        notification_email: notificationEmail || null,
      });
      return res.data as any;
    },
    onSuccess: () => setTopInfo("Profile updated"),
    onError: (e: any) => setTopErr(e?.response?.data?.message || e.message || "Failed"),
  });

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-xl mx-auto space-y-4">
        <PageHeader title="Personal info" />
        {q.isLoading ? (
          <div className="space-y-2"><CardSkeleton lines={3} /><CardSkeleton lines={3} /></div>
        ) : q.error ? (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">{(q.error as any)?.response?.data?.message || (q.error as Error).message}</div>
        ) : (
          <form className="space-y-3" onSubmit={(e) => { e.preventDefault(); save.mutate(); }}>
            {topInfo ? <div className="text-sm text-green-700 border border-green-200 rounded p-2">{topInfo}</div> : null}
            {topErr ? <div className="text-sm text-red-600 border border-red-200 rounded p-2">{topErr}</div> : null}
            <div>
              <label className="block text-sm mb-1">Full name</label>
              <input className="w-full border rounded px-3 py-2" value={name} onChange={(e) => setName(e.target.value)} />
            </div>
            <div>
              <label className="block text-sm mb-1">Email</label>
              <input className="w-full border rounded px-3 py-2" type="email" value={email} onChange={(e) => setEmail(e.target.value)} />
            </div>
            <div>
              <label className="block text-sm mb-1">Notification email</label>
              <input className="w-full border rounded px-3 py-2" type="email" value={notificationEmail} onChange={(e) => setNotificationEmail(e.target.value)} />
            </div>
            <button className="bg-black text-white px-4 py-2 rounded disabled:opacity-50" disabled={save.isPending}>
              {save.isPending ? "Saving…" : "Save changes"}
            </button>
          </form>
        )}
      </div>
    </RequireAuth>
  );
}
