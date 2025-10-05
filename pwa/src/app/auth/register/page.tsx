"use client";
import React from "react";
import { useMutation } from "@tanstack/react-query";
import Link from "next/link";
import http from "@/lib/api";
import { setAccessToken } from "@/lib/auth";
import { useRouter } from "next/navigation";

type LaravelValidationErrors = {
  message?: string;
  errors?: Record<string, string[] | string>;
  code?: string;
};

export default function RegisterPage() {
  const router = useRouter();
  const [name, setName] = React.useState("");
  const [phone, setPhone] = React.useState("");
  const [password, setPassword] = React.useState("");
  const [pin, setPin] = React.useState("");
  const [topError, setTopError] = React.useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = React.useState<Record<string, string[]>>({});

  function normalizePhone(p: string) {
    const digits = p.replace(/\D+/g, '');
    if (digits.startsWith('237')) return `+${digits}`; // already with country
    if (digits.length === 9 && digits.startsWith('6')) return `+237${digits}`; // local CM format
    return `+${digits}`;
  }

  const register = useMutation({
    mutationFn: async () => {
      setTopError(null);
      setFieldErrors({});
      // Ensure Sanctum session + CSRF cookie exists (required for cookie-based auth flows)
      try { await http.get('/sanctum/csrf-cookie'); } catch {}
      const res = await http.post("/api/mobile/auth/register", {
        name,
        phone: normalizePhone(phone),
        password,
        pin,
      });
      return res.data;
    },
    onError: (e: any) => {
      const data: LaravelValidationErrors = e?.response?.data || {};
      if (data?.errors && typeof data.errors === "object") {
        const normalized: Record<string, string[]> = {};
        Object.entries(data.errors).forEach(([k, v]) => {
          normalized[k] = Array.isArray(v) ? v : [String(v)];
        });
        setFieldErrors(normalized);
      }
      let msg = data?.message || e.message || "Registration failed";
      // Only set 'Phone already registered' if the backend indicates phone duplication explicitly
      const status = e?.response?.status;
      const rawMsg = String(data?.message || e?.response?.data || e.message || '');
      const phoneTakenFromFields = Array.isArray((data as any)?.errors?.phone) && ((data as any).errors.phone as string[]).some((t) => /taken|exists|already/i.test(String(t)));
      const explicitPhoneInMsg = /phone|msisdn/i.test(rawMsg) && /taken|exists|already|duplicate/i.test(rawMsg);
      const sqlKeyPhone = /users?_phone_unique|unique.*phone/i.test(rawMsg);
      if ((status === 422 || status === 409) && (phoneTakenFromFields || explicitPhoneInMsg || sqlKeyPhone)) {
        setFieldErrors((prev) => ({ ...prev, phone: ["Phone already registered"] }));
        msg = ""; // suppress top error in favor of field error
      } else if (String(status).startsWith('5')) {
        msg = "Something went wrong. Please try again.";
      }
      if (typeof msg === 'string' && msg.toLowerCase().includes('session store not set')) {
        msg = 'Session was not initialized. Please refresh the page and try again.';
      }
      if (msg) setTopError(msg);
    },
    onSuccess: async (data: any) => {
      try {
        if (data && data.accessToken) {
          setAccessToken(data.accessToken);
        }
      } catch {}
      try {
        const res = await http.get('/api/mobile/policies/status');
        const accepted = !!(res?.data?.accepted);
        if (!accepted) {
          router.replace('/policies/accept?next=/dashboard');
          return;
        }
      } catch {}
      router.push("/dashboard");
    },
  });

  const getErr = (key: string) => fieldErrors[key]?.[0];

  return (
    <div className="min-h-dvh flex items-center justify-center p-6">
      <div className="w-full max-w-sm space-y-5">
        <header className="space-y-1">
          <h1 className="text-2xl font-semibold">Create account</h1>
          <p className="text-sm text-gray-600">Start sending with TexaPay</p>
        </header>

        {topError ? (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">
            {topError}
          </div>
        ) : null}

        <form
          onSubmit={(e) => {
            e.preventDefault();
            register.mutate();
          }}
          className="space-y-4"
          noValidate
        >
          <div>
            <label htmlFor="name" className="block text-sm mb-1">
              Full name
            </label>
            <input
              id="name"
              name="name"
              className="w-full border rounded px-3 py-2"
              value={name}
              onChange={(e) => setName(e.target.value)}
              required
              autoComplete="name"
              aria-invalid={!!getErr("name")}
            />
            {getErr("name") && (
              <p className="mt-1 text-xs text-red-600">{getErr("name")}</p>
            )}
          </div>

          <div>
            <label htmlFor="phone" className="block text-sm mb-1">
              Phone
            </label>
            <input
              id="phone"
              name="phone"
              className="w-full border rounded px-3 py-2"
              value={phone}
              onChange={(e) => {
                setPhone(e.target.value);
                // Clear phone-specific error as user edits
                setFieldErrors((prev) => {
                  if (!prev.phone) return prev;
                  const { phone: _ph, ...rest } = prev as any;
                  return rest;
                });
                if (topError) setTopError(null);
              }}
              placeholder="+2376..."
              required
              autoComplete="tel"
              inputMode="tel"
              aria-invalid={!!getErr("phone")}
            />
            {getErr("phone") && (
              <p className="mt-1 text-xs text-red-600">{getErr("phone")}</p>
            )}
          </div>

          <div>
            <label htmlFor="password" className="block text-sm mb-1">
              Password
            </label>
            <input
              id="password"
              name="password"
              type="password"
              className="w-full border rounded px-3 py-2"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              minLength={6}
              autoComplete="new-password"
              aria-invalid={!!getErr("password")}
            />
            {getErr("password") && (
              <p className="mt-1 text-xs text-red-600">{getErr("password")}</p>
            )}
          </div>

          <div>
            <label htmlFor="pin" className="block text-sm mb-1">
              PIN (4-6 digits)
            </label>
            <input
              id="pin"
              name="pin"
              type="password"
              className="w-full border rounded px-3 py-2"
              value={pin}
              onChange={(e) => setPin(e.target.value)}
              inputMode="numeric"
              pattern="\\d{4,6}"
              required
              aria-invalid={!!getErr("pin")}
            />
            {getErr("pin") && (
              <p className="mt-1 text-xs text-red-600">{getErr("pin")}</p>
            )}
          </div>

          <button
            type="submit"
            className="w-full bg-black text-white px-4 py-2 rounded disabled:opacity-50"
            disabled={register.isPending}
          >
            {register.isPending ? "Creating..." : "Create account"}
          </button>
        </form>
        <p className="text-sm">
          Have an account? <Link className="underline" href="/auth/login">Sign in</Link>
        </p>
      </div>
    </div>
  );
}
