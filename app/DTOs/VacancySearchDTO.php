<?php

declare(strict_types=1);

namespace App\DTOs;

final class VacancySearchDTO
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?int    $categoryId = null,
        public readonly array   $employmentTypes = [],
        public readonly ?int    $salaryMin = null,
        public readonly ?int    $salaryMax = null,
        public readonly array   $languages = [],
        public readonly array   $suitability = [],
        public readonly ?int    $cityId = null,
        public readonly ?int    $companyId = null,
        public readonly int     $perPage = 10,
    ) {}
}
