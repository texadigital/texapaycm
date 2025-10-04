"use client";
import React from "react";
import { useMutation } from "@tanstack/react-query";
import Link from "next/link";
import http from "@/lib/api";
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

  const register = useMutation({
    mutationFn: async () => {
      setTopError(null);
      setFieldErrors({});
      const res = await http.post("/api/mobile/auth/register", {
        name,
        phone,
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
      const msg = data?.message || e.message || "Registration failed";
      setTopError(msg);
    },
    onSuccess: async () => {
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
              onChange={(e) => setPhone(e.target.value)}
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
