'use client';

/**
 * One saved-search row on the account saved-searches page.
 *
 * Renders the name + a chips summary of the persisted params + two actions:
 * Run (routes to /search with the restored params) and Delete (confirm dialog).
 */
import { useMemo, useState } from 'react';
import Link from 'next/link';
import { toast } from 'sonner';
import {
  BookmarkIcon,
  Loader2Icon,
  PlayCircleIcon,
  Trash2Icon,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { useDeleteSavedSearchMutation } from '@/lib/queries/search';
import { t, translateMaybeKey } from '@/lib/i18n/messages';
import { ApiClientError } from '@/lib/api/auth';
import type { SavedSearch, SearchQueryParams } from '@/lib/api/types';

interface Props {
  search: SavedSearch;
}

/**
 * Build the `/search?...` href from the persisted params. We drop keys with
 * `null`/`undefined` so the URL stays clean.
 */
function buildHref(params: SearchQueryParams): string {
  const sp = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === null || value === '') continue;
    sp.set(key, String(value));
  }
  const qs = sp.toString();
  return qs ? `/search?${qs}` : '/search';
}

function summariseParams(params: SearchQueryParams): string[] {
  const chips: string[] = [];
  if (params.q) chips.push(`"${params.q}"`);
  if (params.category_slug) chips.push(params.category_slug);
  if (params.location_slug) chips.push(params.location_slug);
  if (params.condition) chips.push(t(`ads.condition.${params.condition}`));
  if (params.price_min !== undefined || params.price_max !== undefined) {
    const min = params.price_min ?? 0;
    const max = params.price_max ?? '∞';
    chips.push(`${min} – ${max} QAR`);
  }
  if (params.sort) chips.push(t(`search.sort.${params.sort}`));
  return chips;
}

export function SavedSearchCard({ search }: Props) {
  const [confirmOpen, setConfirmOpen] = useState(false);
  const deleteMutation = useDeleteSavedSearchMutation();

  const href = useMemo(() => buildHref(search.query_params), [search]);
  const chips = useMemo(() => summariseParams(search.query_params), [search]);
  const createdAt = useMemo(
    () => new Date(search.created_at).toLocaleDateString(),
    [search.created_at],
  );

  const onDelete = () => {
    deleteMutation.mutate(search.id, {
      onSuccess: () => {
        toast.success(
          t('account.saved_searches.delete_success', 'تم حذف البحث المحفوظ'),
        );
        setConfirmOpen(false);
      },
      onError: (err) => {
        if (err instanceof ApiClientError) {
          toast.error(
            translateMaybeKey(`search.errors.${err.code.toLowerCase()}`) ||
              translateMaybeKey('search.errors.delete_failed') ||
              err.message,
          );
        } else {
          toast.error(t('search.errors.delete_failed'));
        }
      },
    });
  };

  return (
    <article className="border-ink-200 bg-card flex flex-col gap-3 rounded-2xl border p-5 sm:flex-row sm:items-start sm:justify-between">
      <div className="min-w-0 flex-1">
        <h3 className="font-display text-coral flex items-center gap-2 text-2xl leading-tight">
          <BookmarkIcon className="size-5 shrink-0" aria-hidden />
          <span className="truncate">{search.name}</span>
        </h3>

        {chips.length > 0 ? (
          <ul className="mt-3 flex flex-wrap gap-1.5">
            {chips.map((chip, index) => (
              <li
                key={`${chip}-${index}`}
                className="bg-cream-200 text-ink-700 inline-flex items-center rounded-full px-2.5 py-1 text-[11px]"
              >
                {chip}
              </li>
            ))}
          </ul>
        ) : (
          <p className="text-ink-500 mt-2 text-xs">
            {t('search.title_all', 'كل الإعلانات')}
          </p>
        )}

        <p className="text-ink-500 mt-3 text-[11px]">
          {t('account.saved_searches.created_at', { date: createdAt })}
        </p>
      </div>

      <div className="flex shrink-0 flex-wrap items-center gap-2 sm:flex-col sm:items-stretch">
        <Button
          asChild
          size="default"
          className="bg-coral hover:bg-coral/90 rounded-full text-white"
        >
          <Link href={href}>
            <PlayCircleIcon className="size-3.5" aria-hidden />
            {t('account.saved_searches.run', 'تشغيل البحث')}
          </Link>
        </Button>

        <Dialog open={confirmOpen} onOpenChange={setConfirmOpen}>
          <DialogTrigger
            render={
              <Button
                type="button"
                variant="destructive"
                size="default"
                className="rounded-full"
              />
            }
          >
            <Trash2Icon className="size-3.5" aria-hidden />
            {t('account.saved_searches.delete', 'حذف')}
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>
                {t('account.saved_searches.delete_confirm_title')}
              </DialogTitle>
              <DialogDescription>
                {t('account.saved_searches.delete_confirm_body')}
              </DialogDescription>
            </DialogHeader>
            <DialogFooter>
              <DialogClose
                render={
                  <Button variant="outline" size="default" className="rounded-full">
                    {t('search.save_search.cancel', 'إلغاء')}
                  </Button>
                }
              />
              <Button
                type="button"
                variant="destructive"
                size="default"
                disabled={deleteMutation.isPending}
                onClick={onDelete}
                className="rounded-full"
              >
                {deleteMutation.isPending ? (
                  <>
                    <Loader2Icon className="size-3.5 animate-spin" aria-hidden />
                    {t('account.saved_searches.delete', 'حذف')}
                  </>
                ) : (
                  t('account.saved_searches.delete', 'حذف')
                )}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    </article>
  );
}
