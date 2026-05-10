<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employer_user_id')->constrained('users');
            $table->json('questions');
            $table->timestamp('deadline_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index('application_id');
            $table->index(['status', 'deadline_at']);
        });

        Schema::create('interview_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('answers');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique('interview_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_responses');
        Schema::dropIfExists('interview_requests');
    }
};
