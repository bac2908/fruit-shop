<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchHistory extends Model
{
    protected $fillable = [
        'user_id',
        'keyword',
        'keyword_normalized',
        'results_count',
        'last_searched_at',
    ];

    protected $casts = [
        'results_count' => 'integer',
        'last_searched_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
