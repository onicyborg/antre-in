<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['sale_id','product_id','product_name','sku','unit_name','quantity','unit_price','cost_price','subtotal'];
    protected function casts(): array { return ['quantity'=>'integer','unit_price'=>'integer','cost_price'=>'integer','subtotal'=>'integer']; }
    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
