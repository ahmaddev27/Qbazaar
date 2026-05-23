import type { Metadata } from 'next';
import { ForgotPasswordForm } from '@/components/auth/ForgotPasswordForm';
import { t } from '@/lib/i18n/messages';

export const metadata: Metadata = {
  title: 'نسيت كلمة المرور',
  description: 'أرسل رابط إعادة تعيين كلمة المرور إلى بريدك.',
};

export default function ForgotPasswordPage() {
  return (
    <div className="space-y-6">
      <header className="space-y-2">
        <h1 className="font-display text-3xl tracking-tight">
          {t('auth.forgot_password.title')}
        </h1>
        <p className="text-muted-foreground text-sm">
          {t('auth.forgot_password.subtitle')}
        </p>
      </header>
      <ForgotPasswordForm />
    </div>
  );
}
