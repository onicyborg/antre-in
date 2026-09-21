<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['store_name', 'address', 'phone', 'receipt_footer', 'tax_percent', 'draft_expire_hours', 'max_active_drafts'];
    protected function casts(): array { return ['tax_percent' => 'decimal:2', 'draft_expire_hours' => 'integer', 'max_active_drafts' => 'integer']; }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'store_name' => 'antre-in',
            'receipt_footer' => 'Terima kasih atas kunjungan Anda',
            'tax_percent' => 0,
            'draft_expire_hours' => 24,
            'max_active_drafts' => 20,
        ]);
    }
}
