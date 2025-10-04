"use client";
import React from "react";
import { usePathname } from "next/navigation";
import { clearAccessToken, getAccessToken } from "@/lib/auth";
import { useQuery } from "@tanstack/react-query";
import http from "@/lib/api";

export default function TopBar() {
  const pathname = usePathname();
  const onAuthPage = pathname?.startsWith('/auth');
  // Start false on server to avoid hydration mismatch; update after mount
  const [hasToken, setHasToken] = React.useState<boolean>(false);

  React.useEffect(() => {
    const read = () => setHasToken(!!getAccessToken());
    read();
    const handler = () => read();
    window.addEventListener('storage', handler);
    window.addEventListener('auth:unauthorized', handler as any);
    window.addEventListener('notifications:refresh', handler as any);
    return () => {
      window.removeEventListener('storage', handler);
      window.removeEventListener('auth:unauthorized', handler as any);
      window.removeEventListener('notifications:refresh', handler as any);
    };
  }, []);

  const summary = useQuery<{ unread_count: number }>({
    queryKey: ['notifications-summary', hasToken],
    queryFn: async () => {
      const res = await http.get('/api/mobile/notifications/summary');
      return res.data as any;
    },
    enabled: !!hasToken && !onAuthPage,
    staleTime: 15_000,
    refetchOnWindowFocus: true,
  });

  return (
    <header className="px-4 py-2 border-b flex items-center justify-between">
      <a href="/" className="font-medium">TEXA</a>
      {!onAuthPage && hasToken ? (
        <div className="flex items-center gap-4">
          <a href="/notifications" className="relative text-sm underline">
            Notifications
            {(summary.data?.unread_count ?? 0) > 0 && (
              <span className="ml-1 inline-flex items-center justify-center h-5 min-w-5 px-1 rounded-full bg-red-600 text-white text-[10px]">
                {summary.data?.unread_count}
              </span>
            )}
          </a>
          <form
            onSubmit={(e) => {
              e.preventDefault();
              fetch('/api/mobile/auth/logout', { method: 'POST', credentials: 'include' })
                .catch(() => undefined)
                .finally(() => { clearAccessToken(); window.location.href = '/auth/login'; });
            }}
          >
            <button className="text-sm underline" type="submit">Logout</button>
          </form>
        </div>
      ) : null}
    </header>
  );
}
