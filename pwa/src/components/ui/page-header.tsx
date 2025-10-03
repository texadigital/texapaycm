"use client";
import React from "react";

export default function PageHeader({ title, children }: { title: string; children?: React.ReactNode }) {
  return (
    <div className="flex items-center justify-between">
      <h1 className="text-2xl font-semibold">{title}</h1>
      <div className="flex items-center gap-2">{children}</div>
    </div>
  );
}
