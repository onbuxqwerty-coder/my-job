<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('category_id')->constrained();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description');
            $table->integer('salary_from')->nullable();
            $table->integer('salary_to')->nullable();
            $table->string('currency')->default('UAH');
            $table->enum('employment_type', ['full-time', 'part-time', 'remote', 'hybrid', 'contract']);
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancies');
    }
};
