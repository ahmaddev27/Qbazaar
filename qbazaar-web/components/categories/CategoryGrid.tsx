'use client';

/**
 * Responsive grid of category cards.
 *
 * Each card is a link to `/c/{slug}` with the category icon, localised name,
 * and an ads-count chip. The hover treatment uses the brand terracotta accent
 * — sage/coral are reserved for other states.
 */
import Link from 'next/link';
import { Card } from '@/components/ui/card';
import { DynamicIcon } from '@/components/ui/dynamic-icon';
import { localized, getLocale } from '@/lib/i18n/locale';
import { t } from '@/lib/i18n/messages';
import { cn } from '@/lib/utils';
import type { Category } from '@/lib/api/types';

interface Props {
  categories: Category[];
  className?: string;
}

function formatCount(count: number, locale: 'ar' | 'en'): string {
  // Arabic-Indic digits on the Arabic locale match the rest of the marketplace.
  const lang = locale === 'ar' ? 'ar-EG' : 'en-US';
  return new Intl.NumberFormat(lang).format(count);
}

export function CategoryGrid({ categories, className }: Props) {
  const locale = getLocale();
  if (categories.length === 0) {
    return (
      <p className="text-ink-500 py-8 text-center text-sm">
        {t('categories.no_subcategories', 'لا توجد أقسام فرعية بعد')}
      </p>
    );
  }
  return (
    <ul
      className={cn(
        'grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
        className,
      )}
    >
      {categories.map((cat) => (
        <li key={cat.id}>
          <Link
            href={`/c/${cat.slug}`}
            className="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-coral focus-visible:ring-offset-2 rounded-xl"
          >
            <Card
              size="sm"
              className="h-full transition-all duration-200 group-hover:scale-[1.02] group-hover:ring-terracotta group-hover:ring-2 group-focus-visible:ring-terracotta"
            >
              <div className="flex items-center gap-3 px-3">
                <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-coral/15 text-coral">
                  <DynamicIcon name={cat.icon} className="size-5" />
                </span>
                <div className="min-w-0 flex-1">
                  <p className="truncate text-sm font-medium text-ink-900">
                    {localized(cat.name, locale)}
                  </p>
                  <p className="text-ink-500 mt-0.5 text-xs">
                    {t(
                      'categories.ads_count',
                      { count: formatCount(cat.ads_count, locale) },
                      `${formatCount(cat.ads_count, locale)} إعلان`,
                    )}
                  </p>
                </div>
              </div>
            </Card>
          </Link>
        </li>
      ))}
    </ul>
  );
}
