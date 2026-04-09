<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration')->default(60); // minutes
            $table->string('type');  // video, phone, in_person, other
            $table->string('meeting_link')->nullable();
            $table->string('office_address')->nullable();
            $table->text('notes')->nullable();          // visible to candidate
            $table->text('internal_notes')->nullable(); // internal only
            $table->string('status')->default('scheduled'); // scheduled, confirmed, rescheduled, cancelled
            $table->string('confirm_token')->unique();
            $table->string('cancelled_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('application_id');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
