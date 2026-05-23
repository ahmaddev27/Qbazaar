'use client';

/**
 * FE-2.3 — Account security page.
 *
 * Just hosts the `PasswordChangeForm` for now. Two-factor + recovery codes
 * land in a later wave.
 */
import { t } from '@/lib/i18n/messages';
import { PasswordChangeForm } from '@/components/account/PasswordChangeForm';

export default function AccountSecurityPage() {
  return (
    <section className="space-y-6">
      <header className="space-y-1.5">
        <h1 className="font-display text-3xl tracking-tight sm:text-4xl">
          {t('account.security.title')}
        </h1>
        <p className="text-muted-foreground text-sm">
          {t('account.security.subtitle')}
        </p>
      </header>

      <div className="bg-card ring-foreground/10 rounded-2xl p-5 sm:p-7 ring-1">
        <PasswordChangeForm />
      </div>
    </section>
  );
}
