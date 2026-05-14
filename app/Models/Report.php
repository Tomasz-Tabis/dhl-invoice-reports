<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'invoice_upload_id',
        'original_pdf_path',
        'original_pdf_filename',
        'generated_pdf_path',
        'week_number',
        'year',
        'selected_drivers',
    ];

    protected function casts(): array
    {
        return [
            'selected_drivers' => 'array',
            'week_number' => 'integer',
            'year' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoiceUpload(): BelongsTo
    {
        return $this->belongsTo(InvoiceUpload::class);
    }
}
