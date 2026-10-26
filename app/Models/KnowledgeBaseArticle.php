<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBaseArticle extends Model
{
    protected $fillable = [
        'key',
        'category',
        'title',
        'summary',
        'steps',
    ];

    protected function casts(): array
    {
        return ['steps' => 'array'];
    }
}
