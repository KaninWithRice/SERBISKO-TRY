<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncConflict extends Model {
    protected $guarded = [];

    protected $casts = [
        'existing_data_json' => 'array',
        'incoming_data_json' => 'array',
        'raw_sheet_row'      => 'array', // 1. ADD: Cast the raw row so it's treated as an array
        'resolved_at'        => 'datetime', // Good practice for date handling
    ];

    public function existingUser() {
        return $this->belongsTo(User::class, 'existing_user_id');
    }

    // 2. REFINED: Access the student through the user relationship properly
    public function existingStudent()
    {
        return $this->hasOneThrough(
            Student::class, 
            User::class, 
            'id',           // Foreign key on users table...
            'user_id',      // Foreign key on students table...
            'existing_user_id', // Local key on sync_conflicts table...
            'id'            // Local key on users table...
        );
    }

    public function resolver() {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // --- SCOPES ---
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d');
    }
}