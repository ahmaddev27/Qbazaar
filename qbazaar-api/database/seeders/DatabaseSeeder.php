<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Reference data (Sprint 3) — public taxonomy + Qatar locations.
        // Seeded first so factories in later seeders/tests can reference categories.
        $this->call([
            CategorySeeder::class,
            LocationSeeder::class,
            // Sprint 11 — admin RBAC + DB-backed moderation rules.
            // Roles must exist before the admin user is granted super_admin,
            // and moderation rules must be present so the publish hot path
            // hits a populated table instead of a slow config-fallback.
            RolesAndPermissionsSeeder::class,
            ModerationRulesSeeder::class,
            // Sprint 12 — CMS pages + Help center seed data.
            PageSeeder::class,
            HelpSeeder::class,
            // Demo data (users / ads / convos / offers / favorites / reports /
            // tickets) — populates the dev site so designers + QA always see a
            // realistic shape. Last so it can reference categories + locations
            // + RBAC roles.
            DemoDataSeeder::class,
        ]);

        User::factory()->create([
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+97455000001',
        ]);
    }
}
