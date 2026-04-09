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
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->timestamp('featured_until')->nullable()->after('is_featured');
            $table->index(['is_featured', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropIndex(['is_featured', 'published_at']);
            $table->dropColumn(['is_featured', 'featured_until']);
        });
    }
};
