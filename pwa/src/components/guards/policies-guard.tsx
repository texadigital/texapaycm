"use client";
import React from "react";
import { usePathname } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { getAccessToken } from "@/lib/auth";
import http from "@/lib/api";

export default function PoliciesGuard({ children }: { children: React.ReactNode }) {
  const pathname = usePathname() || "/";
  const allowed = React.useMemo(() => {
    // Allow auth and policies routes without acceptance
    if (pathname.startsWith("/auth")) return true;
    if (pathname.startsWith("/policies/accept")) return true;
    if (pathname.startsWith("/policies/terms")) return true;
    if (pathname.startsWith("/policies/privacy")) return true;
    return false;
  }, [pathname]);

  const hasToken = !!getAccessToken();

  const status = useQuery<{ accepted: boolean; versions?: { terms?: string; privacy?: string } }>({
    queryKey: ["policies-status", hasToken],
    queryFn: async () => {
      const res = await http.get("/api/mobile/policies/status");
      return res.data as any;
    },
    enabled: hasToken && !allowed,
    staleTime: 5 * 60_000,
  });

  React.useEffect(() => {
    if (!hasToken) return; // RequireAuth handles auth
    if (allowed) return;
    if (status.data && status.data.accepted === false) {
      const next = typeof window !== 'undefined' ? encodeURIComponent(window.location.pathname + window.location.search) : "/";
      window.location.replace(`/policies/accept?next=${next}`);
    }
  }, [hasToken, allowed, status.data]);

  return <>{children}</>;
}
