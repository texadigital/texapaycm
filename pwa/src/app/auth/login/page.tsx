"use client";
import React from "react";
import { useMutation } from "@tanstack/react-query";
import Link from "next/link";
import http from "@/lib/api";
import { validateCameroon, providerMeta, formatForDisplay } from "@/lib/phone";
import { setAccessToken } from "@/lib/auth";

export default function LoginPage() {
  const [nextUrl] = React.useState<string>(() => {
    if (typeof window === 'undefined') return "/dashboard";
    const u = new URL(window.location.href);
    return u.searchParams.get('next') || "/dashboard";
  });
  const [phone, setPhone] = React.useState("");
  const [password, setPassword] = React.useState("");
  const [pin, setPin] = React.useState("");
  const [needPin, setNeedPin] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);

  // No CSRF needed for JWT login

  const login = useMutation({
    mutationFn: async () => {
      setError(null);
      // Validate & normalize phone before sending
      const v = validateCameroon(phone);
      if (!v.valid) {
        throw new Error(v.error || "Invalid phone number");
      }
      const res = await http.post(
        "/api/mobile/auth/login",
        {
          phone: v.normalized,
          password,
          pin: needPin ? (pin || undefined) : undefined,
        },
        {
          withCredentials: true,
        }
      );
      const data = res.data as any;
      if (data?.accessToken) {
        setAccessToken(data.accessToken);
      }
      return data;
    },
    onError: (e: any) => {
      const code = e?.response?.data?.code;
      if (code === 'PIN_REQUIRED') {
        setNeedPin(true);
        setError("PIN required. Enter your 4-6 digit PIN to continue.");
        return;
      }
      const msg = e?.response?.data?.message || e.message || "Login failed";
      setError(msg);
    },
    onSuccess: async () => {
      try {
        // Ask backend if policies are accepted; if not, go to acceptance first
        const res = await http.get('/api/mobile/policies/status');
        const accepted = !!(res?.data?.accepted);
        if (!accepted) {
          const qp = new URLSearchParams({ next: nextUrl || '/dashboard' });
          window.location.href = `/policies/accept?${qp.toString()}`;
          return;
        }
      } catch {}
      window.location.href = nextUrl || "/dashboard";
    },
  });

  return (
    <div className="min-h-dvh flex items-center justify-center p-6">
      <div className="w-full max-w-sm space-y-4">
        <h1 className="text-2xl font-semibold">Sign in</h1>
        {error ? (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">
            {error}
          </div>
        ) : null}
        <form
          onSubmit={(e) => {
            e.preventDefault();
            login.mutate();
          }}
          className="space-y-3"
        >
          <div>
            <label className="block text-sm mb-1">Phone</label>
            <input
              className="w-full border rounded px-3 py-2"
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              placeholder="2376XXXXXXXX"
              required
            />
            {(() => {
              const v = validateCameroon(phone);
              const meta = providerMeta(v.provider);
              return (
                <div className="mt-1 flex items-center gap-2 text-xs">
                  <span className="text-gray-600">{formatForDisplay(phone)}</span>
                  {meta ? (
                    <span className={`px-2 py-0.5 rounded ${meta.color}`}>{meta.label}</span>
                  ) : null}
                </div>
              );
            })()}
          </div>
          <div>
            <label className="block text-sm mb-1">Password</label>
            <input
              type="password"
              className="w-full border rounded px-3 py-2"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </div>
          {needPin && (
            <div>
              <label className="block text-sm mb-1">PIN</label>
              <input
                className="w-full border rounded px-3 py-2"
                value={pin}
                onChange={(e) => setPin(e.target.value)}
                inputMode="numeric"
                type="password"
                autoComplete="one-time-code"
                pattern="[0-9]{4,6}"
                placeholder="4-6 digits"
                required
              />
            </div>
          )}
          <button
            type="submit"
            className="w-full bg-black text-white px-4 py-2 rounded disabled:opacity-50"
            disabled={login.isPending}
          >
            {login.isPending ? "Signing in..." : "Sign in"}
          </button>
        </form>
        <div className="text-sm space-y-1">
          <p>
            <Link className="underline" href="/auth/forgot-password">Forgot password?</Link>
          </p>
          <p>
            Have a code? <Link className="underline" href="/auth/reset-password">Reset now</Link>
          </p>
          <p>
            No account? <Link className="underline" href="/auth/register">Create one</Link>
          </p>
        </div>
      </div>
    </div>
  );
}
