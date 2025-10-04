"use client";
import React from "react";
import { useSearchParams, useRouter } from "next/navigation";
import { useQuery, useMutation } from "@tanstack/react-query";
import RequireAuth from "@/components/guards/require-auth";
import PageHeader from "@/components/ui/page-header";
import http from "@/lib/api";

export default function PoliciesAcceptPage() {
  return (
    <React.Suspense fallback={<div className="min-h-dvh grid place-items-center p-6 text-sm text-gray-600">Loading…</div>}>
      <PoliciesAcceptInner />
    </React.Suspense>
  );
}

function PoliciesAcceptInner() {
  const sp = useSearchParams();
  const router = useRouter();
  const nextUrl = sp.get("next") || "/dashboard";

  const status = useQuery<{ accepted: boolean; versions?: { terms?: string; privacy?: string } }>({
    queryKey: ["policies-status"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/policies/status");
      return res.data as any;
    },
    staleTime: 60_000,
  });

  const meta = useQuery<{ terms?: { url?: string; version?: string }; privacy?: { url?: string; version?: string } }>({
    queryKey: ["policies-index"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/policies");
      return res.data as any;
    },
    staleTime: 60 * 60_000,
  });

  React.useEffect(() => {
    if (status.data?.accepted) {
      router.replace(nextUrl);
    }
  }, [status.data?.accepted, nextUrl, router]);

  const [agree, setAgree] = React.useState(false);
  const [signature, setSignature] = React.useState("");
  const [error, setError] = React.useState<string | null>(null);

  const accept = useMutation({
    mutationFn: async () => {
      setError(null);
      const res = await http.post("/api/mobile/policies/accept", { signature });
      return res.data as { success?: boolean };
    },
    onSuccess: (d) => {
      if (d?.success) router.replace(nextUrl);
      else setError("Unable to record acceptance. Please try again.");
    },
    onError: (e: any) => setError(e?.response?.data?.message || e.message),
  });

  const termsUrl = meta.data?.terms?.url || "/policies/terms";
  const privacyUrl = meta.data?.privacy?.url || "/policies/privacy";

  return (
    <RequireAuth>
      <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-4">
        <PageHeader title="Accept Terms & Privacy" />

        {(status.isLoading || meta.isLoading) && (
          <div className="text-sm text-gray-600">Loading policy information…</div>
        )}
        {error && (
          <div className="text-sm text-red-600 border border-red-200 rounded p-2">{error}</div>
        )}

        <div className="border rounded p-4 space-y-3 text-sm">
          <p>
            Please read and accept our <a className="underline" href={termsUrl}>Terms & Conditions</a> and <a className="underline" href={privacyUrl}>Privacy Policy</a> to continue using the app.
          </p>
          <label className="flex items-start gap-2">
            <input type="checkbox" checked={agree} onChange={(e) => setAgree(e.target.checked)} />
            <span>I have read and agree to the Terms & Conditions and Privacy Policy.</span>
          </label>
          <div>
            <label className="block text-xs text-gray-700 mb-1">Type your full name as signature (optional)</label>
            <input className="w-full border rounded px-3 py-2" value={signature} onChange={(e) => setSignature(e.target.value)} placeholder="Your name" />
          </div>
          <button
            className="bg-black text-white px-4 py-2 rounded disabled:opacity-50"
            disabled={!agree || accept.isPending}
            onClick={() => accept.mutate()}
          >
            {accept.isPending ? "Submitting…" : "Accept and continue"}
          </button>
        </div>
      </div>
    </RequireAuth>
  );
}
