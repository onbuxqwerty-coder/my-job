<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('pending','screening','interview','hired','rejected','withdrawn','viewed','reviewing','offered') NOT NULL DEFAULT 'pending'");
        }

        Schema::create('application_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_logs');

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('pending','screening','interview','hired','rejected','withdrawn') NOT NULL DEFAULT 'pending'");
        }
    }
};
