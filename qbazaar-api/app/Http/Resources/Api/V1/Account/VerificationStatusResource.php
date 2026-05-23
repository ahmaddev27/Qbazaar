<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Account;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Verification snapshot for the signed-in user.
 *
 * `business_verified` and `kyc_status` are placeholders — the underlying
 * business-verification and KYC workflows ship in Phase 2. Returning the
 * fields now keeps the response contract stable so clients don't break when
 * the real data lands.
 *
 * @mixin User
 */
class VerificationStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'email_verified' => (bool) $this->email_verified,
            'phone_verified' => (bool) $this->phone_verified,
            'business_verified' => false, // TODO Phase 2: wire to business-verifications table
            'kyc_status' => 'none',       // TODO Phase 2: wire to kyc_status enum on users
        ];
    }
}
