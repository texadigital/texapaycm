"use client";
import React from "react";

export function Card({ children, className = "" }: { children: React.ReactNode; className?: string }) {
  return <div className={("border rounded bg-white/50 dark:bg-black/30 " + className).trim()}>{children}</div>;
}

export function CardBody({ children, className = "" }: { children: React.ReactNode; className?: string }) {
  return <div className={("p-4 " + className).trim()}>{children}</div>;
}

export function CardTitle({ children, className = "" }: { children: React.ReactNode; className?: string }) {
  return <div className={("text-sm text-gray-500 " + className).trim()}>{children}</div>;
}
