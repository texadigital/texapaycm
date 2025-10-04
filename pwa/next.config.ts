import type { NextConfig } from "next";
import withPWA from "next-pwa";

const backendBase = process.env.NEXT_PUBLIC_API_BASE_URL;

const nextConfig: NextConfig = {
  reactStrictMode: true,
  // Ensure Turbopack uses this package as the root (not the monorepo root)
  turbopack: {
    root: __dirname,
  },
  // SSR mode (default) – we deploy behind PM2/Nginx, so no static export
  images: { unoptimized: true },
  eslint: { ignoreDuringBuilds: true },
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
  workboxOptions: {
    navigateFallback: "/offline",
  },
  additionalManifestEntries: [
    { url: "/", revision: undefined },
    { url: "/dashboard", revision: undefined },
    { url: "/transfers", revision: undefined },
    { url: "/transfer/confirm", revision: undefined },
    { url: "/transfer/quote", revision: undefined },
    { url: "/transfer/verify", revision: undefined },
    { url: "/profile", revision: undefined },
    { url: "/profile/personal-info", revision: undefined },
    { url: "/profile/security", revision: undefined },
    { url: "/profile/limits", revision: undefined },
    { url: "/notifications", revision: undefined },
    { url: "/notifications/preferences", revision: undefined },
    { url: "/support", revision: undefined },
    { url: "/support/help", revision: undefined },
    { url: "/support/contact", revision: undefined },
    { url: "/support/tickets", revision: undefined },
    { url: "/banks", revision: undefined },
    { url: "/policies", revision: undefined },
    { url: "/kyc", revision: undefined },
    { url: "/auth/login", revision: undefined },
    { url: "/auth/register", revision: undefined },
    { url: "/auth/forgot-password", revision: undefined },
    { url: "/auth/reset-password", revision: undefined },
    { url: "/offline", revision: undefined },
  ],
  runtimeCaching: [
    // Background Sync for write operations (queued and replayed when back online)
    {
      urlPattern: /\/api\/mobile\/.*$/,
      handler: 'NetworkOnly',
      method: 'POST',
      options: {
        backgroundSync: {
          name: 'bg-sync-post',
          options: { maxRetentionTime: 24 * 60 },
        },
      },
    },
    {
      urlPattern: /\/api\/mobile\/.*$/,
      handler: 'NetworkOnly',
      method: 'PUT',
      options: {
        backgroundSync: {
          name: 'bg-sync-put',
          options: { maxRetentionTime: 24 * 60 },
        },
      },
    },
    {
      urlPattern: /\/api\/mobile\/.*$/,
      handler: 'NetworkOnly',
      method: 'DELETE',
      options: {
        backgroundSync: {
          name: 'bg-sync-delete',
          options: { maxRetentionTime: 24 * 60 },
        },
      },
    },
    // Static assets: images, fonts, icons
    {
      urlPattern: ({ request }: any) => request.destination === 'image',
      handler: 'CacheFirst',
      options: {
        cacheName: 'images',
        expiration: { maxEntries: 200, maxAgeSeconds: 60 * 60 * 24 * 30 },
        cacheableResponse: { statuses: [200] },
      },
    },
    {
      urlPattern: ({ request }: any) => request.destination === 'font',
      handler: 'CacheFirst',
      options: {
        cacheName: 'fonts',
        expiration: { maxEntries: 50, maxAgeSeconds: 60 * 60 * 24 * 365 },
        cacheableResponse: { statuses: [200] },
      },
    },
    {
      urlPattern: /\/api\/mobile\/banks.*/,
      handler: "CacheFirst",
      options: {
        cacheName: "api-banks",
        expiration: { maxAgeSeconds: 60 * 60 * 24, maxEntries: 100 },
        cacheableResponse: { statuses: [200] },
      },
    },
    {
      urlPattern: /\/api\/mobile\/dashboard.*/,
      handler: "NetworkFirst",
      options: {
        cacheName: "api-dashboard",
        networkTimeoutSeconds: 2,
        cacheableResponse: { statuses: [200] },
        expiration: { maxAgeSeconds: 60, maxEntries: 50 },
      },
    },
    {
      urlPattern: /\/api\/mobile\/notifications(\/.*)?$/,
      handler: "StaleWhileRevalidate",
      options: {
        cacheName: "api-notifications",
        cacheableResponse: { statuses: [200] },
        expiration: { maxAgeSeconds: 60 * 5, maxEntries: 100 },
      },
    },
    {
      // Transfers read-only endpoints (index/show/timeline). Do NOT cache receipt endpoints.
      urlPattern: /\/api\/mobile\/transfers(\/.+)?$/,
      handler: "NetworkFirst",
      options: {
        cacheName: "api-transfers",
        networkTimeoutSeconds: 2,
        cacheableResponse: { statuses: [200] },
        expiration: { maxAgeSeconds: 120, maxEntries: 100 },
        matchOptions: {
          ignoreSearch: false,
        },
      },
    },
    {
      urlPattern: /\/api\/mobile\/pricing\/rate-preview.*/,
      handler: "NetworkFirst",
      options: {
        cacheName: "api-rate-preview",
        expiration: { maxAgeSeconds: 30, maxEntries: 100 },
        cacheableResponse: { statuses: [200] },
      },
    },
    // Profile summary
    {
      urlPattern: /\/api\/mobile\/profile(\/.*)?$/,
      handler: "NetworkFirst",
      options: {
        cacheName: "api-profile",
        cacheableResponse: { statuses: [200] },
        expiration: { maxAgeSeconds: 60, maxEntries: 50 },
      },
    },
    // Security endpoints
    {
      urlPattern: /\/api\/mobile\/profile\/security(\/.*)?$/,
      handler: "NetworkFirst",
      options: {
        cacheName: "api-security",
        cacheableResponse: { statuses: [200] },
        expiration: { maxAgeSeconds: 60, maxEntries: 50 },
      },
    },
    // Notification preferences
    {
      urlPattern: /\/api\/mobile\/notifications\/preferences(\/.*)?$/,
      handler: "StaleWhileRevalidate",
      options: {
        cacheName: "api-notification-preferences",
        cacheableResponse: { statuses: [200] },
        expiration: { maxAgeSeconds: 60 * 5, maxEntries: 100 },
      },
    },
    // Support content
    {
      urlPattern: /\/api\/mobile\/support(\/.*)?$/,
      handler: "StaleWhileRevalidate",
      options: {
        cacheName: "api-support",
        cacheableResponse: { statuses: [200] },
        expiration: { maxAgeSeconds: 60 * 10, maxEntries: 100 },
      },
    },
  ],
})
(nextConfig);
