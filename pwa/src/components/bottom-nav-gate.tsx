"use client";
import React from "react";
import { usePathname } from "next/navigation";
import BottomNav from "@/components/bottom-nav";
import { getAccessToken } from "@/lib/auth";

export default function BottomNavGate() {
  const path = usePathname();
  const [authed, setAuthed] = React.useState(false);

  React.useEffect(() => {
    // Only in browser; simple check for token
    setAuthed(!!getAccessToken());
  }, [path]);

  // Hide on auth routes and when not authenticated
  const isAuthRoute = path?.startsWith("/auth");
  if (isAuthRoute || !authed) return null;
  return <BottomNav />;
}
