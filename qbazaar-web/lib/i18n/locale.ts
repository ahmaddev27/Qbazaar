/**
 * Tiny locale helper.
 *
 * Wave 1 ships a single Arabic locale (the `<html lang="ar">` is hardcoded in
 * `app/layout.tsx`), so this helper just returns 'ar'. When the `[locale]`
 * segment lands in a later wave it will be swapped for `useLocale()` from
 * `next-intl` without changing any call-sites.
 */
import type { LocalizedString } from '@/lib/api/types';

export type Locale = 'ar' | 'en';

export function getLocale(): Locale {
  return 'ar';
}

/**
 * Read the localized side of a `LocalizedString` for the active locale,
 * falling back to the other side or an empty string when missing. Used by
 * every component that renders bilingual reference data.
 */
export function localized(
  value: LocalizedString | null | undefined,
  locale: Locale = getLocale(),
): string {
  if (!value) return '';
  return value[locale] ?? value.ar ?? value.en ?? '';
}
