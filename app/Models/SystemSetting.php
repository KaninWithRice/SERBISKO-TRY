<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSetting extends Model
{
    // 1. The Fillable Array (Safety)
    protected $fillable = [
        'active_spreadsheet_id',
        'active_sheet_range',
        'active_school_year',
        'edit_form_url',    
        'public_form_url',
        'last_updated_by',
    ];

    // 2. The Relationship (Accountability)
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }
}