"use client";
import React from "react";
import PageHeader from "@/components/ui/page-header";

export default function SupportHomePage() {
  return (
    <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-4">
      <PageHeader title="Support" />

      <section className="space-y-3 text-sm">
        <div className="border rounded p-3">
          <div className="font-medium mb-1">Help topics</div>
          <ul className="list-disc pl-5 space-y-1 text-gray-700">
            <li><a className="underline" href="/support/help/getting-started">Getting started</a></li>
            <li><a className="underline" href="/support/help/limits-and-fees">Limits and fees</a></li>
            <li><a className="underline" href="/support/help/transfers">Transfers & receipts</a></li>
          </ul>
        </div>
        <div className="border rounded p-3">
          <div className="font-medium mb-1">Contact us</div>
          <p className="text-gray-700">For urgent issues email support@texapay.com.
          Ticketing UI will appear here in a later phase.</p>
        </div>
      </section>
    </div>
  );
}
