"use client";
import React from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import http from "@/lib/api";

export default function AddressPage() {
  return (
    <React.Suspense fallback={<div className="min-h-dvh grid place-items-center p-6 text-sm text-gray-600">Loading…</div>}>
      <AddressInner />
    </React.Suspense>
  );
}

function AddressInner() {
  const router = useRouter();
  const { data } = useQuery({
    queryKey: ["profile-personal-info"],
    queryFn: async () => {
      const res = await http.get("/api/mobile/profile/personal-info");
      return res.data as any;
    },
    placeholderData: (prev) => prev as any,
    staleTime: 30_000,
  });

  const [address1, setAddress1] = React.useState("");
  const [address2, setAddress2] = React.useState("");
  const [city, setCity] = React.useState("");
  const [state, setState] = React.useState("");
  const [postal, setPostal] = React.useState("");
  const [country, setCountry] = React.useState("CM");
  const [topError, setTopError] = React.useState<string|null>(null);
  const [saved, setSaved] = React.useState(false);

  React.useEffect(() => {
    if (!data) return;
    setAddress1(data?.address_line1 || "");
    setAddress2(data?.address_line2 || "");
    setCity(data?.city || "");
    setState(data?.state || "");
    setPostal(data?.postal_code || "");
    setCountry(data?.country || "CM");
  }, [data]);

  const save = useMutation({
    mutationFn: async () => {
      setTopError(null);
      setSaved(false);
      const payload = {
        address_line1: address1,
        address_line2: address2 || null,
        city,
        state,
        postal_code: postal || null,
        country,
      };
      const res = await http.post("/api/mobile/profile/personal-info", payload);
      return res.data as any;
    },
    onSuccess: () => {
      setSaved(true);
      // Short success state then route to dashboard
      setTimeout(() => router.replace('/dashboard'), 600);
    },
    onError: (e: any) => setTopError(e?.response?.data?.message || e.message || "Failed to save"),
  });

  return (
    <div className="min-h-dvh max-w-md mx-auto p-6 space-y-4">
      <h1 className="text-xl font-semibold">Address</h1>
      {topError && (
        <div className="text-sm text-red-600 border border-red-200 rounded p-2">{topError}</div>
      )}
      {saved && (
        <div className="text-sm text-emerald-700 border border-emerald-200 rounded p-2">Saved</div>
      )}
      <form className="space-y-3" onSubmit={(e)=>{e.preventDefault(); save.mutate();}}>
        <div>
          <label className="block text-sm mb-1">Address line 1</label>
          <input className="w-full border rounded px-3 py-2" value={address1} onChange={(e)=>setAddress1(e.target.value)} required />
        </div>
        <div>
          <label className="block text-sm mb-1">Address line 2 (optional)</label>
          <input className="w-full border rounded px-3 py-2" value={address2} onChange={(e)=>setAddress2(e.target.value)} />
        </div>
        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className="block text-sm mb-1">City</label>
            <input className="w-full border rounded px-3 py-2" value={city} onChange={(e)=>setCity(e.target.value)} required />
          </div>
          <div>
            <label className="block text-sm mb-1">State/Province</label>
            <input className="w-full border rounded px-3 py-2" value={state} onChange={(e)=>setState(e.target.value)} required />
          </div>
        </div>
        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className="block text-sm mb-1">Postal code</label>
            <input className="w-full border rounded px-3 py-2" value={postal} onChange={(e)=>setPostal(e.target.value)} />
          </div>
          <div>
            <label className="block text-sm mb-1">Country</label>
            <select className="w-full border rounded px-3 py-2" value={country} onChange={(e)=>setCountry(e.target.value)}>
              <option value="CM">Cameroon</option>
              <option value="NG">Nigeria</option>
              <option value="GH">Ghana</option>
            </select>
          </div>
        </div>
        <button className="w-full h-11 rounded bg-emerald-600 text-white disabled:opacity-50" disabled={save.isPending}>
          {save.isPending? 'Saving…':'Save address'}
        </button>
      </form>
    </div>
  );
}
