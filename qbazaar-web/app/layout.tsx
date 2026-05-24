import type { Metadata } from 'next';
import { Amiri, Cairo, DM_Sans, Geist_Mono, Instrument_Serif } from 'next/font/google';
import { ThemeProvider } from 'next-themes';
import { Toaster } from '@/components/ui/sonner';
import { Providers } from './providers';
import './globals.css';

const dmSans = DM_Sans({
  subsets: ['latin'],
  weight: ['400', '500', '600', '700'],
  variable: '--font-dm-sans',
});

const instrumentSerif = Instrument_Serif({
  subsets: ['latin'],
  weight: '400',
  style: ['normal', 'italic'],
  variable: '--font-instrument-serif',
});

const cairo = Cairo({
  subsets: ['arabic'],
  weight: ['400', '500', '600', '700'],
  variable: '--font-cairo',
});

// Amiri — classic Arabic serif. Used as the display heading face for RTL,
// pairing with Instrument Serif's editorial vibe on the LTR side.
const amiri = Amiri({
  subsets: ['arabic'],
  weight: ['400', '700'],
  style: ['normal', 'italic'],
  variable: '--font-amiri',
});

const geistMono = Geist_Mono({
  subsets: ['latin'],
  variable: '--font-mono',
});

export const metadata: Metadata = {
  title: {
    default: "QBazaar — Qatar's friendly classifieds marketplace",
    template: '%s · QBazaar',
  },
  description: 'QBazaar — buy, sell and discover near you in Qatar.',
};

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  // Wave 1 ships a single Arabic locale + RTL — the `[locale]` segment lands in Wave 2.
  return (
    <html
      lang="ar"
      dir="rtl"
      suppressHydrationWarning
      className={`${dmSans.variable} ${instrumentSerif.variable} ${cairo.variable} ${amiri.variable} ${geistMono.variable}`}
    >
      <body className="min-h-full flex flex-col">
        <ThemeProvider attribute="class" defaultTheme="light" enableSystem>
          <Providers>{children}</Providers>
          <Toaster richColors closeButton position="top-center" />
        </ThemeProvider>
      </body>
    </html>
  );
}
