<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Removes the (blocker → blocked) pivot row. Silent on miss so DELETE
 * stays idempotent for clients that retry on network errors.
 */
class UnblockUserAction
{
    public function execute(User $blocker, User $blocked): void
    {
        DB::table('user_blocks')
            ->where('blocker_id', $blocker->id)
            ->where('blocked_id', $blocked->id)
            ->delete();
    }
}
