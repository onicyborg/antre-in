<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NumberSequence extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['type', 'date', 'last_number'];
    protected function casts(): array { return ['date' => 'date', 'last_number' => 'integer']; }
}
