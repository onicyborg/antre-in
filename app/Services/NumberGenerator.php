<?php

namespace App\Services;

use App\Models\NumberSequence;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class NumberGenerator
{
    public function invoice(CarbonInterface|string|null $date = null): string { return $this->next('invoice', $date, 'INV', 4); }
    public function draft(CarbonInterface|string|null $date = null): string { return $this->next('draft', $date, 'DRF', 3); }
    public function generateInvoice(CarbonInterface|string|null $date = null): string { return $this->invoice($date); }
    public function generateDraft(CarbonInterface|string|null $date = null): string { return $this->draft($date); }
    public function invoiceNumber(CarbonInterface|string|null $date = null): string { return $this->invoice($date); }
    public function draftNumber(CarbonInterface|string|null $date = null): string { return $this->draft($date); }

    private function next(string $type, CarbonInterface|string|null $date, string $prefix, int $width): string
    {
        $day = $date instanceof CarbonInterface ? $date->copy() : ($date ? now()->parse($date) : now());
        $dateString = $day->toDateString();
        return DB::transaction(function () use ($type, $dateString, $prefix, $width, $day): string {
            $sequence = NumberSequence::query()->where('type', $type)->whereDate('date', $dateString)->lockForUpdate()->first();
            if (!$sequence) {
                try { $sequence = NumberSequence::create(['type'=>$type,'date'=>$dateString,'last_number'=>0]); }
                catch (QueryException) { $sequence = NumberSequence::query()->where('type',$type)->whereDate('date',$dateString)->lockForUpdate()->firstOrFail(); }
            }
            $sequence->increment('last_number');
            return $prefix.'-'.$day->format('Ymd').'-'.str_pad((string) $sequence->fresh()->last_number, $width, '0', STR_PAD_LEFT);
        });
    }
}
