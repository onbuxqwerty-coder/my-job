<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('message_templates')->nullOnDelete();
            $table->string('type'); // invitation, message, offer, rejection
            $table->string('subject');
            $table->text('body');
            $table->string('status')->default('sent'); // sent, delivered, failed
            $table->boolean('copy_to_sender')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_messages');
    }
};
