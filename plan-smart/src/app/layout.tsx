import "@/styles/globals.css";
import { Header } from "@/components/Header";
import { cn } from "@/lib/utils";
import { ToastProvider } from "@/components/ui/toast-context";
import { Inter } from "next/font/google";
import type { Metadata } from "next";

const inter = Inter({ subsets: ["latin"] });

export const metadata: Metadata = {
  title: "Smart Calendar - AI-Powered Schedule Optimization",
  description: "Premium calendar planning with AI assistance to optimize your daily schedule based on your preferences and energy levels.",
  icons: [{ rel: "icon", url: "/favicon.ico" }],
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en" suppressHydrationWarning>
      <body className={cn(
        "min-h-screen bg-background font-sans antialiased",
        inter.className
      )}>
        <ToastProvider>
          <Header />
          {children}
        </ToastProvider>
      </body>
    </html>
  );
}
