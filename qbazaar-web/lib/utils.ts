import { clsx, type ClassValue } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

/**
 * Format a timestamp as a localised relative-time phrase (e.g. "5 minutes ago",
 * "قبل 5 دقائق"). Falls back to a short absolute date if the value is in the
 * future or older than 30 days.
 */
export function formatRelativeTime(
  isoTimestamp: string,
  locale: string = 'ar',
): string {
  const date = new Date(isoTimestamp);
  if (Number.isNaN(date.getTime())) return '';

  const now = Date.now();
  const diffMs = now - date.getTime();
  const diffSec = Math.round(diffMs / 1000);

  const rtf = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });

  if (Math.abs(diffSec) < 60) return rtf.format(-diffSec, 'second');
  const diffMin = Math.round(diffSec / 60);
  if (Math.abs(diffMin) < 60) return rtf.format(-diffMin, 'minute');
  const diffHr = Math.round(diffMin / 60);
  if (Math.abs(diffHr) < 24) return rtf.format(-diffHr, 'hour');
  const diffDay = Math.round(diffHr / 24);
  if (Math.abs(diffDay) < 30) return rtf.format(-diffDay, 'day');

  // Older than 30 days — show the short date so the relative phrase doesn't
  // become misleading (e.g. "12 months ago" instead of an actual date).
  return new Intl.DateTimeFormat(locale, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(date);
}

/**
 * Render a date as a long-form month + year (e.g. "March 2022", "مارس 2022").
 * Used on the public profile "Member since" line.
 */
export function formatMonthYear(
  isoTimestamp: string,
  locale: string = 'ar',
): string {
  const date = new Date(isoTimestamp);
  if (Number.isNaN(date.getTime())) return '';
  return new Intl.DateTimeFormat(locale, {
    year: 'numeric',
    month: 'long',
  }).format(date);
}
