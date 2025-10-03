"use client";
import React from "react";
import Link from "next/link";
import { useMutation } from "@tanstack/react-query";
import http from "@/lib/api";

export default function ForgotPasswordPage() {
  const [phone, setPhone] = React.useState("");
  const [message, setMessage] = React.useState<string | null>(null);
  const [error, setError] = React.useState<string | null>(null);

  const send = useMutation({
    mutationFn: async () => {
      setMessage(null);
      setError(null);
      const res = await http.post("/api/mobile/auth/forgot-password", { phone });
      return res.data as any;
    },
    onSuccess: (d: any) => {
      setMessage(d?.message || "Reset code sent. Check your SMS/push.");
    },
    onError: (e: any) => setError(e?.response?.data?.message || e.message || "Failed"),
  });

  return (
    <div className="min-h-dvh flex items-center justify-center p-6">
      <div className="w-full max-w-sm space-y-4">
        <h1 className="text-2xl font-semibold">Forgot password</h1>
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
            send.mutate();
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
          <button type="submit" className="w-full bg-black text-white px-4 py-2 rounded" disabled={send.isPending}>
            {send.isPending ? "Sending..." : "Send reset code"}
          </button>
        </form>
        <p className="text-sm">
          Have a code? <Link className="underline" href="/auth/reset-password">Reset now</Link>
        </p>
        <p className="text-sm">
          <Link className="underline" href="/auth/login">Back to login</Link>
        </p>
      </div>
    </div>
  );
}
