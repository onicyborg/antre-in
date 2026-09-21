<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    use HasUuids;
    protected $fillable = ['user_id','action','table_name','record_id','method','url','ip_address','old_values','new_values'];
    protected function casts(): array { return ['old_values'=>'array','new_values'=>'array']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
