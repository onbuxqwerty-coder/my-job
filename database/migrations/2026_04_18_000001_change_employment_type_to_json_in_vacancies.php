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
        // Step 1: add temporary JSON column
        Schema::table('vacancies', function (Blueprint $table) {
            $table->json('employment_type_new')->nullable()->after('employment_type');
        });

        // Step 2: migrate data row by row
        DB::table('vacancies')->whereNotNull('employment_type')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                DB::table('vacancies')->where('id', $row->id)->update([
                    'employment_type_new' => json_encode([$row->employment_type]),
                ]);
            }
        });

        // Step 3: drop old enum column, rename new one
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropColumn('employment_type');
        });

        Schema::table('vacancies', function (Blueprint $table) {
            $table->renameColumn('employment_type_new', 'employment_type');
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->string('employment_type_old')->nullable()->after('employment_type');
        });

        DB::table('vacancies')->whereNotNull('employment_type')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $arr = json_decode($row->employment_type, true);
                DB::table('vacancies')->where('id', $row->id)->update([
                    'employment_type_old' => $arr[0] ?? null,
                ]);
            }
        });

        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropColumn('employment_type');
        });

        Schema::table('vacancies', function (Blueprint $table) {
            $table->renameColumn('employment_type_old', 'employment_type');
        });
    }
};
