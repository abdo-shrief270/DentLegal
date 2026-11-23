<?php

namespace Tests;

use App\Modules\Identity\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected string $seeder = RoleSeeder::class;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['X-App-Version' => '1.0.0 (1)', 'Accept' => 'application/json']);
    }

    protected function actingAsDentist(?User $dentist = null): User
    {
        $dentist ??= User::factory()->dentist()->create();
        Sanctum::actingAs($dentist, ['dentist']);

        return $dentist;
    }
}
