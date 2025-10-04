"use client";
import React from "react";
import { usePathname, useRouter } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { getAccessToken } from "@/lib/auth";
import http from "@/lib/api";

export default function PoliciesBanner() {
  const pathname = usePathname() || "/";
  const router = useRouter();
  // Track token reactively (initially false to avoid SSR mismatch)
  const [hasToken, setHasToken] = React.useState<boolean>(false);
  React.useEffect(() => {
    const read = () => setHasToken(!!getAccessToken());
    read();
    const handler = () => read();
    window.addEventListener('storage', handler);
    window.addEventListener('auth:token', handler as any);
    window.addEventListener('auth:unauthorized', handler as any);
    return () => {
      window.removeEventListener('storage', handler);
      window.removeEventListener('auth:token', handler as any);
      window.removeEventListener('auth:unauthorized', handler as any);
    };
  }, []);

  const allowed = React.useMemo(() => {
    if (pathname.startsWith("/auth")) return true;
    if (pathname.startsWith("/policies/accept")) return true;
    if (pathname.startsWith("/policies/terms")) return true;
    if (pathname.startsWith("/policies/privacy")) return true;
    return false;
  }, [pathname]);

  const status = useQuery<{ accepted: boolean; versions?: { terms?: string; privacy?: string } }>({
    queryKey: ["policies-status", hasToken],
    queryFn: async () => {
      const res = await http.get("/api/mobile/policies/status");
      return res.data as any;
    },
    enabled: hasToken && !allowed,
    staleTime: 60_000,
    retry: false,
    throwOnError: false as any,
  });

  if (!hasToken || allowed) return null;
  if (!status.data || status.data.accepted !== false) return null;

  const next = typeof window !== 'undefined' ? encodeURIComponent(window.location.pathname + window.location.search) : encodeURIComponent(pathname);

  return (
    <div className="bg-yellow-50 border-b border-yellow-200 text-yellow-900">
      <div className="px-4 py-2 max-w-3xl mx-auto flex items-center justify-between gap-3 text-sm">
        <div className="leading-snug">
          You must review and accept our Terms & Privacy to continue using the app.
        </div>
        <button
          className="shrink-0 border border-yellow-400 text-yellow-900 bg-yellow-100 hover:bg-yellow-200 rounded px-3 py-1"
          onClick={() => router.push(`/policies/accept?next=${next}`)}
        >
          Review & accept
        </button>
      </div>
    </div>
  );
}
