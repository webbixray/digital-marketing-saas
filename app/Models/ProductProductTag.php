<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductProductTag extends Model
{
    protected $table = 'product_product_tag';

    public $timestamps = true;

    protected $fillable = [
        'product_id',
        'product_tag_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(ProductTag::class, 'product_tag_id');
    }
}
