import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import "./globals.css";
import QueryProvider from "@/components/providers/query-provider";
import AuthProvider from "@/components/providers/auth-provider";
import TopBar from "@/components/top-bar";
import SessionWatcher from "@/components/guards/session-watcher";
import AuthNoticeProvider from "@/components/providers/auth-notice";
import AuthNoticeBanner from "@/components/guards/auth-notice-banner";
import OfflineQueueProvider from "@/components/providers/offline-queue-provider";
import AnalyticsProvider from "@/components/providers/analytics";
import BottomNav from "@/components/bottom-nav";
import InstallPromptProvider from "@/components/providers/install-prompt";
import PoliciesGuard from "@/components/guards/policies-guard";
import PoliciesBanner from "@/components/guards/policies-banner";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "TexaPay",
  description: "TexaPay PWA",
  applicationName: "TexaPay",
  themeColor: "#000000",
  manifest: "/manifest.json",
  icons: {
    icon: "/favicon.ico",
  },
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <body
        suppressHydrationWarning
        className={`${geistSans.variable} ${geistMono.variable} antialiased`}
      >
        <QueryProvider>
          <AuthProvider>
            <AuthNoticeProvider>
              <TopBar />
              <PoliciesBanner />
              <SessionWatcher />
              <AuthNoticeBanner />
              <AnalyticsProvider />
              <InstallPromptProvider />
              <PoliciesGuard>
                <OfflineQueueProvider>
                  <div className="pb-16">{children}</div>
                  <BottomNav />
                </OfflineQueueProvider>
              </PoliciesGuard>
            </AuthNoticeProvider>
          </AuthProvider>
        </QueryProvider>
      </body>
    </html>
  );
}
