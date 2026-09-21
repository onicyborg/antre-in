<?php

namespace Tests\Feature;

use App\Enums\SaleStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function completedSale(User $user, Product $product, int $quantity=2): Sale
    {
        $response=$this->actingAs($user)->postJson(route('pos.checkout'),['items'=>[['product_id'=>$product->id,'quantity'=>$quantity]],'discount_type'=>'nominal','discount_value'=>2000,'payment_method'=>'cash','paid_amount'=>30000]); return Sale::findOrFail($response->json('data.id'));
    }

    public function test_sales_and_products_report_use_completed_only_and_calculate_profit(): void
    {
        $admin=User::factory()->admin()->create(); $category=Category::factory()->create(); $unit=Unit::factory()->create(); $product=Product::factory()->create(['category_id'=>$category->id,'unit_id'=>$unit->id,'cost_price'=>5000,'sell_price'=>15000,'stock'=>10]); $sale=$this->completedSale($admin,$product); Sale::factory()->create(['user_id'=>$admin->id,'status'=>SaleStatus::Draft,'drafted_at'=>now()]);
        $response=$this->actingAs($admin)->get(route('reports.sales',['start_date'=>now()->toDateString(),'end_date'=>now()->toDateString()])); $response->assertOk()->assertViewIs('reports.sales'); $rows=$response->viewData('rows'); $this->assertCount(1,$rows); $this->assertSame(28000,(int)$rows->first()->total); $this->assertSame(18000,(int)$response->viewData('profit')->first());
        $response=$this->actingAs($admin)->get(route('reports.products',['start_date'=>now()->toDateString(),'end_date'=>now()->toDateString()])); $response->assertOk(); $row=$response->viewData('rows')->first(); $this->assertSame(2,(int)$row->quantity); $this->assertSame(20000,(int)$row->gross_profit);
    }

    public function test_draft_report_counts_statuses_and_duration_metrics(): void
    {
        $admin=User::factory()->admin()->create(); $now=now(); Sale::factory()->create(['user_id'=>$admin->id,'status'=>SaleStatus::Completed,'draft_number'=>'DRF-C','drafted_at'=>now()->subMinutes(60),'completed_at'=>now()]); Sale::factory()->create(['user_id'=>$admin->id,'status'=>SaleStatus::Discarded,'draft_number'=>'DRF-M','drafted_at'=>now(),'discard_reason'=>'manual','discarded_at'=>now()]); Sale::factory()->create(['user_id'=>$admin->id,'status'=>SaleStatus::Discarded,'draft_number'=>'DRF-E','drafted_at'=>now(),'discard_reason'=>'expired','discarded_at'=>now()]); Sale::factory()->create(['user_id'=>$admin->id,'status'=>SaleStatus::Draft,'draft_number'=>'DRF-A','drafted_at'=>now()]);
        $response=$this->actingAs($admin)->get(route('reports.drafts',['start_date'=>$now->toDateString(),'end_date'=>$now->toDateString()])); $response->assertOk(); $kpi=$response->viewData('kpi'); $this->assertSame(4,$kpi['created']); $this->assertSame(1,$kpi['completed']); $this->assertSame(1,$kpi['discarded_manual']); $this->assertSame(1,$kpi['expired']); $this->assertSame(1,$kpi['active']); $this->assertSame(60.0,$kpi['average']); $this->assertSame(60.0,$kpi['median']);
    }

    public function test_stock_report_and_cashier_dashboard_are_scoped(): void
    {
        $admin=User::factory()->admin()->create(); $kasir=User::factory()->kasir()->create(); $category=Category::factory()->create(); $unit=Unit::factory()->create(); Product::factory()->create(['category_id'=>$category->id,'unit_id'=>$unit->id,'stock'=>2,'min_stock'=>3,'cost_price'=>4000]); $this->actingAs($admin)->get(route('reports.stock'))->assertOk()->assertViewIs('reports.stock')->assertViewHas('lowCount',1); $this->actingAs($kasir)->get(route('dashboard'))->assertOk()->assertViewHas('isAdmin',false);
    }
}
