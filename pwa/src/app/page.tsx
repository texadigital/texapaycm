"use client";
import React from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { getAccessToken, refreshAccessToken, setAccessToken } from "@/lib/auth";

export default function Home() {
  const router = useRouter();
  const [checking, setChecking] = React.useState(true);

  React.useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const token = getAccessToken();
        if (token) {
          if (!cancelled) router.replace("/dashboard");
          return;
        }
        const refreshed = await refreshAccessToken();
        if (refreshed) {
          setAccessToken(refreshed);
          if (!cancelled) router.replace("/dashboard");
          return;
        }
      } catch {}
      if (!cancelled) setChecking(false);
    })();
    return () => {
      cancelled = true;
    };
  }, [router]);

  if (checking) {
    return (
      <div className="min-h-dvh flex items-center justify-center">
        <div className="animate-pulse text-sm text-gray-600">Loading…</div>
      </div>
    );
  }

  return (
    <main className="min-h-dvh flex items-center justify-center p-6">
      <div className="w-full max-w-md space-y-6 text-center">
        <header className="space-y-2">
          <h1 className="text-3xl font-semibold">Welcome to TexaPay</h1>
          <p className="text-gray-600">Send money fast and securely across borders.</p>
        </header>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <Link
            href="/auth/register"
            className="inline-flex items-center justify-center rounded bg-black text-white py-2.5 px-4 hover:opacity-90"
          >
            Get started
          </Link>
          <Link
            href="/auth/login"
            className="inline-flex items-center justify-center rounded border border-gray-300 py-2.5 px-4 hover:bg-gray-50"
          >
            Sign in
          </Link>
        </div>
        <p className="text-xs text-gray-500">By continuing, you agree to our terms and policies.</p>
      </div>
    </main>
  );
}
