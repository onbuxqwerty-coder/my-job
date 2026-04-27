<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('published_at');
            $table->string('status', 32)->default('draft')->after('expires_at');
            $table->timestamp('expiry_notification_sent_at')->nullable()->after('status');

            $table->index(['status', 'expires_at'], 'vacancies_status_expires_idx');
            $table->index('published_at', 'vacancies_published_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropIndex('vacancies_status_expires_idx');
            $table->dropIndex('vacancies_published_at_idx');

            $table->dropColumn([
                'expires_at',
                'status',
                'expiry_notification_sent_at',
            ]);
        });
    }
};
