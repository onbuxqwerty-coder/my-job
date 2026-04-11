<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->boolean('is_top')->default(false)->after('is_featured');
            $table->index(['is_top', 'category_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropIndex(['is_top', 'category_id', 'published_at']);
            $table->dropColumn('is_top');
        });
    }
};
