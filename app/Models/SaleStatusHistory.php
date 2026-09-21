<?php

namespace App\Models;

use App\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleStatusHistory extends Model
{
    use HasUuids;
    protected $fillable = ['sale_id', 'from_status', 'to_status', 'user_id', 'note'];
    protected function casts(): array { return ['from_status'=>SaleStatus::class, 'to_status'=>SaleStatus::class]; }
    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
