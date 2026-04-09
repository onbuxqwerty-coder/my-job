<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix ApplicationStatus enum
        Schema::table('applications', function (Blueprint $table) {
            $table->enum('status', ['pending', 'screening', 'interview', 'hired', 'rejected'])
                ->default('pending')
                ->change();
        });

        // Add is_verified to companies
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('location');
        });

        // Add position to categories
        Schema::table('categories', function (Blueprint $table) {
            $table->integer('position')->default(0)->after('icon');
        });

        // Add SoftDeletes to vacancies
        Schema::table('vacancies', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add SoftDeletes to companies
        Schema::table('companies', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->enum('status', ['pending', 'accepted', 'rejected', 'hired'])
                ->default('pending')
                ->change();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['is_verified']);
            $table->dropSoftDeletes();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('position');
        });

        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
