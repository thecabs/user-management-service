<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('user_id'); // FK -> users.id (uuid)
            $table->string('fingerprint', 191); // hash/device fp
            $table->string('device_name', 191)->nullable(); // ex: iPhone 12
            $table->string('platform', 100)->nullable();     // ex: iOS/Android/Web

            $table->boolean('is_trusted')->default(true);
            $table->enum('trust_level', ['low', 'medium', 'high'])->default('low');
            $table->unsignedSmallInteger('risk_score')->default(0);
            $table->boolean('mfa_verified')->default(false);

            $table->timestamp('first_trusted_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'fingerprint']);
            $table->index(['user_id', 'last_seen_at']);
            $table->index('trust_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');
    }
};
