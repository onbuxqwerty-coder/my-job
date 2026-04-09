<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // invitation, message, offer, rejection
            $table->string('subject');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
