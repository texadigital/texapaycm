"use client";
import React from "react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

type Props = {
  children: React.ReactNode;
};

export default function QueryProvider({ children }: Props) {
  const [queryClient] = React.useState(
    () =>
      new QueryClient({
        defaultOptions: {
          queries: {
            staleTime: 30_000,
            gcTime: 5 * 60_000,
            refetchOnWindowFocus: false,
            refetchOnReconnect: false,
            retry: 1,
            placeholderData: (prev) => prev,
          },
        },
      })
  );
  return (
    <QueryClientProvider client={queryClient}>
      {children}
      {process.env.NEXT_PUBLIC_RQ_DEVTOOLS === "1" ? null : null}
    </QueryClientProvider>
  );
}
