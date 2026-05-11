<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->string('publication_type')
                  ->default('standard')
                  ->after('status');

            $table->string('anonymous_name')
                  ->nullable()
                  ->after('publication_type');

            $table->boolean('auto_refresh')
                  ->default(false)
                  ->after('anonymous_name');

            $table->timestamp('auto_refresh_until')
                  ->nullable()
                  ->after('auto_refresh');
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropColumn([
                'publication_type',
                'anonymous_name',
                'auto_refresh',
                'auto_refresh_until',
            ]);
        });
    }
};
