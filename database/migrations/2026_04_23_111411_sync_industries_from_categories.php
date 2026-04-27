<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS=0'); }
        DB::table('industry_subsectors')->truncate();
        DB::table('industries')->truncate();
        if (DB::getDriverName() === 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS=1'); }

        $now = now();

        $categories = DB::table('categories')->orderBy('position')->get();

        foreach ($categories as $category) {
            $industryId = DB::table('industries')->insertGetId([
                'name'       => $category->name,
                'slug'       => $category->slug,
                'position'   => $category->position,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $positions = DB::table('positions')
                ->where('category_id', $category->id)
                ->get();

            $rows = $positions->map(fn(object $p) => [
                'industry_id' => $industryId,
                'name'        => $p->name,
                'slug'        => $p->slug,
                'created_at'  => $now,
                'updated_at'  => $now,
            ])->toArray();

            if (!empty($rows)) {
                DB::table('industry_subsectors')->insert($rows);
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS=0'); }
        DB::table('industry_subsectors')->truncate();
        DB::table('industries')->truncate();
        if (DB::getDriverName() === 'mysql') { DB::statement('SET FOREIGN_KEY_CHECKS=1'); }
    }
};
