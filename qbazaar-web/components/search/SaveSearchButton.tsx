'use client';

/**
 * "Save this search" button + dialog.
 *
 * Pops a dialog with a single name input (validated by Zod) and persists the
 * current query-params bag through `useSaveSearchMutation`. The button is
 * gated on auth — when signed out it nudges the user to log in instead of
 * silently failing the request.
 */
import { useState } from 'react';
import Link from 'next/link';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { toast } from 'sonner';
import { BookmarkPlusIcon, Loader2Icon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { FieldError } from '@/components/auth/FieldError';
import { t, translateMaybeKey } from '@/lib/i18n/messages';
import { useAuth } from '@/hooks/useAuth';
import { useSaveSearchMutation } from '@/lib/queries/search';
import { ApiClientError } from '@/lib/api/auth';
import type { SearchQueryParams } from '@/lib/api/types';

const SAVED_SEARCH_LIMIT = 'SAVED_SEARCH_LIMIT';

const schema = z.object({
  name: z
    .string()
    .min(1, 'search.save_search.name_required')
    .max(60, 'search.save_search.name_max'),
});

type FormValues = z.infer<typeof schema>;

interface Props {
  params: SearchQueryParams;
  className?: string;
}

export function SaveSearchButton({ params, className }: Props) {
  const { isAuthenticated, isHydrated } = useAuth();
  const [open, setOpen] = useState(false);

  const form = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { name: '' },
  });
  const mutation = useSaveSearchMutation();

  const onSubmit = form.handleSubmit((values) => {
    mutation.mutate(
      { name: values.name.trim(), query_params: params },
      {
        onSuccess: () => {
          toast.success(t('search.save_search.success_toast', 'تم حفظ البحث'));
          form.reset();
          setOpen(false);
        },
        onError: (err) => {
          if (err instanceof ApiClientError && err.code === SAVED_SEARCH_LIMIT) {
            toast.error(
              t('search.save_search.limit_error', 'وصلت إلى الحد الأقصى'),
            );
            return;
          }
          if (err instanceof ApiClientError) {
            toast.error(
              translateMaybeKey(`search.errors.${err.code.toLowerCase()}`) ||
                translateMaybeKey('search.errors.save_failed') ||
                err.message,
            );
            return;
          }
          toast.error(t('search.errors.save_failed', 'تعذّر حفظ البحث'));
        },
      },
    );
  });

  // Until the auth store hydrates we render the button but disabled — keeps
  // the layout stable and avoids a flash.
  if (isHydrated && !isAuthenticated) {
    return (
      <Button
        asChild
        type="button"
        variant="outline"
        size="default"
        className={`text-coral border-coral hover:bg-coral/10 rounded-full ${className ?? ''}`}
      >
        <Link href="/login">
          <BookmarkPlusIcon className="size-3.5" aria-hidden />
          {t('search.save_search.button', 'احفظ البحث')}
        </Link>
      </Button>
    );
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger
        render={
          <Button
            type="button"
            variant="outline"
            size="default"
            className={`text-coral border-coral hover:bg-coral/10 rounded-full ${className ?? ''}`}
          />
        }
      >
        <BookmarkPlusIcon className="size-3.5" aria-hidden />
        {t('search.save_search.button', 'احفظ البحث')}
      </DialogTrigger>

      <DialogContent>
        <form onSubmit={onSubmit} noValidate>
          <DialogHeader>
            <DialogTitle>
              {t('search.save_search.dialog_title', 'احفظ هذا البحث')}
            </DialogTitle>
            <DialogDescription>
              {t(
                'search.save_search.dialog_subtitle',
                'سنحفظ الفلاتر الحالية لتعيد تشغيلها متى شئت.',
              )}
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-1.5 py-4">
            <Label htmlFor="saved-search-name">
              {t('search.save_search.name_label', 'اسم البحث')}
            </Label>
            <Input
              id="saved-search-name"
              type="text"
              autoComplete="off"
              maxLength={80}
              placeholder={t('search.save_search.name_placeholder')}
              aria-invalid={form.formState.errors.name ? 'true' : 'false'}
              aria-describedby={
                form.formState.errors.name ? 'saved-search-name-error' : undefined
              }
              className="h-10"
              {...form.register('name')}
            />
            <FieldError
              id="saved-search-name-error"
              message={form.formState.errors.name?.message}
            />
          </div>

          <DialogFooter>
            <DialogClose
              render={
                <Button
                  type="button"
                  variant="outline"
                  size="default"
                  className="rounded-full"
                >
                  {t('search.save_search.cancel', 'إلغاء')}
                </Button>
              }
            />
            <Button
              type="submit"
              size="default"
              disabled={mutation.isPending}
              className="bg-coral hover:bg-coral/90 rounded-full text-white"
            >
              {mutation.isPending ? (
                <>
                  <Loader2Icon className="size-3.5 animate-spin" aria-hidden />
                  {t('search.save_search.saving', 'جاري الحفظ…')}
                </>
              ) : (
                t('search.save_search.save', 'حفظ')
              )}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
