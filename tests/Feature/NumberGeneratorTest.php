<?php

namespace Tests\Feature;

use App\Services\NumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_and_draft_sequences_are_daily_and_padded(): void
    {
        $generator = app(NumberGenerator::class);
        $this->assertSame('INV-20260921-0001', $generator->invoice('2026-09-21'));
        $this->assertSame('INV-20260921-0002', $generator->invoice('2026-09-21'));
        $this->assertSame('DRF-20260921-001', $generator->draft('2026-09-21'));
        $this->assertSame('INV-20260922-0001', $generator->invoice('2026-09-22'));
    }
}
