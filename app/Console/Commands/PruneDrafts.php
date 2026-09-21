<?php

namespace App\Console\Commands;

use App\Services\SaleService;
use Illuminate\Console\Command;

class PruneDrafts extends Command
{
    protected $signature = 'drafts:prune';
    protected $description = 'Membuang draft yang sudah kedaluwarsa dan melepas kunci basi';
    public function handle(SaleService $service): int { $this->info($service->pruneExpired().' draft kedaluwarsa diproses.'); return self::SUCCESS; }
}
