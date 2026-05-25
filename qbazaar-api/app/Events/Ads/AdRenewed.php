<?php

declare(strict_types=1);

namespace App\Events\Ads;

use App\Models\Ad;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after `Ad::renew()` resets the expiry window. Used by the activity-log
 * stream + optional confirmation notification.
 */
class AdRenewed
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Ad $ad) {}
}
