'use client';

/**
 * FE-2.7 — Data & account.
 *
 * Three stacked cards, each a self-contained action:
 *
 *   1. Export my data           POST  /account/data-export-request
 *   2. Deactivate my account    POST  /account/deactivate
 *   3. Delete my account        DELETE /account/delete-request
 *
 * Steps 2 & 3 both require the current password (so a hijacked session
 * can't kill an account) and accept an optional reason. After success
 * we sign the user out + redirect to `/login` with a sticky notice
 * (`?deactivated=1` or `?deleted=1`) so the login page can explain what
 * happened next.
 *
 * The export action keeps the user on this page — the actual file is
 * delivered out-of-band over email.
 */
import { useRouter } from 'next/navigation';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import { toast } from 'sonner';
import {
  DatabaseIcon,
  DownloadCloudIcon,
  Loader2Icon,
  PowerIcon,
  Trash2Icon,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { FieldError } from '@/components/auth/FieldError';
import { t, translateMaybeKey } from '@/lib/i18n/messages';
import { cn } from '@/lib/utils';
import {
  deactivateAccount,
  requestAccountDeletion,
  requestDataExport,
} from '@/lib/api/account';
import { ApiClientError } from '@/lib/api/auth';
import { AuthErrorCode, UserErrorCode } from '@/lib/api/types';
import {
  deactivateSchema,
  deleteAccountSchema,
  type DeactivateInput,
  type DeleteAccountInput,
} from '@/lib/validation/account';
import { useAuth } from '@/hooks/useAuth';

export default function AccountDataPage() {
  return (
    <section className="space-y-6">
      <header className="space-y-1.5">
        <h1 className="font-display text-3xl tracking-tight sm:text-4xl">
          {t('account.data.title')}
        </h1>
        <p className="text-muted-foreground text-sm">
          {t('account.data.subtitle')}
        </p>
      </header>

      <div className="space-y-5">
        <ExportDataCard />
        <DeactivateAccountCard />
        <DeleteAccountCard />
      </div>
    </section>
  );
}

// ── 1. Export ─────────────────────────────────────────────────────────────

function ExportDataCard() {
  const [queued, setQueued] = useState(false);

  const mutation = useMutation({
    mutationFn: requestDataExport,
    onSuccess: () => setQueued(true),
    onError: (err) => {
      if (err instanceof ApiClientError) {
        toast.error(
          translateMaybeKey(`account.errors.${err.code}`) ||
            translateMaybeKey(`auth.errors.${err.code}`) ||
            err.message,
        );
        return;
      }
      toast.error(t('auth.errors.unknown'));
    },
  });

  return (
    <article
      aria-labelledby="export-data-title"
      className="bg-card ring-foreground/10 rounded-2xl p-5 ring-1 sm:p-7"
    >
      <CardHeading
        id="export-data-title"
        icon={<DatabaseIcon className="text-coral size-5" aria-hidden />}
        title={t('account.data.export.title')}
        body={t('account.data.export.body')}
      />

      {queued ? (
        <div
          role="status"
          className="bg-sage/10 border-sage/30 text-ink-700 mt-4 space-y-2 rounded-xl border px-4 py-3 text-sm"
        >
          <p className="text-ink-900 font-semibold">
            {t('account.data.export.queued_title')}
          </p>
          <p>{t('account.data.export.queued_body')}</p>
          <Button
            type="button"
            variant="outline"
            size="default"
            className="rounded-full"
            onClick={() => {
              setQueued(false);
              mutation.reset();
            }}
          >
            {t('account.data.export.request_again')}
          </Button>
        </div>
      ) : (
        <Button
          type="button"
          size="default"
          className="mt-4 rounded-full"
          onClick={() => mutation.mutate()}
          disabled={mutation.isPending}
        >
          {mutation.isPending ? (
            <>
              <Loader2Icon className="size-4 animate-spin" aria-hidden />
              {t('account.data.export.submitting')}
            </>
          ) : (
            <>
              <DownloadCloudIcon className="size-4" aria-hidden />
              {t('account.data.export.submit')}
            </>
          )}
        </Button>
      )}
    </article>
  );
}

// ── 2. Deactivate ─────────────────────────────────────────────────────────

function DeactivateAccountCard() {
  const router = useRouter();
  const { logout } = useAuth();
  const [open, setOpen] = useState(false);

  const form = useForm<DeactivateInput>({
    resolver: zodResolver(deactivateSchema),
    mode: 'onBlur',
    defaultValues: { password: '', reason: '' },
  });

  const mutation = useMutation({
    mutationFn: deactivateAccount,
    onSuccess: async () => {
      // Sign the user out locally so the deactivated session can't keep poking
      // protected endpoints; the toast is the user-visible confirmation.
      await logout();
      router.replace('/login?deactivated=1');
    },
  });

  const onSubmit = form.handleSubmit(async (values) => {
    try {
      await mutation.mutateAsync({
        password: values.password,
        reason: values.reason ?? null,
      });
    } catch (err) {
      handleLifecycleError(err, form, 'deactivate');
    }
  });

  const submitting = form.formState.isSubmitting || mutation.isPending;
  const errors = form.formState.errors;

  return (
    <article
      aria-labelledby="deactivate-account-title"
      className="bg-card ring-foreground/10 rounded-2xl p-5 ring-1 sm:p-7"
    >
      <CardHeading
        id="deactivate-account-title"
        icon={<PowerIcon className="text-coral size-5" aria-hidden />}
        title={t('account.data.deactivate.title')}
        body={t('account.data.deactivate.body')}
      />

      <Dialog
        open={open}
        onOpenChange={(next) => {
          if (!next && submitting) return;
          if (!next) form.reset({ password: '', reason: '' });
          setOpen(next);
        }}
      >
        <Button
          type="button"
          variant="outline"
          size="default"
          className="border-coral/40 text-coral hover:bg-coral/10 mt-4 rounded-full"
          onClick={() => setOpen(true)}
        >
          {t('account.data.deactivate.submit')}
        </Button>

        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {t('account.data.deactivate.dialog_title')}
            </DialogTitle>
            <DialogDescription>
              {t('account.data.deactivate.dialog_body')}
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={onSubmit} noValidate className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="deactivate-password">
                {t('account.data.deactivate.password_label')}
              </Label>
              <Input
                id="deactivate-password"
                type="password"
                autoComplete="current-password"
                dir="ltr"
                placeholder="••••••••"
                aria-invalid={Boolean(errors.password)}
                aria-describedby={
                  errors.password ? 'deactivate-password-error' : undefined
                }
                className="h-10"
                {...form.register('password')}
              />
              <FieldError
                id="deactivate-password-error"
                message={errors.password?.message}
              />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="deactivate-reason">
                {t('account.data.deactivate.reason_label')}
              </Label>
              <Textarea
                id="deactivate-reason"
                rows={3}
                maxLength={280}
                placeholder={t('account.data.deactivate.reason_placeholder')}
                aria-invalid={Boolean(errors.reason)}
                aria-describedby={
                  errors.reason ? 'deactivate-reason-error' : undefined
                }
                {...form.register('reason')}
              />
              <FieldError
                id="deactivate-reason-error"
                message={errors.reason?.message}
              />
            </div>

            <DialogFooter>
              <DialogClose
                render={
                  <Button
                    variant="outline"
                    size="default"
                    className="rounded-full"
                    type="button"
                  >
                    {t('account.data.deactivate.cancel')}
                  </Button>
                }
              />
              <Button
                type="submit"
                size="default"
                disabled={submitting}
                className={cn(
                  'border-coral/40 text-coral hover:bg-coral/10 rounded-full border bg-transparent',
                  submitting && 'cursor-progress',
                )}
              >
                {submitting ? (
                  <>
                    <Loader2Icon className="size-4 animate-spin" aria-hidden />
                    {t('account.data.deactivate.confirming')}
                  </>
                ) : (
                  t('account.data.deactivate.confirm')
                )}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </article>
  );
}

// ── 3. Delete ─────────────────────────────────────────────────────────────

function DeleteAccountCard() {
  const router = useRouter();
  const { logout } = useAuth();
  const [open, setOpen] = useState(false);

  const form = useForm<DeleteAccountInput>({
    resolver: zodResolver(deleteAccountSchema),
    mode: 'onBlur',
    defaultValues: { password: '', reason: '' },
  });

  const mutation = useMutation({
    mutationFn: requestAccountDeletion,
    onSuccess: async () => {
      toast.success(t('account.data.delete.scheduled_toast'));
      await logout();
      router.replace('/login?deleted=1');
    },
  });

  const onSubmit = form.handleSubmit(async (values) => {
    try {
      await mutation.mutateAsync({
        password: values.password,
        reason: values.reason ?? null,
      });
    } catch (err) {
      handleLifecycleError(err, form, 'delete');
    }
  });

  const submitting = form.formState.isSubmitting || mutation.isPending;
  const errors = form.formState.errors;

  return (
    <article
      aria-labelledby="delete-account-title"
      className="bg-card ring-destructive/20 rounded-2xl p-5 ring-1 sm:p-7"
    >
      <CardHeading
        id="delete-account-title"
        icon={<Trash2Icon className="text-destructive size-5" aria-hidden />}
        title={t('account.data.delete.title')}
        body={t('account.data.delete.body')}
      />

      <Dialog
        open={open}
        onOpenChange={(next) => {
          if (!next && submitting) return;
          if (!next) form.reset({ password: '', reason: '' });
          setOpen(next);
        }}
      >
        <Button
          type="button"
          size="default"
          className="bg-destructive text-destructive-foreground hover:bg-destructive/90 mt-4 rounded-full"
          onClick={() => setOpen(true)}
        >
          <Trash2Icon className="size-4" aria-hidden />
          {t('account.data.delete.submit')}
        </Button>

        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {t('account.data.delete.dialog_title')}
            </DialogTitle>
            <DialogDescription>
              {t('account.data.delete.dialog_body')}
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={onSubmit} noValidate className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="delete-password">
                {t('account.data.delete.password_label')}
              </Label>
              <Input
                id="delete-password"
                type="password"
                autoComplete="current-password"
                dir="ltr"
                placeholder="••••••••"
                aria-invalid={Boolean(errors.password)}
                aria-describedby={
                  errors.password ? 'delete-password-error' : undefined
                }
                className="h-10"
                {...form.register('password')}
              />
              <FieldError
                id="delete-password-error"
                message={errors.password?.message}
              />
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="delete-reason">
                {t('account.data.delete.reason_label')}
              </Label>
              <Textarea
                id="delete-reason"
                rows={3}
                maxLength={280}
                placeholder={t('account.data.delete.reason_placeholder')}
                aria-invalid={Boolean(errors.reason)}
                aria-describedby={
                  errors.reason ? 'delete-reason-error' : undefined
                }
                {...form.register('reason')}
              />
              <FieldError
                id="delete-reason-error"
                message={errors.reason?.message}
              />
            </div>

            <DialogFooter>
              <DialogClose
                render={
                  <Button
                    variant="outline"
                    size="default"
                    className="rounded-full"
                    type="button"
                  >
                    {t('account.data.delete.cancel')}
                  </Button>
                }
              />
              <Button
                type="submit"
                size="default"
                disabled={submitting}
                className={cn(
                  'bg-destructive text-destructive-foreground hover:bg-destructive/90 rounded-full',
                  submitting && 'cursor-progress',
                )}
              >
                {submitting ? (
                  <>
                    <Loader2Icon className="size-4 animate-spin" aria-hidden />
                    {t('account.data.delete.confirming')}
                  </>
                ) : (
                  t('account.data.delete.confirm')
                )}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </article>
  );
}

// ── Shared helpers ────────────────────────────────────────────────────────

function CardHeading({
  id,
  icon,
  title,
  body,
}: {
  id: string;
  icon: React.ReactNode;
  title: string;
  body: string;
}) {
  return (
    <header className="space-y-1.5">
      <h2
        id={id}
        className="font-display text-ink-900 inline-flex items-center gap-2 text-xl tracking-tight sm:text-2xl"
      >
        {icon}
        {title}
      </h2>
      <p className="text-muted-foreground text-sm">{body}</p>
    </header>
  );
}

type LifecycleForm = DeactivateInput | DeleteAccountInput;

function handleLifecycleError(
  err: unknown,
  form: ReturnType<typeof useForm<LifecycleForm>>,
  flow: 'deactivate' | 'delete',
) {
  if (err instanceof ApiClientError) {
    if (err.code === AuthErrorCode.ValidationFailed && err.details) {
      const known: (keyof LifecycleForm)[] = ['password', 'reason'];
      let mapped = false;
      for (const [field, messages] of Object.entries(err.details)) {
        if ((known as string[]).includes(field) && messages?.length) {
          form.setError(field as keyof LifecycleForm, {
            type: 'server',
            message: messages[0],
          });
          mapped = true;
        }
      }
      if (mapped) return;
    }

    if (err.code === UserErrorCode.PasswordIncorrect) {
      const msg = t('account.errors.USER_002');
      form.setError('password', { type: 'server', message: msg });
      toast.error(msg);
      return;
    }

    const passwordMissingCode =
      flow === 'deactivate'
        ? UserErrorCode.DeactivationPasswordRequired
        : UserErrorCode.DeletionPasswordRequired;
    if (err.code === passwordMissingCode) {
      const msg = t(`account.errors.${err.code}`);
      form.setError('password', { type: 'server', message: msg });
      toast.error(msg);
      return;
    }

    const fallback =
      translateMaybeKey(`account.errors.${err.code}`) ||
      translateMaybeKey(`auth.errors.${err.code}`) ||
      err.message;
    toast.error(fallback);
    return;
  }
  toast.error(t('auth.errors.unknown'));
}
