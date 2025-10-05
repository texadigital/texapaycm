"use client";
import React from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { getAccessToken } from "@/lib/auth";

export default function Home() {
  const router = useRouter();
  const [loading, setLoading] = React.useState(true);
  const [authed, setAuthed] = React.useState(false);

  React.useEffect(() => {
    const token = getAccessToken();
    if (token) {
      setAuthed(true);
      // Small splash, then go to dashboard
      const id = setTimeout(() => router.replace('/dashboard'), 600);
      return () => clearTimeout(id);
    }
    // Not authenticated: show onboarding
    setAuthed(false);
    const id2 = setTimeout(() => setLoading(false), 400);
    return () => clearTimeout(id2);
  }, [router]);

  // Splash
  if (authed) {
    return (
      <div className="min-h-dvh grid place-items-center">
        <div className="text-center">
          <div className="text-2xl font-semibold mb-2">TEXA</div>
          <div className="text-sm text-gray-600">Loading your dashboard…</div>
        </div>
      </div>
    );
  }

  // Onboarding
  return (
    <div className="min-h-dvh grid place-items-center p-6">
      <div className="w-full max-w-sm space-y-6 text-center">
        <div>
          <div className="inline-flex items-center justify-center h-12 w-12 rounded-full bg-black text-white text-lg">T</div>
          <div className="mt-2 text-2xl font-semibold">Welcome to TexaPay</div>
          <div className="text-sm text-gray-600">Fast, transparent transfers to bank accounts.</div>
        </div>

        {loading ? (
          <div className="text-sm text-gray-500">Loading…</div>
        ) : (
          <div className="space-y-3">
            <Link href="/auth/register" className="block w-full bg-black text-white px-4 py-2 rounded">Create account</Link>
            <Link href="/auth/login" className="block w-full border px-4 py-2 rounded">Sign in</Link>
          </div>
        )}

        <div className="text-[11px] text-gray-500">By continuing, you agree to our policies.</div>
      </div>
    </div>
  );
}
