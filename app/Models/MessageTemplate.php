<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MessageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'type',
        'subject',
        'body',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type'      => MessageType::class,
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Replace template variables with actual values.
     *
     * @param array<string, string> $vars
     */
    public function render(array $vars): string
    {
        return self::replaceVars($this->body, $vars);
    }

    /**
     * @param array<string, string> $vars
     */
    public static function replaceVars(string $text, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }

        return $text;
    }
}
