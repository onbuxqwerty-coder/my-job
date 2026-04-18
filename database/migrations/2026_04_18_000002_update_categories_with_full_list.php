<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $categories = [
            1  => 'IT, комп\'ютери, інтернет',
            2  => 'Адміністрація, керівництво середньої ланки',
            3  => 'Будівництво, архітектура',
            4  => 'Бухгалтерія, аудит',
            5  => 'Готельно-ресторанний бізнес, туризм',
            6  => 'Дизайн, творчість',
            7  => 'ЗМІ, видавництво, поліграфія',
            8  => 'Краса, фітнес, спорт',
            9  => 'Культура, музика, шоу-бізнес',
            10 => 'Логістика, склад',
            11 => 'Маркетинг, реклама',
            12 => 'Медицина, фармацевтика',
            13 => 'Нерухомість',
            14 => 'Освіта, наука',
            15 => 'Охорона, безпека',
            16 => 'Продаж, закупівля',
            17 => 'Робочі спеціальності, виробництво',
            18 => 'Роздрібна торгівля',
            19 => 'Секретаріат, діловодство',
            20 => 'Сільське господарство, агробізнес',
            21 => 'Страхування',
            22 => 'Сфера обслуговування',
            23 => 'Телекомунікації та зв\'язок',
            24 => 'Топменеджмент, керівництво вищої ланки',
            25 => 'Транспорт, автобізнес',
            26 => 'Управління персоналом, HR',
            27 => 'Фінанси, банк',
            28 => 'Юриспруденція',
        ];

        // Map old IDs to new positions/names (preserves vacancy foreign keys)
        $oldToNew = [
            1 => ['name' => 'IT, комп\'ютери, інтернет',  'position' => 1],
            2 => ['name' => 'Продаж, закупівля',           'position' => 16],
            3 => ['name' => 'Маркетинг, реклама',          'position' => 11],
            4 => ['name' => 'Медицина, фармацевтика',      'position' => 12],
            5 => ['name' => 'Будівництво, архітектура',    'position' => 3],
            6 => ['name' => 'Фінанси, банк',               'position' => 27],
            7 => ['name' => 'Освіта, наука',               'position' => 14],
            8 => ['name' => 'Логістика, склад',            'position' => 10],
            9 => ['name' => 'Юриспруденція',               'position' => 28],
           10 => ['name' => 'Дизайн, творчість',           'position' => 6],
        ];

        foreach ($oldToNew as $id => $data) {
            DB::table('categories')->where('id', $id)->update([
                'name'       => $data['name'],
                'slug'       => Str::slug($data['name']),
                'position'   => $data['position'],
                'updated_at' => now(),
            ]);
        }

        // Existing IDs that need updating
        $existingIds = DB::table('categories')->pluck('id')->toArray();

        // Insert new categories (those not already present by position match)
        $newCategories = [
            ['name' => 'Адміністрація, керівництво середньої ланки', 'position' => 2],
            ['name' => 'Бухгалтерія, аудит',                         'position' => 4],
            ['name' => 'Готельно-ресторанний бізнес, туризм',        'position' => 5],
            ['name' => 'ЗМІ, видавництво, поліграфія',               'position' => 7],
            ['name' => 'Краса, фітнес, спорт',                       'position' => 8],
            ['name' => 'Культура, музика, шоу-бізнес',               'position' => 9],
            ['name' => 'Нерухомість',                                 'position' => 13],
            ['name' => 'Охорона, безпека',                           'position' => 15],
            ['name' => 'Робочі спеціальності, виробництво',          'position' => 17],
            ['name' => 'Роздрібна торгівля',                         'position' => 18],
            ['name' => 'Секретаріат, діловодство',                   'position' => 19],
            ['name' => 'Сільське господарство, агробізнес',          'position' => 20],
            ['name' => 'Страхування',                                'position' => 21],
            ['name' => 'Сфера обслуговування',                       'position' => 22],
            ['name' => 'Телекомунікації та зв\'язок',                'position' => 23],
            ['name' => 'Топменеджмент, керівництво вищої ланки',     'position' => 24],
            ['name' => 'Транспорт, автобізнес',                      'position' => 25],
            ['name' => 'Управління персоналом, HR',                  'position' => 26],
        ];

        foreach ($newCategories as $data) {
            $exists = DB::table('categories')->where('slug', Str::slug($data['name']))->exists();
            if (! $exists) {
                DB::table('categories')->insert([
                    'name'       => $data['name'],
                    'slug'       => Str::slug($data['name']),
                    'position'   => $data['position'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Restore original short names
        $restore = [
            1 => 'IT',         2 => 'Продажі',   3 => 'Маркетинг',
            4 => 'Медицина',   5 => 'Будівництво', 6 => 'Фінанси',
            7 => 'Освіта',     8 => 'Логістика',  9 => 'Право',
           10 => 'Дизайн',
        ];
        foreach ($restore as $id => $name) {
            DB::table('categories')->where('id', $id)->update(['name' => $name, 'slug' => Str::slug($name), 'position' => 0]);
        }
        DB::table('categories')->whereNotIn('id', array_keys($restore))->delete();
    }
};
