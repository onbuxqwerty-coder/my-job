<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactRole;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'name', 'contact', 'role', 'topic', 'message', 'is_read',
    ];

    protected $casts = [
        'role'    => ContactRole::class,
        'is_read' => 'boolean',
    ];
}
