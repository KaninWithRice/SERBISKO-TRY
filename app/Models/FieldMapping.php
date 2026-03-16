<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldMapping extends Model
{
    protected $fillable = [
        'google_header',
        'database_field',
        'display_label',
        'category',
        'priority',
        'is_visible',
        'is_system_core',
        'last_updated_by',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }
}