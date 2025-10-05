"use client";
import React from "react";
import { useInfiniteQuery, useMutation } from "@tanstack/react-query";
import http from "@/lib/api";
import PageHeader from "@/components/ui/page-header";
import { CardSkeleton } from "@/components/ui/skeleton";
import RequireAuth from "@/components/guards/require-auth";

type Notification = {
  id: number;
  type?: string;
  data?: any;
  read_at?: string | null;
  created_at?: string;
};

type PageRes = {
  notifications: Notification[];
  pagination: { current_page: number; last_page: number; per_page: number; total: number };
  unread_count: number;
};

function titleFor(n: Notification) {
  const t = n.type || "notification";
  if (t.startsWith("transfer.payin")) return "Transfer pay-in";
  if (t.startsWith("transfer.payout")) return "Transfer payout";
  if (t.startsWith("auth.login")) return "Login";
  if (t.includes("password")) return "Password";
  return t.replace(/\./g, " ");
}
function toneFor(n: Notification) {
  const t = n.type || "";
  if (t.endsWith("success")) return { dot: "bg-emerald-500", pill: "bg-emerald-50 text-emerald-700" };
  if (t.endsWith("failed") || t.endsWith("error")) return { dot: "bg-rose-500", pill: "bg-rose-50 text-rose-700" };
  if (t.endsWith("pending")) return { dot: "bg-amber-500", pill: "bg-amber-50 text-amber-700" };
  return { dot: "bg-gray-400", pill: "bg-gray-50 text-gray-700" };
}

export default function NotificationsPage() {
  const q = useInfiniteQuery<PageRes>({
    queryKey: ["notifications"],
    queryFn: async ({ pageParam = 1 }) => {
      const res = await http.get("/api/mobile/notifications", { params: { page: pageParam } });
      return res.data as PageRes;
    },
    initialPageParam: 1,
    getNextPageParam: (last) => {
      const cur = last?.pagination?.current_page || 1;
      const lastPage = last?.pagination?.last_page || 1;
      return cur < lastPage ? cur + 1 : undefined;
    },
    staleTime: 30_000,
    gcTime: 5 * 60_000,
    refetchOnWindowFocus: false,
    refetchOnReconnect: false,
    placeholderData: (prev) => prev as any,
  });

  const sentinelRef = React.useRef<HTMLDivElement | null>(null);
  React.useEffect(() => {
    if (!sentinelRef.current) return;
    const io = new IntersectionObserver((entries) => {
      const [e] = entries;
      if (e.isIntersecting && q.hasNextPage && !q.isFetchingNextPage) q.fetchNextPage();
    }, { rootMargin: '200px' });
    io.observe(sentinelRef.current);
    return () => io.disconnect();
  }, [q.hasNextPage, q.isFetchingNextPage]);

  const markOne = useMutation({
    mutationFn: async (id: number) => {
      await http.put(`/api/mobile/notifications/${id}/read`);
      return id;
    },
    onSuccess: (id) => {
      // Optimistically mark read in cache
      const pages = q.data?.pages || [];
      for (const p of pages) {
        for (const n of p.notifications) if (n.id === id) (n as any).read_at = new Date().toISOString();
      }
      try { window.dispatchEvent(new CustomEvent('notifications:refresh')); } catch {}
    },
  });

  const markAll = useMutation({
    mutationFn: async () => { await http.put(`/api/mobile/notifications/read-all`); },
    onSuccess: () => {
      const pages = q.data?.pages || [];
      for (const p of pages) for (const n of p.notifications) (n as any).read_at = new Date().toISOString();
      try { window.dispatchEvent(new CustomEvent('notifications:refresh')); } catch {}
    },
  });

  // Group by calendar day label
  const groups = React.useMemo(() => {
    const pages = q.data?.pages || [];
    const all = pages.flatMap(p => p.notifications || []);
    const by: Record<string, Notification[]> = {};
    const fmt = (d: Date) => {
      const today = new Date();
      const yday = new Date(); yday.setDate(today.getDate()-1);
      const ds = d.toDateString(), ts = today.toDateString(), ys = yday.toDateString();
      if (ds === ts) return 'Today';
      if (ds === ys) return 'Yesterday';
      return d.toLocaleDateString();
    };
    for (const n of all) {
      const d = new Date(n.created_at || Date.now());
      const k = fmt(d);
      if (!by[k]) by[k] = [];
      by[k].push(n);
    }
    return Object.entries(by).map(([k, items]) => ({ key: k, items: items.sort((a,b)=> (new Date(b.created_at||0).getTime() - new Date(a.created_at||0).getTime())) }));
  }, [q.data]);

  return (
    <RequireAuth>
    <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-4">
      <PageHeader title="Notifications">
        <button className="border rounded px-3 py-1" onClick={() => q.refetch()} disabled={q.isFetching}>
          {q.isFetching ? "Refreshing..." : "Refresh"}
        </button>
        <button className="border rounded px-3 py-1" onClick={() => markAll.mutate()} disabled={markAll.isPending}>
          {markAll.isPending ? "Marking..." : "Mark all read"}
        </button>
      </PageHeader>

      {q.isLoading && (
        <div className="space-y-3">
          <CardSkeleton lines={2} />
          <CardSkeleton lines={2} />
          <CardSkeleton lines={2} />
        </div>
      )}
      {q.isError && (
        <div className="text-sm text-red-600 border border-red-200 rounded p-2">
          Failed to load notifications. <button className="underline" onClick={()=>q.refetch()}>Try again</button>
        </div>
      )}

      {groups.map((g) => (
        <section key={g.key} className="border rounded">
          <div className="p-3 text-xs text-gray-600 border-b">{g.key}</div>
          <div className="divide-y">
            {g.items.map((n) => {
              const tone = toneFor(n);
              const created = new Date(n.created_at || Date.now()).toLocaleString();
              return (
                <div key={n.id} className="p-3 text-sm flex items-start justify-between gap-3">
                  <div className="flex items-start gap-3">
                    <span className={`mt-1 inline-block w-2 h-2 rounded-full ${tone.dot}`} />
                    <div>
                      <div className="font-medium">{titleFor(n)}</div>
                      <div className="text-xs text-gray-600">{created}</div>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    {n.read_at ? (
                      <span className={`text-[11px] px-2 py-0.5 rounded ${tone.pill}`}>Read</span>
                    ) : (
                      <button className="text-xs underline" onClick={() => markOne.mutate(n.id)} disabled={markOne.isPending}>
                        Mark as read
                      </button>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </section>
      ))}

      <div ref={sentinelRef} />
      {q.isFetchingNextPage && (
        <div className="text-xs text-gray-600 text-center">Loading more…</div>
      )}
    </div>
    </RequireAuth>
  );
}
