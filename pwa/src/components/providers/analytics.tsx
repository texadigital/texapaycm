"use client";
import React from "react";
import Script from "next/script";

/**
 * AnalyticsProvider
 * - Loads GA4 if NEXT_PUBLIC_GA_ID is present
 * - Loads Sentry (CDN) if NEXT_PUBLIC_SENTRY_DSN is present
 */
export default function AnalyticsProvider() {
  const gaId = process.env.NEXT_PUBLIC_GA_ID;
  const sentryDsn = process.env.NEXT_PUBLIC_SENTRY_DSN;
  const sentryEnv = process.env.NEXT_PUBLIC_SENTRY_ENV || "production";

  return (
    <>
      {/* GA4 */}
      {gaId ? (
        <>
          <Script
            src={`https://www.googletagmanager.com/gtag/js?id=${gaId}`}
            strategy="afterInteractive"
          />
          <Script id="ga4-init" strategy="afterInteractive">
            {`
              window.dataLayer = window.dataLayer || [];
              function gtag(){dataLayer.push(arguments);}
              gtag('js', new Date());
              gtag('config', '${gaId}');
            `}
          </Script>
        </>
      ) : null}

      {/* Sentry (CDN) */}
      {sentryDsn ? (
        <>
          <Script
            src="https://browser.sentry-cdn.com/8.25.0/bundle.min.js"
            integrity="sha384-hpS/8/2Q6C4R8K6+M9wC9F4Q8h1X0eZzC6HjGft3r8qK6cE+9w6r5O1cEo0w6nP4"
            crossOrigin="anonymous"
            strategy="afterInteractive"
            onLoad={() => {
              // @ts-ignore
              if (window.Sentry) {
                // @ts-ignore
                window.Sentry.init({ dsn: '${process.env.NEXT_PUBLIC_SENTRY_DSN}', environment: '${sentryEnv}' });
              }
            }}
          />
        </>
      ) : null}
    </>
  );
}
