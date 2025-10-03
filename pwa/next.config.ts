import type { NextConfig } from "next";
import withPWA from "next-pwa";

const backendBase = process.env.NEXT_PUBLIC_API_BASE_URL;

const nextConfig: NextConfig = {
  reactStrictMode: true,
  // Ensure Turbopack uses this package as the root (not the monorepo root)
  turbopack: {
    root: __dirname,
  },
  async rewrites() {
    if (!backendBase) return [];
    return [
      {
        source: "/api/:path*",
        destination: `${backendBase}/api/:path*`,
      },
      {
        source: "/sanctum/:path*",
        destination: `${backendBase}/sanctum/:path*`,
      },
    ];
  },
};

export default withPWA({
  dest: "public",
  register: true,
  skipWaiting: true,
  disable: process.env.NODE_ENV === "development",
  runtimeCaching: [
    {
      urlPattern: /\/api\/mobile\/banks.*/,
      handler: "CacheFirst",
      options: {
        cacheName: "api-banks",
        expiration: { maxAgeSeconds: 60 * 60 * 24 },
        cacheableResponse: { statuses: [200] },
      },
    },
    {
      urlPattern: /\/api\/mobile\/dashboard.*/,
      handler: "NetworkFirst",
      options: {
        cacheName: "api-dashboard",
        networkTimeoutSeconds: 3,
        cacheableResponse: { statuses: [200] },
      },
    },
    {
      urlPattern: /\/api\/mobile\/notifications(\/.*)?$/,
      handler: "StaleWhileRevalidate",
      options: {
        cacheName: "api-notifications",
        cacheableResponse: { statuses: [200] },
      },
    },
    {
      urlPattern: /\/api\/mobile\/pricing\/rate-preview.*/,
      handler: "NetworkFirst",
      options: {
        cacheName: "api-rate-preview",
        expiration: { maxAgeSeconds: 60 },
        cacheableResponse: { statuses: [200] },
      },
    },
  ],
})(nextConfig);
