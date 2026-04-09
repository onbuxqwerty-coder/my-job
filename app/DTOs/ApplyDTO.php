<?php

declare(strict_types=1);

namespace App\DTOs;

final class ApplyDTO
{
    public function __construct(
        public readonly string $resumeUrl,
        public readonly ?string $coverLetter = null,
    ) {}
}
