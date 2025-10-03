"use client";
import React from "react";

export function Skeleton({ className = "" }: { className?: string }) {
  return (
    <div
      className={
        "animate-pulse rounded-md bg-gray-200/60 dark:bg-gray-700/40 " + className
      }
    />
  );
}

export function CardSkeleton({ lines = 3 }: { lines?: number }) {
  return (
    <div className="border rounded p-4 space-y-3">
      {Array.from({ length: lines }).map((_, i) => (
        <Skeleton key={i} className={i === 0 ? "h-5 w-1/3" : "h-4 w-2/3"} />
      ))}
    </div>
  );
}
