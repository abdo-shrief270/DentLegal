<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Dashboard TOTP two-factor (PROJECT1_PLAN.md S5.7): mandatory for lawyers and admins. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('two_factor_secret')->nullable();          // encrypted base32 secret
            $table->text('two_factor_recovery_codes')->nullable();  // encrypted list of SHA-256 hashes
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->bigInteger('two_factor_last_step')->nullable(); // last accepted 30 s step: a code works once
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'two_factor_last_step']);
        });
    }
};
