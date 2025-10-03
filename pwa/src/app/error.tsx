"use client";
import React from "react";
import Link from "next/link";

export default function GlobalError({ error, reset }: { error: Error & { digest?: string }; reset: () => void }) {
  React.useEffect(() => {
    // You can log the error to an error reporting service
    // console.error(error);
  }, [error]);

  return (
    <html>
      <body>
        <div className="min-h-dvh flex items-center justify-center p-6">
          <div className="w-full max-w-md space-y-3 text-center">
            <h1 className="text-2xl font-semibold">Something went wrong</h1>
            <p className="text-sm text-gray-600">An unexpected error occurred while rendering this page.</p>
            {error?.message ? (
              <p className="text-xs text-red-600 break-words">{error.message}</p>
            ) : null}
            <div className="flex items-center justify-center gap-2 pt-2">
              <button className="border rounded px-4 py-2" onClick={() => reset()}>Try again</button>
              <Link className="underline" href="/">Go home</Link>
            </div>
          </div>
        </div>
      </body>
    </html>
  );
}
