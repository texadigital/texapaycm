"use client";
import React from "react";

export type AuthNoticeCtx = {
  unauthorized: boolean;
  setUnauthorized: (v: boolean) => void;
};

const Ctx = React.createContext<AuthNoticeCtx | undefined>(undefined);

export default function AuthNoticeProvider({ children }: { children: React.ReactNode }) {
  const [unauthorized, setUnauthorized] = React.useState(false);

  React.useEffect(() => {
    const onUnauthorized = () => setUnauthorized(true);
    window.addEventListener('auth:unauthorized', onUnauthorized as any);
    return () => window.removeEventListener('auth:unauthorized', onUnauthorized as any);
  }, []);

  const value = React.useMemo(() => ({ unauthorized, setUnauthorized }), [unauthorized]);
  return <Ctx.Provider value={value}>{children}</Ctx.Provider>;
}

export function useAuthNotice(): AuthNoticeCtx {
  const v = React.useContext(Ctx);
  if (!v) throw new Error("useAuthNotice must be used within AuthNoticeProvider");
  return v;
}
