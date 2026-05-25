<?php

declare(strict_types=1);

namespace App\Notifications\Ads;

use App\Enums\Language;
use App\Models\Ad;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * "Your ad is live" — sent when a draft clears auto-moderation (or admin
 * approval) and transitions to ACTIVE.
 *
 * Delivers via mail + database so the in-app bell icon picks it up too.
 * The CTA points at the public ad URL on the web app; the deep-link host
 * is configured via `qbazaar.web_url` so staging / prod stay separate.
 */
class AdApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Ad $ad) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        // Database channel will be enabled once the Sprint 10 notifications
        // table lands. For now we deliver via mail only to avoid coupling
        // moderation hand-off to a not-yet-shipped migration.
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $locale = $this->resolveLocale($notifiable);

        return (new MailMessage)
            ->subject(__('messages.ad_notifications.approved.subject', [], $locale))
            ->greeting(__('messages.ad_notifications.approved.greeting', [], $locale))
            ->line(__('messages.ad_notifications.approved.line_intro', ['title' => $this->ad->title], $locale))
            ->action(__('messages.ad_notifications.approved.action', [], $locale), $this->adUrl())
            ->line(__('messages.ad_notifications.approved.line_outro', [], $locale));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'kind' => 'ad.approved',
            'ad_id' => $this->ad->id,
            'title' => $this->ad->title,
            'url' => $this->adUrl(),
        ];
    }

    private function adUrl(): string
    {
        return rtrim((string) config('qbazaar.web_url', config('app.url')), '/') . '/ads/' . $this->ad->id;
    }

    private function resolveLocale(mixed $notifiable): string
    {
        if ($notifiable instanceof User) {
            return $notifiable->language instanceof Language
                ? $notifiable->language->value
                : (string) config('qbazaar.default_language', 'ar');
        }

        return (string) config('qbazaar.default_language', 'ar');
    }
}
