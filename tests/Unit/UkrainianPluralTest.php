<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Vacancy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UkrainianPluralTest extends TestCase
{
    private function pluralize(int $n): string
    {
        $reflection = new \ReflectionClass(Vacancy::class);
        $method     = $reflection->getMethod('pluralizeUk');
        $method->setAccessible(true);

        return $method->invoke(null, $n, 'день', 'дні', 'днів');
    }

    #[DataProvider('pluralCasesProvider')]
    public function test_ukrainian_plural_for_days(int $n, string $expected): void
    {
        $this->assertSame($expected, $this->pluralize($n));
    }

    public static function pluralCasesProvider(): array
    {
        return [
            [0,    'днів'],
            [1,    'день'],
            [2,    'дні'],
            [3,    'дні'],
            [4,    'дні'],
            [5,    'днів'],
            [10,   'днів'],
            [11,   'днів'],  // КРИТИЧНО: 11 → "днів", не "дні"
            [12,   'днів'],
            [14,   'днів'],
            [15,   'днів'],
            [20,   'днів'],
            [21,   'день'],
            [22,   'дні'],
            [25,   'днів'],
            [101,  'день'],
            [111,  'днів'],
            [121,  'день'],
            [1000, 'днів'],
        ];
    }
}
