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
            DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('pending','screening','interview','hired','rejected','withdrawn') NOT NULL DEFAULT 'pending'");
        } else {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropColumn('status');
            });
            Schema::table('applications', function (Blueprint $table) {
                $table->string('status')->default('pending')->after('cover_letter');
            });
        }

        Schema::create('application_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->constrained('users');
            $table->string('actor_role');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_history');

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('pending','screening','interview','hired','rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};
