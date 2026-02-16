import type { Metadata } from "next";
import { Inter, JetBrains_Mono } from "next/font/google";
import { cookies, headers } from "next/headers";
import { ThemeProvider } from "next-themes";
import { Toaster } from "@/components/ui/sonner";
import { I18nProvider } from "@/components/providers/i18n-provider";
import {
  isLocale,
  localeCookieName,
  resolveLocale,
  resolveLocaleFromAcceptLanguage,
} from "@/lib/i18n/config";
import "./globals.css";

const inter = Inter({
  subsets: ["latin"],
  variable: "--font-inter",
});

const jetbrainsMono = JetBrains_Mono({
  subsets: ["latin"],
  variable: "--font-mono",
});

export const metadata: Metadata = {
  title: "Koomky | CRM Freelance",
  description: "CRM auto-heberge pour freelances",
  icons: {
    icon: "/brand/icon.png",
    shortcut: "/brand/icon.png",
    apple: "/brand/icon.png",
  },
};

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const cookieStore = await cookies();
  const localeFromCookie = cookieStore.get(localeCookieName)?.value;

  let initialLocale = resolveLocale(localeFromCookie);
  if (!isLocale(localeFromCookie)) {
    const headerStore = await headers();
    initialLocale = resolveLocaleFromAcceptLanguage(
      headerStore.get("accept-language")
    );
  }

  return (
    <html lang={initialLocale} suppressHydrationWarning>
      <body
        className={`${inter.variable} ${jetbrainsMono.variable} font-sans antialiased`}
      >
        <I18nProvider initialLocale={initialLocale}>
          <ThemeProvider
            attribute="class"
            defaultTheme="system"
            enableSystem
            disableTransitionOnChange
          >
            {children}
            <Toaster position="top-right" richColors closeButton />
          </ThemeProvider>
        </I18nProvider>
      </body>
    </html>
  );
}
