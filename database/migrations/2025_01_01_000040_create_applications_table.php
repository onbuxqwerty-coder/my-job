<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vacancy_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('resume_url');
            $table->text('cover_letter')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'hired'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
