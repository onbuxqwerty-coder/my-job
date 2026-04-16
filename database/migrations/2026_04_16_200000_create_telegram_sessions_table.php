<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('session_token', 64)->unique()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->bigInteger('telegram_id')->nullable();
            $table->string('phone', 20)->nullable();
            $table->enum('status', ['pending', 'authorized', 'expired'])->default('pending');
            $table->string('login_token', 64)->nullable()->unique();
            $table->string('role', 20)->default('candidate');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_sessions');
    }
};
