"use client";
import React from "react";
import Link from "next/link";
import { useSearchParams, useRouter } from "next/navigation";
import { useMutation } from "@tanstack/react-query";
import http from "@/lib/api";

export default function ResetPasswordPage() {
  return (
    <React.Suspense fallback={<div className="min-h-dvh grid place-items-center p-6 text-sm text-gray-600">Loading…</div>}>
      <ResetPasswordInner />
    </React.Suspense>
  );
}

function ResetPasswordInner() {
  const sp = useSearchParams();
  const router = useRouter();
  const initialIdentifier = React.useMemo(() => sp.get("identifier") || sp.get("phone") || "", [sp]);
  const [identifier, setIdentifier] = React.useState(initialIdentifier);
  const [code, setCode] = React.useState("");
  const [password, setPassword] = React.useState("");
  const [confirm, setConfirm] = React.useState("");
  const [message, setMessage] = React.useState<string | null>(null);
  const [error, setError] = React.useState<string | null>(null);
  const [resendIn, setResendIn] = React.useState<number>(60);

  // Countdown timer for resend
  React.useEffect(() => {
    if (resendIn <= 0) return;
    const t = setTimeout(() => setResendIn((s) => s - 1), 1000);
    return () => clearTimeout(t);
  }, [resendIn]);

  const reset = useMutation({
    mutationFn: async () => {
      setMessage(null);
      setError(null);
      if (!identifier || code.length !== 6) throw new Error("Enter the 6-digit code sent to your phone.");
      if (!password || password.length < 6) throw new Error("Enter a new password (min 6 characters).");
      if (password !== confirm) throw new Error("Passwords do not match.");
      const res = await http.post("/api/mobile/auth/password/reset", { identifier, code, new_password: password });
      return res.data as any;
    },
    onSuccess: (d: any) => {
      setMessage(d?.message || "Password reset successful. You can now sign in.");
      // Redirect to login after a short pause
      setTimeout(() => router.replace(`/auth/login?reset=1`), 1200);
    },
    onError: (e: any) => setError(e?.response?.data?.message || e.message || "Failed"),
  });

  const resend = useMutation({
    mutationFn: async () => {
      setError(null);
      const res = await http.post("/api/mobile/auth/password/forgot", { identifier });
      return res.data as any;
    },
    onSuccess: () => setResendIn(60),
    onError: (e: any) => {
      const ra = Number(e?.response?.headers?.['retry-after']) || Number(e?.response?.data?.retryAfterSeconds);
      if (e?.response?.status === 429 && ra) {
        setResendIn(Math.min(120, Math.max(5, ra)));
        setError(`Please wait ${Math.min(120, Math.max(5, ra))}s before trying again.`);
      } else {
        setError(e?.response?.data?.message || e.message || "Failed to resend code");
      }
    },
  });

  return (
    <div className="min-h-dvh flex items-center justify-center p-6">
      <div className="w-full max-w-sm space-y-4">
        <h1 className="text-2xl font-semibold">Reset password</h1>
        {message ? (
          <div className="text-sm text-green-700 border border-green-200 rounded p-2">{message}</div>
        ) : null}
        {error ? (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">{error}</div>
        ) : null}
        <form
          className="space-y-3"
          onSubmit={(e) => {
            e.preventDefault();
            reset.mutate();
          }}
        >
          <div>
            <label className="block text-sm mb-1">Phone</label>
            <input
              className="w-full border rounded px-3 py-2"
              value={identifier}
              onChange={(e) => setIdentifier(e.target.value)}
              placeholder="+2376..."
              required
            />
          </div>
          <div>
            <label className="block text-sm mb-1">Code</label>
            <input
              className="w-full border rounded px-3 py-2"
              value={code}
              onChange={(e) => setCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
              placeholder="6-digit code"
              required
              inputMode="numeric"
              pattern="\\d{6}"
            />
          </div>
          <div className="flex items-center justify-between text-xs text-gray-600">
            <span>{code.length === 6 ? "Code looks good" : "Enter the 6-digit code sent to your phone"}</span>
            <button
              type="button"
              className="underline disabled:opacity-50"
              onClick={() => resend.mutate()}
              disabled={resend.isPending || resendIn > 0}
            >
              {resendIn > 0 ? `Resend in ${resendIn}s` : (resend.isPending ? 'Resending…' : 'Resend code')}
            </button>
          </div>
          {code.length === 6 && (
            <>
              <div>
                <label className="block text-sm mb-1">New password</label>
                <input
                  className="w-full border rounded px-3 py-2"
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  required
                  minLength={6}
                />
              </div>
              <div>
                <label className="block text-sm mb-1">Confirm password</label>
                <input
                  className="w-full border rounded px-3 py-2"
                  type="password"
                  value={confirm}
                  onChange={(e) => setConfirm(e.target.value)}
                  placeholder="••••••••"
                  required
                  minLength={6}
                />
              </div>
            </>
          )}
          <button type="submit" className="w-full bg-black text-white px-4 py-2 rounded disabled:opacity-50" disabled={reset.isPending || code.length !== 6}>
            {reset.isPending ? "Resetting..." : "Reset password"}
          </button>
        </form>
        <p className="text-sm">
          <Link href="/auth/login" className="underline">Back to login</Link>
        </p>
      </div>
    </div>
  );
}
