'use client';

/**
 * Sort dropdown rendered on the search results page header.
 *
 * Controlled component — the actual URL update is owned by the parent so the
 * sort state lives in the query string (and survives reloads + sharing).
 */
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { t } from '@/lib/i18n/messages';
import { cn } from '@/lib/utils';
import type { SortMode } from '@/lib/api/types';

interface Props {
  value: SortMode;
  onChange: (next: SortMode) => void;
  className?: string;
}

const SORT_OPTIONS: SortMode[] = [
  'latest',
  'oldest',
  'price_asc',
  'price_desc',
];

export function SortDropdown({ value, onChange, className }: Props) {
  return (
    <div className={cn('flex items-center gap-2', className)}>
      <span className="text-ink-500 text-xs font-medium">
        {t('search.sort.label', 'الترتيب')}
      </span>
      <Select
        value={value}
        onValueChange={(next) => onChange((next as SortMode) ?? 'latest')}
      >
        <SelectTrigger className="h-9 min-w-[180px]">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          {SORT_OPTIONS.map((option) => (
            <SelectItem key={option} value={option}>
              {t(`search.sort.${option}`)}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
    </div>
  );
}
