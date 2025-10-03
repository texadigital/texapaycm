"use client";
import React from "react";
import { clearAccessToken } from "@/lib/auth";

export default function AuthProvider({ children }: { children: React.ReactNode }) {
  React.useEffect(() => {
    function onUnauthorized() {
      try { clearAccessToken(); } catch {}
      if (typeof window !== 'undefined') {
        window.location.href = "/auth/login";
      }
    }
    if (typeof window !== 'undefined') {
      window.addEventListener('auth:unauthorized', onUnauthorized);
      return () => window.removeEventListener('auth:unauthorized', onUnauthorized);
    }
  }, []);

  return <>{children}</>;
}
