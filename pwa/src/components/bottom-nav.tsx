"use client";
import React from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { getAccessToken } from "@/lib/auth";

const tabs = [
  { href: "/dashboard", label: "Home" },
  { href: "/transfers", label: "Transfers" },
  { href: "/profile", label: "Me" },
];

export default function BottomNav() {
  const path = usePathname();
  const [hasToken, setHasToken] = React.useState<boolean>(!!getAccessToken());
  const onAuthPage = path?.startsWith("/auth");

  React.useEffect(() => {
    const handler = () => setHasToken(!!getAccessToken());
    window.addEventListener("storage", handler);
    window.addEventListener("auth:unauthorized", handler as any);
    return () => {
      window.removeEventListener("storage", handler);
      window.removeEventListener("auth:unauthorized", handler as any);
    };
  }, []);

  if (onAuthPage || !hasToken) return null;

  return (
    <nav className="fixed bottom-0 left-0 right-0 z-40 border-t bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/75">
      <ul className="max-w-2xl mx-auto grid grid-cols-3 text-xs">
        {tabs.map((t) => {
          const active = path === t.href || (path?.startsWith(t.href + "/"));
          return (
            <li key={t.href} className="text-center">
              <Link
                href={t.href}
                className={`block px-2 py-2 ${active ? "text-black font-medium" : "text-gray-600"}`}
                aria-current={active ? "page" : undefined}
              >
                {t.label}
              </Link>
            </li>
          );
        })}
      </ul>
    </nav>
  );
}
