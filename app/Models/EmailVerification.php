<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    protected $fillable = [
        'email',
        'code',
        'code_expires_at',
        'is_verified',
        'verified_at',
    ];

    protected $casts = [
        'code_expires_at' => 'datetime',
        'verified_at'     => 'datetime',
        'is_verified'     => 'boolean',
    ];

    public static function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function isCodeValid(): bool
    {
        return $this->code_expires_at->isFuture();
    }

    public function verifyCode(string $code): bool
    {
        if ($this->is_verified || !$this->isCodeValid() || $this->code !== $code) {
            return false;
        }

        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        return true;
    }
}
