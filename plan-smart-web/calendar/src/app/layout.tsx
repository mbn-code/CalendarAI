import "@/styles/globals.css";
import { Inter } from "next/font/google";
import { type Metadata } from "next";
import { Toaster } from "sonner";

const inter = Inter({ subsets: ["latin"] });

export const metadata: Metadata = {
  title: "Calendar",
  description: "Smart calendar application",
  icons: [{ rel: "icon", url: "/favicon.ico" }],
};

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="en" className={inter.className}>
      <body className="antialiased bg-background text-foreground">
        {children}
        <Toaster richColors closeButton position="top-right" />
      </body>
    </html>
  );
}
