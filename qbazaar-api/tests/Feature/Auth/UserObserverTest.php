<?php

declare(strict_types=1);

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('logs a signed_up activity on user creation', function (): void {
    $user = User::factory()->create();

    $row = Activity::query()
        ->where('subject_id', $user->id)
        ->where('event', 'signed_up')
        ->first();

    expect($row)->not->toBeNull()
        ->and($row->log_name)->toBe('user')
        ->and($row->properties->get('account_type'))->toBe($user->account_type->value);
});

it('logs a status_changed activity with old/new properties', function (): void {
    $user = User::factory()->create(['status' => UserStatus::ACTIVE->value]);

    $user->forceFill(['status' => UserStatus::SUSPENDED->value])->save();

    $row = Activity::query()
        ->where('subject_id', $user->id)
        ->where('event', 'status_changed')
        ->latest('id')
        ->first();

    expect($row)->not->toBeNull()
        ->and($row->properties->get('old'))->toBe(UserStatus::ACTIVE->value)
        ->and($row->properties->get('new'))->toBe(UserStatus::SUSPENDED->value);
});

it('logs a password_changed activity without leaking the hash', function (): void {
    $user = User::factory()->create();

    $user->forceFill(['password' => Hash::make('Brand!New1234')])->save();

    $row = Activity::query()
        ->where('subject_id', $user->id)
        ->where('event', 'password_changed')
        ->latest('id')
        ->first();

    expect($row)->not->toBeNull();

    // Defence-in-depth: the password hash MUST NOT be in the properties.
    $props = (array) $row->properties->toArray();
    expect(json_encode($props))
        ->not->toContain('Brand!New1234')
        ->and(array_key_exists('old', $props))->toBeFalse()
        ->and(array_key_exists('new', $props))->toBeFalse();
});

it('logs an email_changed activity with the old + new addresses', function (): void {
    $user = User::factory()->create(['email' => 'old@example.qa']);

    $user->forceFill(['email' => 'new@example.qa'])->save();

    $row = Activity::query()
        ->where('subject_id', $user->id)
        ->where('event', 'email_changed')
        ->latest('id')
        ->first();

    expect($row)->not->toBeNull()
        ->and($row->properties->get('old'))->toBe('old@example.qa')
        ->and($row->properties->get('new'))->toBe('new@example.qa');
});

it('logs a deleted activity on soft delete', function (): void {
    $user = User::factory()->create();

    $user->delete();

    $row = Activity::query()
        ->where('subject_id', $user->id)
        ->where('event', 'deleted')
        ->latest('id')
        ->first();

    expect($row)->not->toBeNull();
});

it('records one activity row per touched field, not per save call', function (): void {
    $user = User::factory()->create();

    // Touch both email and phone in a single save — we expect TWO separate
    // activity rows, one per field, plus none for the unchanged ones.
    $user->forceFill([
        'email' => 'multi@example.qa',
        'phone' => '+97455999999',
    ])->save();

    $emailRows = Activity::query()
        ->where('subject_id', $user->id)
        ->where('event', 'email_changed')
        ->count();
    $phoneRows = Activity::query()
        ->where('subject_id', $user->id)
        ->where('event', 'phone_changed')
        ->count();

    expect($emailRows)->toBe(1)
        ->and($phoneRows)->toBe(1);
});
