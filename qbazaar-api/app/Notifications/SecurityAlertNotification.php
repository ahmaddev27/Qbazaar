<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\Language;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Sent when a successful login originates from a device we've never seen on
 * this user before.
 *
 * "Never seen" is decided by App\Services\Auth\DeviceFingerprintService — we
 * keep this notification dumb and just render whatever we're handed.
 *
 * Localised to the user's preferred language. The mail body intentionally
 * does NOT include the new access token, password, or any sensitive value —
 * only the device label, IP, and timestamp.
 *
 * // TODO Phase 2: lookup city for the IP via a geo library and surface it
 *    in the mail. For now we emit the IP as-is.
 */
class SecurityAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $deviceLabel,
        public readonly string $ip,
        public readonly Carbon $occurredAt,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $this->resolveLocale($notifiable);
        $name = $notifiable instanceof User ? $notifiable->full_name : '';

        return (new MailMessage)
            ->subject(__('auth.security_alert.mail.subject', [], $locale))
            ->greeting(__('auth.security_alert.mail.greeting', ['name' => $name], $locale))
            ->line(__('auth.security_alert.mail.line_intro', [], $locale))
            ->line(__('auth.security_alert.mail.line_device', ['device' => $this->deviceLabel], $locale))
            ->line(__('auth.security_alert.mail.line_ip', ['ip' => $this->ip], $locale))
            ->line(__('auth.security_alert.mail.line_time', ['time' => $this->occurredAt->toIso8601String()], $locale))
            ->line(__('auth.security_alert.mail.line_if_you', [], $locale))
            ->line(__('auth.security_alert.mail.line_if_not_you', [], $locale));
    }

    private function resolveLocale(object $notifiable): string
    {
        if ($notifiable instanceof User) {
            return $notifiable->language instanceof Language
                ? $notifiable->language->value
                : (string) config('qbazaar.default_language', 'ar');
        }

        return (string) config('qbazaar.default_language', 'ar');
    }
}
