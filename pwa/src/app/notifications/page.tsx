"use client";
import React from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
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

type IndexRes = {
  notifications: Notification[];
  pagination: { current_page: number; last_page: number; per_page: number; total: number };
  unread_count: number;
};

export default function NotificationsPage() {
  const { data, isLoading, error, refetch, isFetching } = useQuery<IndexRes>({
    queryKey: ["notifications"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/notifications");
      return res.data;
    },
    staleTime: 30_000,
  });

  const markOne = useMutation({
    mutationFn: async (id: number) => {
      const res = await http.put(`/api/mobile/notifications/${id}/read`);
      return res.data;
    },
    onSuccess: () => refetch(),
  });

  const markAll = useMutation({
    mutationFn: async () => {
      const res = await http.put(`/api/mobile/notifications/read-all`);
      return res.data;
    },
    onSuccess: () => refetch(),
  });

  return (
    <RequireAuth>
    <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-4">
      <PageHeader title="Notifications">
        <button className="border rounded px-3 py-1" onClick={() => refetch()} disabled={isFetching}>
          {isFetching ? "Refreshing..." : "Refresh"}
        </button>
        <button className="border rounded px-3 py-1" onClick={() => markAll.mutate()} disabled={markAll.isPending}>
          {markAll.isPending ? "Marking..." : "Mark all read"}
        </button>
      </PageHeader>

      {isLoading && (
        <div className="space-y-3">
          <CardSkeleton lines={2} />
          <CardSkeleton lines={2} />
          <CardSkeleton lines={2} />
        </div>
      )}
      {error && (
        <div className="text-sm text-red-600 border border-red-200 rounded p-2">
          {(error as any)?.response?.data?.message || (error as Error).message}
        </div>
      )}

      <div className="border rounded divide-y">
        {data?.notifications?.length ? (
          data.notifications.map((n) => (
            <div key={n.id} className="p-3 text-sm flex items-start justify-between gap-3">
              <div>
                <div className="font-medium">{n.type || "Notification"}</div>
                <div className="text-xs text-gray-600">{new Date(n.created_at || Date.now()).toLocaleString()}</div>
              </div>
              <div className="flex items-center gap-2">
                {n.read_at ? (
                  <span className="text-xs text-gray-500">Read</span>
                ) : (
                  <button className="text-xs underline" onClick={() => markOne.mutate(n.id)} disabled={markOne.isPending}>
                    Mark as read
                  </button>
                )}
              </div>
            </div>
          ))
        ) : (
          <div className="p-4 text-sm text-gray-600">No notifications.</div>
        )}
      </div>
    </div>
    </RequireAuth>
  );
}
