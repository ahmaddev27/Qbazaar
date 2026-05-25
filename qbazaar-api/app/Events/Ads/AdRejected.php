<?php

declare(strict_types=1);

namespace App\Events\Ads;

use App\Data\Moderation\ModerationResult;
use App\Models\Ad;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when auto-moderation flags a draft at publish time. The ad transitions
 * to PENDING (awaiting manual review) and the seller receives a notification
 * listing the rule families that fired.
 *
 * The full {@see ModerationResult} rides the event so listeners (notification,
 * activity log) can render the rejection reasons without re-running the rules.
 */
class AdRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Ad $ad,
        public readonly ModerationResult $result,
    ) {}

    /**
     * Convenience accessor — listeners only ever need the flag list.
     *
     * @return list<string>
     */
    public function flags(): array
    {
        return $this->result->flags;
    }
}
