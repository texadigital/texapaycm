"use client";
import React from "react";
import { usePathname, useRouter } from "next/navigation";

type Props = {
  title: string;
  children?: React.ReactNode;
  showBack?: boolean; // default: true
  backHrefFallback?: string; // optional fallback if router.back() wouldn't make sense
};

export default function PageHeader({ title, children, showBack = true, backHrefFallback }: Props) {
  const router = useRouter();
  const pathname = usePathname() || "/";
  const rootRoutes = new Set(["/", "/dashboard", "/transfers", "/profile", "/notifications", "/support"]);
  const canShowBack = showBack && !rootRoutes.has(pathname);

  return (
    <div className="flex items-center justify-between">
      <div className="flex items-center gap-2">
        {canShowBack && (
          <button
            aria-label="Back"
            className="border rounded px-2 py-1 text-sm"
            onClick={() => {
              if (document.referrer) router.back();
              else if (backHrefFallback) window.location.href = backHrefFallback;
              else router.push("/dashboard");
            }}
          >
            ←
          </button>
        )}
        <h1 className="text-2xl font-semibold">{title}</h1>
      </div>
      <div className="flex items-center gap-2">{children}</div>
    </div>
  );
}
