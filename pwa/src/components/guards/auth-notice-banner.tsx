"use client";
import React from "react";
import { useAuthNotice } from "@/components/providers/auth-notice";

export default function AuthNoticeBanner() {
  const { unauthorized, setUnauthorized } = useAuthNotice();
  if (!unauthorized) return null;
  return (
    <div className="px-4 py-2 text-sm bg-red-50 text-red-700">
      <div className="max-w-4xl mx-auto flex items-center gap-3">
        <span className="font-medium">Session expired</span>
        <span className="hidden sm:inline">Please log in again to continue.</span>
        <div className="ml-auto flex items-center gap-2">
          <a className="underline" href="/auth/login">Login</a>
          <button className="underline" onClick={() => setUnauthorized(false)}>Dismiss</button>
        </div>
      </div>
    </div>
  );
}
