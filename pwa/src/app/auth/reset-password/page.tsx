"use client";
import React from "react";
import { useMutation } from "@tanstack/react-query";
import http from "@/lib/api";

export default function ResetPasswordPage() {
  const [phone, setPhone] = React.useState("");
  const [code, setCode] = React.useState("");
  const [password, setPassword] = React.useState("");
  const [message, setMessage] = React.useState<string | null>(null);
  const [error, setError] = React.useState<string | null>(null);

  const reset = useMutation({
    mutationFn: async () => {
      setMessage(null);
      setError(null);
      const res = await http.post("/api/mobile/auth/reset-password", { phone, code, password });
      return res.data as any;
    },
    onSuccess: (d: any) => {
      setMessage(d?.message || "Password reset successful. You can now sign in.");
    },
    onError: (e: any) => setError(e?.response?.data?.message || e.message || "Failed"),
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
              value={phone}
              onChange={(e) => setPhone(e.target.value)}
              placeholder="+2376..."
              required
            />
          </div>
          <div>
            <label className="block text-sm mb-1">Code</label>
            <input
              className="w-full border rounded px-3 py-2"
              value={code}
              onChange={(e) => setCode(e.target.value)}
              placeholder="6-digit code"
              required
              inputMode="numeric"
              pattern="\n?\d{4,8}"
            />
          </div>
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
          <button type="submit" className="w-full bg-black text-white px-4 py-2 rounded" disabled={reset.isPending}>
            {reset.isPending ? "Resetting..." : "Reset password"}
          </button>
        </form>
        <p className="text-sm">
          <a className="underline" href="/auth/login">Back to login</a>
        </p>
      </div>
    </div>
  );
}
