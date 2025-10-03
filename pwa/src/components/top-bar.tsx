"use client";
import React from "react";
import { usePathname } from "next/navigation";
import { clearAccessToken, getAccessToken } from "@/lib/auth";

export default function TopBar() {
  const pathname = usePathname();
  const onAuthPage = pathname?.startsWith('/auth');
  const [hasToken, setHasToken] = React.useState<boolean>(!!getAccessToken());

  React.useEffect(() => {
    const handler = () => setHasToken(!!getAccessToken());
    window.addEventListener('storage', handler);
    window.addEventListener('auth:unauthorized', handler as any);
    return () => {
      window.removeEventListener('storage', handler);
      window.removeEventListener('auth:unauthorized', handler as any);
    };
  }, []);

  return (
    <header className="px-4 py-2 border-b flex items-center justify-between">
      <a href="/" className="font-medium">TEXA</a>
      {!onAuthPage && hasToken ? (
        <form
          onSubmit={(e) => {
            e.preventDefault();
            fetch('/api/mobile/auth/logout', { method: 'POST', credentials: 'include' })
              .catch(() => undefined)
              .finally(() => { clearAccessToken(); window.location.href = '/auth/login'; });
          }}
        >
          <button className="text-sm underline" type="submit">Logout</button>
        </form>
      ) : null}
    </header>
  );
}
