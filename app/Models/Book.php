<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'authors',
        'cover_image_url',
        'description',
        'page_count',
        'published_date',
        'main_category',
        'status',
        'price',
        'purchase_date',
        'start_reading_date',
        'finish_reading_date',
        'reading_progress',
        'personal_notes',
        'google_books_id',
        'average_rating',
        'personal_rating',
    ];

    protected $casts = [
        'authors' => 'array',
        'published_date' => 'date',
        'purchase_date' => 'date',
        'start_reading_date' => 'date',
        'finish_reading_date' => 'date',
        'average_rating' => 'decimal:1',
        'personal_rating' => 'decimal:1',
    ];

    protected function price(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $value !== null ? $value / 100 : null;
            },
            set: function ($value) {
                return $value !== null ? $value * 100 : null;
            }
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
