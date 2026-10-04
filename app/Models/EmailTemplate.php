<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailTemplate extends Model
{
    use HasAgency, HasFactory, SoftDeletes;

    protected $fillable = [
        'agency_id',
        'name',
        'slug',
        'subject',
        'category',
        'html_content',
        'plain_text_content',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Render the template with the given variables, replacing {{ var }} placeholders
     * in both subject and content. Returns ['subject' => ..., 'html' => ..., 'text' => ...].
     *
     * @param  array<string, mixed>  $variables
     * @return array{subject: string, html: string, text: string}
     */
    public function render(array $variables = []): array
    {
        $replace = fn (string $template): string => preg_replace_callback(
            '/{{\s*([a-zA-Z0-9_.]+)\s*}}/',
            fn (array $m) => (string) ($variables[$m[1]] ?? ''),
            $template ?? ''
        );

        return [
            'subject' => $replace($this->subject ?? ''),
            'html' => $replace($this->html_content ?? ''),
            'text' => $replace($this->plain_text_content ?? ''),
        ];
    }
}
