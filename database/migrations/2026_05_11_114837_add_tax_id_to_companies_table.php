<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->enum('business_type', ['legal', 'individual'])
                  ->default('legal')
                  ->after('name');

            $table->string('edrpou', 8)->nullable()->after('business_type');
            $table->string('ipn', 10)->nullable()->after('edrpou');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['business_type', 'edrpou', 'ipn']);
        });
    }
};
