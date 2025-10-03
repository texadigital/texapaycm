"use client";
import React from "react";

export default function OfflinePage() {
  return (
    <div className="min-h-dvh p-6 max-w-xl mx-auto space-y-3">
      <h1 className="text-2xl font-semibold">You are offline</h1>
      <p className="text-sm text-gray-700">Some data may be unavailable, but you can still browse cached pages and your recent activity. We’ll sync changes automatically when you’re back online.</p>
      <ul className="list-disc pl-5 text-sm text-gray-700 space-y-1">
        <li>Use the bottom tabs to navigate cached areas.</li>
        <li>Queued actions will sync when online.</li>
      </ul>
    </div>
  );
}
