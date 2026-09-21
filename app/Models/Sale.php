<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['status','draft_number','invoice_number','label','user_id','completed_by','locked_by','locked_at','subtotal','discount_type','discount_value','discount_amount','tax_percent','tax_amount','total','payment_method','paid_amount','change_amount','payment_reference','note','drafted_at','completed_at','voided_at','voided_by','void_reason','discarded_at','discard_reason'];
    protected function casts(): array { return ['status' => SaleStatus::class, 'payment_method' => PaymentMethod::class, 'tax_percent' => 'decimal:2', 'subtotal'=>'integer','discount_value'=>'integer','discount_amount'=>'integer','tax_amount'=>'integer','total'=>'integer','paid_amount'=>'integer','change_amount'=>'integer','locked_at'=>'datetime','drafted_at'=>'datetime','completed_at'=>'datetime','voided_at'=>'datetime','discarded_at'=>'datetime']; }
    public function items(): HasMany { return $this->hasMany(SaleItem::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function completedBy(): BelongsTo { return $this->belongsTo(User::class, 'completed_by'); }
    public function lockedBy(): BelongsTo { return $this->belongsTo(User::class, 'locked_by'); }
    public function voidedBy(): BelongsTo { return $this->belongsTo(User::class, 'voided_by'); }
    public function statusHistories(): HasMany { return $this->hasMany(SaleStatusHistory::class)->oldest(); }
}
