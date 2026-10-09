import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import { AuthProvider } from "../providers/AuthProvider";
import "./globals.css";
import { IBM_Plex_Sans } from "next/font/google";
const plex = IBM_Plex_Sans({ subsets: ["latin"], weight: ["400", "500", "600"] });

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "HelpDesk",
  description: "Customer Support Platform",
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html
      lang="en"
      className={`${geistSans.variable} ${geistMono.variable} plex.className h-full antialiased`}
    >
      <body className="plex.className min-h-full flex flex-col">
        {children}
      </body>
    </html>
  );
}
