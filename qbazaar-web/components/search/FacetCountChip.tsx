/**
 * Tiny pill displayed next to filter labels — e.g. `Cars (145)`.
 *
 * Server-friendly + stateless. The count is intentionally not localised
 * because Arabic-Indic vs. Western digits is a global page setting that
 * lives in a later i18n wave; rendering a raw number keeps the chip honest.
 */
import { cn } from '@/lib/utils';

interface Props {
  count: number;
  active?: boolean;
  className?: string;
}

export function FacetCountChip({ count, active, className }: Props) {
  return (
    <span
      className={cn(
        'inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] font-semibold tabular-nums',
        active
          ? 'bg-coral text-white'
          : 'bg-cream-200 text-ink-700',
        className,
      )}
    >
      {count}
    </span>
  );
}
