<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->json('personal_info')->nullable();
            $table->json('location')->nullable();
            $table->json('notifications')->nullable();
            $table->json('additional_info')->nullable();
            $table->timestamp('last_saved_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });

        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained('resumes')->onDelete('cascade');
            $table->string('position');
            $table->string('company_name');
            $table->string('company_industry')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index('resume_id');
            $table->index('company_industry');
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained('resumes')->onDelete('cascade');
            $table->string('skill_name');
            $table->timestamps();

            $table->index(['resume_id', 'skill_name']);
        });

        Schema::create('email_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('code');
            $table->timestamp('code_expires_at');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verifications');
        Schema::dropIfExists('skills');
        Schema::dropIfExists('experiences');
        Schema::dropIfExists('resumes');
    }
};
