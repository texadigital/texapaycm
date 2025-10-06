"use client";
import React from "react";
import { useMutation } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import http from "@/lib/api";

function useRetryAfterCooldown() {
  const [until, setUntil] = React.useState(0);
  const [left, setLeft] = React.useState(0);
  React.useEffect(() => {
    if (until <= Date.now()) { setLeft(0); return; }
    const id = setInterval(() => {
      setLeft(Math.max(0, Math.ceil((until - Date.now())/1000)));
    }, 500);
    return () => clearInterval(id);
  }, [until]);
  return { until, left, setUntil };
}

export default function SignupPage() {
  const router = useRouter();
  const [step, setStep] = React.useState<"phone"|"code"|"setpass"|"profile">("phone");
  const [topError, setTopError] = React.useState<string | null>(null);

  const [phone, setPhone] = React.useState("");
  const [code, setCode] = React.useState("");
  const [password, setPassword] = React.useState("");
  const [firstName, setFirstName] = React.useState("");
  const [lastName, setLastName] = React.useState("");
  const [dob, setDob] = React.useState("");
  const { left: cooldownLeft, setUntil } = useRetryAfterCooldown();

  function norm(p: string) {
    const d = p.replace(/\D+/g, "");
    if (d.startsWith("237")) return "+"+d;
    if (d.length===9 && d.startsWith("6")) return "+237"+d;
    return d ? "+"+d : "";
  }

  const req = useMutation({
    mutationFn: async () => {
      setTopError(null);
      const res = await http.post("/api/mobile/auth/signup/request", { phone: norm(phone) });
      return res.data;
    },
    onError: (e:any) => {
      const ra = Number(e?.response?.headers?.['retry-after']) || Number(e?.response?.data?.retryAfterSeconds);
      if (e?.response?.status===429 && ra) {
        setUntil(Date.now()+Math.min(120, Math.max(5, ra))*1000);
        setTopError(`Please wait ${Math.min(120, Math.max(5, ra))}s before trying again.`);
        return;
      }
      setTopError(e?.response?.data?.message || e.message);
    },
    onSuccess: () => setStep("code"),
  });

  const verify = useMutation({
    mutationFn: async () => {
      setTopError(null);
      const res = await http.post("/api/mobile/auth/signup/verify", {
        phone: norm(phone),
        code,
        password,
        first_name: firstName,
        last_name: lastName,
        dob,
      });
      return res.data;
    },
    onError: (e:any) => {
      const ra = Number(e?.response?.headers?.['retry-after']) || Number(e?.response?.data?.retryAfterSeconds);
      if (e?.response?.status===429 && ra) {
        setUntil(Date.now()+Math.min(120, Math.max(5, ra))*1000);
        setTopError(`Please wait ${Math.min(120, Math.max(5, ra))}s before trying again.`);
        return;
      }
      setTopError(e?.response?.data?.message || e.message);
    },
    onSuccess: () => {
      router.replace("/profile/address");
    }
  });

  return (
    <div className="min-h-dvh max-w-md mx-auto p-6 space-y-4">
      <h1 className="text-xl font-semibold">Create account</h1>
      {topError && (
        <div className="text-sm text-red-600 border border-red-200 rounded p-2">{topError}{cooldownLeft>0?` (${cooldownLeft}s)`:''}</div>
      )}

      {step === "phone" && (
        <form className="space-y-3" onSubmit={(e)=>{e.preventDefault(); if (cooldownLeft>0) return; req.mutate();}}>
          <label className="block text-sm">Phone</label>
          <input className="w-full border rounded px-3 py-2" value={phone} onChange={(e)=>setPhone(e.target.value)} placeholder="+2376..." required />
          <button className="w-full h-11 rounded bg-emerald-600 text-white disabled:opacity-50" disabled={req.isPending || cooldownLeft>0}>
            {req.isPending? 'Sending code…':'Send code'}
          </button>
        </form>
      )}

      {step === "code" && (
        <form className="space-y-3" onSubmit={(e)=>{e.preventDefault(); setStep("setpass");}}>
          <label className="block text-sm">Verification code</label>
          <input className="w-full border rounded px-3 py-2" value={code} onChange={(e)=>setCode(e.target.value)} inputMode="numeric" pattern="\d{4,8}" required />
          <button className="w-full h-11 rounded bg-emerald-600 text-white">Continue</button>
          <button type="button" className="w-full h-11 rounded bg-gray-100" onClick={()=> req.mutate()} disabled={req.isPending || cooldownLeft>0}>Resend {cooldownLeft>0?`(${cooldownLeft}s)`:''}</button>
        </form>
      )}

      {step === "setpass" && (
        <form className="space-y-3" onSubmit={(e)=>{e.preventDefault(); setStep("profile");}}>
          <label className="block text-sm">Password</label>
          <input type="password" className="w-full border rounded px-3 py-2" value={password} onChange={(e)=>setPassword(e.target.value)} minLength={8} required />
          <button className="w-full h-11 rounded bg-emerald-600 text-white">Continue</button>
        </form>
      )}

      {step === "profile" && (
        <form className="space-y-3" onSubmit={(e)=>{e.preventDefault(); verify.mutate();}}>
          <div>
            <label className="block text-sm">First name</label>
            <input className="w-full border rounded px-3 py-2" value={firstName} onChange={(e)=>setFirstName(e.target.value)} required />
          </div>
          <div>
            <label className="block text-sm">Last name</label>
            <input className="w-full border rounded px-3 py-2" value={lastName} onChange={(e)=>setLastName(e.target.value)} required />
          </div>
          <div>
            <label className="block text-sm">Date of birth</label>
            <input type="date" className="w-full border rounded px-3 py-2" value={dob} onChange={(e)=>setDob(e.target.value)} required />
          </div>
          <button className="w-full h-11 rounded bg-emerald-600 text-white disabled:opacity-50" disabled={verify.isPending || cooldownLeft>0}>
            {verify.isPending? 'Creating…':'Create account'}
          </button>
        </form>
      )}
    </div>
  );
}
