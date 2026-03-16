<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreEnrollment extends Model
{
    protected $fillable = ['student_id', 'responses', 'status'];

    protected $casts = [
        'responses' => 'array', // Important for the JSON column
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}