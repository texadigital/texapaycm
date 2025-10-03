"use client";
import React from "react";
import { useQuery } from "@tanstack/react-query";
import http from "@/lib/api";
import { Card, CardBody } from "@/components/ui/card";
import { CardSkeleton } from "@/components/ui/skeleton";

type Bank = {
  bankCode: string;
  name: string;
  aliases?: string[];
};

type BanksResponse = {
  banks: Bank[];
};

export default function BanksPage() {
  const [q, setQ] = React.useState("");
  const [refresh, setRefresh] = React.useState(false);

  const { data, isLoading, error, refetch, isFetching } = useQuery<BanksResponse>({
    queryKey: ["banks", q, refresh],
    queryFn: async () => {
      const params = new URLSearchParams();
      if (q) params.set("q", q);
      if (refresh) params.set("refresh", "1");
      const res = await http.get(`/api/mobile/banks${params.toString() ? `?${params.toString()}` : ""}`);
      return res.data;
    },
    staleTime: 60_000 * 60 * 24, // 24h
  });

  const banks = data?.banks ?? [];

  return (
    <div className="min-h-dvh p-6 max-w-2xl mx-auto space-y-4">
      <div className="flex items-center gap-2">
        <input
          className="flex-1 border rounded px-3 py-2"
          placeholder="Search banks"
          value={q}
          onChange={(e) => setQ(e.target.value)}
        />
        <button
          className="border rounded px-3 py-2"
          onClick={() => {
            setRefresh(false);
            refetch();
          }}
          disabled={isFetching}
        >
          {isFetching ? "Searching..." : "Search"}
        </button>
        <button
          className="border rounded px-3 py-2"
          onClick={() => {
            setRefresh(true);
            refetch().finally(() => setRefresh(false));
          }}
        >
          Force refresh
        </button>
      </div>

      {isLoading && (
        <div className="space-y-3">
          <CardSkeleton lines={2} />
          <CardSkeleton lines={2} />
          <CardSkeleton lines={2} />
        </div>
      )}
      {error && (
        <div className="text-sm text-red-600 border border-red-200 rounded p-2">
          {(error as any)?.response?.data?.message || (error as Error).message}
        </div>
      )}

      <div className="border rounded divide-y">
        {banks.length === 0 ? (
          <Card>
            <CardBody>
              <div className="text-sm text-gray-600">No banks.</div>
            </CardBody>
          </Card>
        ) : (
          banks.map((b) => (
            <Card key={b.bankCode}>
              <CardBody>
                <div className="font-medium">{b.name}</div>
                <div className="text-xs text-gray-600">{b.bankCode}</div>
                {b.aliases && b.aliases.length > 0 ? (
                  <div className="text-xs text-gray-500 mt-1">Aliases: {b.aliases.join(", ")}</div>
                ) : null}
              </CardBody>
            </Card>
          ))
        )}
      </div>
    </div>
  );
}
