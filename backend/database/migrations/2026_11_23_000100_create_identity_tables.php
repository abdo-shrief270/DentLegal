<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dentist_profiles', function (Blueprint $table) {
            $table->foreignUuid('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->string('specialty', 80);
            $table->string('city', 60);
            $table->string('facility_name', 160);
            $table->string('scfhs_license_no', 40)->nullable();
            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lawyer_profiles', function (Blueprint $table) {
            $table->foreignUuid('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->string('license_no', 40);
            $table->string('firm_name', 160)->nullable();
            $table->timestamps();
        });

        Schema::create('invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('role', 16);
            $table->string('name')->nullable();
            $table->string('phone_e164', 16)->nullable();
            $table->string('email')->nullable();
            $table->string('token_hash', 64)->nullable()->unique(); // dashboard invites (link → set password)
            $table->foreignUuid('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['phone_e164', 'role']);
        });
        DB::statement("ALTER TABLE invitations ADD CONSTRAINT invitations_role_check CHECK (role IN ('dentist','lawyer','admin'))");
        // At most one open invitation per phone.
        DB::statement('CREATE UNIQUE INDEX invitations_open_phone_unique ON invitations (phone_e164) WHERE accepted_at IS NULL AND revoked_at IS NULL AND phone_e164 IS NOT NULL');

        Schema::create('otp_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('phone_e164', 16);
            $table->string('code_hash', 64);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['phone_e164', 'created_at']);
        });

        Schema::create('devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('token_id')->nullable()->unique(); // the Sanctum token issued on this device
            $table->string('platform', 8);
            $table->string('name', 80)->nullable();
            $table->string('app_version', 32)->nullable();
            $table->text('fcm_token')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->index('user_id');
        });
        DB::statement("ALTER TABLE devices ADD CONSTRAINT devices_platform_check CHECK (platform IN ('ios','android','web'))");

        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 24);
            $table->string('version', 16);
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->index(['user_id', 'type']);
        });
        DB::statement("ALTER TABLE consents ADD CONSTRAINT consents_type_check CHECK (type IN ('terms','privacy','ai_processing','lawyer_access'))");
    }

    public function down(): void
    {
        foreach (['consents', 'devices', 'otp_codes', 'invitations', 'lawyer_profiles', 'dentist_profiles'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
