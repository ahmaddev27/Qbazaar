<?php

declare(strict_types=1);

namespace App\Data\Moderation;

use App\Actions\Ads\ModerateAdAction;
use Spatie\LaravelData\Data;

/**
 * Outcome of running an ad through {@see ModerateAdAction}.
 *
 * Carries:
 *  - `clean`   — true when no rule fired. The caller can publish straight to ACTIVE.
 *  - `flags`   — distinct rule identifiers that fired (`banned_words`, `phone`,
 *                `external_link`). The frontend uses this to render the
 *                rejection-reason chips on the seller's draft.
 *  - `details` — per-flag specifics (matched words, URLs). Logged into the
 *                activity-log row for later review; not surfaced verbatim to
 *                the seller (we paraphrase instead).
 *
 * Kept immutable + serialisable so it can ride inside the AdRejected event
 * payload without leaking the original Ad model.
 */
class ModerationResult extends Data
{
    /**
     * @param list<string> $flags
     * @param array<string, mixed> $details
     */
    public function __construct(
        public bool $clean,
        public array $flags,
        public array $details,
    ) {}

    public static function clean(): self
    {
        return new self(true, [], []);
    }

    /**
     * @param list<string> $flags
     * @param array<string, mixed> $details
     */
    public static function rejected(array $flags, array $details): self
    {
        return new self(false, $flags, $details);
    }
}
