'use client';

/**
 * FE-2.2 — Account profile editor.
 *
 * The page pulls the initial data with React Query, then hands it to the
 * `ProfileForm` component which owns RHF + Zod validation and the
 * `PUT /account/profile` mutation.
 */
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { Loader2Icon } from 'lucide-react';

import { t } from '@/lib/i18n/messages';
import { getAccountProfile } from '@/lib/api/account';
import { ProfileForm } from '@/components/account/ProfileForm';
import { AvatarUploader } from '@/components/account/AvatarUploader';

export default function AccountProfilePage() {
  const queryClient = useQueryClient();
  const {
    data: profile,
    isLoading,
    error,
  } = useQuery({
    queryKey: ['account', 'profile'],
    queryFn: getAccountProfile,
  });

  return (
    <section className="space-y-6">
      <header className="space-y-1.5">
        <h1 className="font-display text-3xl tracking-tight sm:text-4xl">
          {t('account.profile.title')}
        </h1>
        <p className="text-muted-foreground text-sm">
          {t('account.profile.subtitle')}
        </p>
      </header>

      <AvatarUploader
        fullName={profile?.full_name ?? ''}
        onUploaded={() => {
          // Keep the cached profile in sync so the form sees the latest URLs.
          queryClient.invalidateQueries({ queryKey: ['account', 'profile'] });
        }}
      />

      <div className="bg-card ring-foreground/10 rounded-2xl p-5 sm:p-7 ring-1">
        {isLoading || !profile ? (
          <div
            className="flex justify-center py-10"
            role="status"
            aria-live="polite"
          >
            <Loader2Icon
              className="text-muted-foreground size-5 animate-spin"
              aria-hidden
            />
          </div>
        ) : error ? (
          <p className="text-destructive text-sm" role="alert">
            {t('auth.errors.network')}
          </p>
        ) : (
          <ProfileForm initial={profile} />
        )}
      </div>
    </section>
  );
}
