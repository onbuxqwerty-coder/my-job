<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('category')->nullable(); // backend, frontend, design, management
            $table->timestamps();
        });

        Schema::create('candidate_skills', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skill_tags')->cascadeOnDelete();
            $table->tinyInteger('level')->default(1); // 1=beginner, 5=expert
            $table->primary(['user_id', 'skill_id']);
        });

        Schema::create('vacancy_skills', function (Blueprint $table) {
            $table->foreignId('vacancy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skill_tags')->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->primary(['vacancy_id', 'skill_id']);
        });

        Schema::create('vacancy_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vacancy_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->timestamp('calculated_at');
            $table->unique(['user_id', 'vacancy_id']);
            $table->index(['user_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancy_recommendations');
        Schema::dropIfExists('vacancy_skills');
        Schema::dropIfExists('candidate_skills');
        Schema::dropIfExists('skill_tags');
    }
};
