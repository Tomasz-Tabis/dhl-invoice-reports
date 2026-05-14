<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_pdf_path',
        'original_pdf_filename',
        'parsed_data',
        'week_number',
        'year',
    ];

    protected function casts(): array
    {
        return [
            'parsed_data' => 'array',
            'week_number' => 'integer',
            'year' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
