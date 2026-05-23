<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Builds a stable, hashed fingerprint per (platform + truncated UA + IP) so we
 * can decide "is this a new device for this user?" without storing PII in
 * cleartext.
 *
 * Storage decision (decided here, flagged in commit BE-1.20):
 *   We piggyback on `refresh_tokens.device_fingerprint` — the column already
 *   exists, refresh tokens are minted on every login, and pruning is handled
 *   by the existing rotation/expiry flow. No new table.
 *
 *   The trade-off: a logged-out user with no live refresh tokens won't have a
 *   fingerprint history. For our use case (alerting on new device on a
 *   successful login) that's fine — the "previous fingerprints" set is
 *   recomputed at login time, and an absent set just means "first login ever",
 *   which doesn't fire the alert.
 */
class DeviceFingerprintService
{
    /**
     * Compute the fingerprint from the request signals. Returns null only if
     * we can't extract ANY identifying info (extremely unlikely in practice).
     */
    public function fingerprintFromRequest(Request $request): string
    {
        $platform = $request->attributes->get('client_platform');
        $platform = is_string($platform) ? $platform : 'unknown';

        // Truncate the UA — full Chrome UAs run 200+ chars and contain
        // floating values (build numbers) that defeat stability. The first
        // 80 chars capture engine + major version, which is what we want.
        $ua = substr((string) $request->userAgent(), 0, 80);

        $ip = (string) $request->ip();

        return hash('sha256', $platform . '|' . $ua . '|' . $ip);
    }

    /**
     * Has this user signed in from this fingerprint before? We look across all
     * refresh-token rows (active OR used) and exclude the one we're about to
     * mint by comparing on the hash.
     */
    public function isKnownForUser(User $user, string $fingerprint): bool
    {
        return RefreshToken::query()
            ->where('user_id', $user->id)
            ->where('device_fingerprint', $fingerprint)
            ->exists();
    }

    /**
     * Human-readable label for the device, derived from platform + UA.
     * Used by SecurityAlertNotification to render a friendly line.
     */
    public function labelFromRequest(Request $request): string
    {
        $platform = $request->attributes->get('client_platform');
        $platform = is_string($platform) ? $platform : 'unknown';

        $ua = (string) $request->userAgent();
        $short = $this->shortBrowser($ua);

        return $short === '' ? $platform : ($platform . ' / ' . $short);
    }

    private function shortBrowser(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Firefox/') => 'Firefox',
            // Chrome arm matched first; if we reach here Chrome/ isn't in the UA, so Safari/ alone is enough.
            str_contains($ua, 'Safari/') => 'Safari',
            str_contains($ua, 'Flutter') => 'Flutter',
            str_contains($ua, 'Dart') => 'Dart',
            default => '',
        };
    }
}
