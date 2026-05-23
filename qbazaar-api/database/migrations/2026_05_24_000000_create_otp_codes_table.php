<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One-time passwords (phone verification + later 2FA).
     *
     * Why a dedicated table instead of overloading password_reset_tokens?
     *  - OTPs are tied to a phone (not an email) and have their own throttling
     *    semantics (per-phone, per-hour ceiling, attempt counter).
     *  - We hash the code at rest so a leaked dump can't be replayed.
     *  - `attempts` lets us burn the row after N failed verifications without
     *    pinging Cache for every wrong guess.
     *  - `used_at` is the soft-burn timestamp: a row is "active" only when
     *    `used_at IS NULL AND expires_at > now()`.
     */
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('phone');
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            // Hot lookup: "find the currently-active OTP for this phone".
            $table->index(['phone', 'used_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
