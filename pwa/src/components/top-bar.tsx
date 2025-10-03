"use client";
import React from "react";
import { clearAccessToken } from "@/lib/auth";

export default function TopBar() {
  return (
    <header className="px-4 py-2 border-b flex items-center justify-between">
      <a href="/" className="font-medium">TEXA</a>
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
    </header>
  );
}
