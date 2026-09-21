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

class VoidTest extends TestCase
{
    use RefreshDatabase;

    private function saleContext(): array
    {
        $admin=User::factory()->admin()->create(); $kasir=User::factory()->kasir()->create(); $other=User::factory()->kasir()->create(); $category=Category::factory()->create(); $unit=Unit::factory()->create(); $product=Product::factory()->create(['category_id'=>$category->id,'unit_id'=>$unit->id,'stock'=>5,'sell_price'=>12000]);
        $response=$this->actingAs($kasir)->postJson(route('pos.checkout'),['items'=>[['product_id'=>$product->id,'quantity'=>2]],'payment_method'=>'cash','paid_amount'=>24000]); return [$admin,$kasir,$other,$product,Sale::findOrFail($response->json('data.id'))];
    }

    public function test_admin_void_returns_stock_and_writes_history(): void
    {
        [$admin,$kasir,$other,$product,$sale]=$this->saleContext(); $this->actingAs($admin)->post(route('transactions.void',$sale),['void_reason'=>'Kesalahan input'])->assertRedirect(route('transactions.show',$sale)); $sale->refresh(); $this->assertSame(SaleStatus::Voided,$sale->status); $this->assertSame(5,$product->fresh()->stock); $this->assertDatabaseHas('stock_movements',['sale_id'=>$sale->id,'type'=>'void_return','quantity_change'=>2]); $this->assertDatabaseHas('sale_status_histories',['sale_id'=>$sale->id,'to_status'=>'voided','user_id'=>$admin->id]);
    }

    public function test_void_requires_reason_and_cannot_be_done_twice(): void
    {
        [$admin,$kasir,$other,$product,$sale]=$this->saleContext(); $this->actingAs($admin)->post(route('transactions.void',$sale),[])->assertSessionHasErrors('void_reason'); $this->actingAs($admin)->post(route('transactions.void',$sale),['void_reason'=>'Retur'])->assertRedirect(); $this->actingAs($admin)->post(route('transactions.void',$sale),['void_reason'=>'Sekali lagi'])->assertSessionHasErrors('sale'); $this->assertSame(5,$product->fresh()->stock);
    }

    public function test_only_admin_can_void(): void
    {
        [$admin,$kasir,$other,$product,$sale]=$this->saleContext(); $this->actingAs($other)->post(route('transactions.void',$sale),['void_reason'=>'Tidak boleh'])->assertForbidden();
    }

    public function test_cashier_cannot_access_another_cashiers_transaction(): void
    {
        [$admin,$kasir,$other,$product,$sale]=$this->saleContext(); $this->actingAs($other)->get(route('transactions.show',$sale))->assertForbidden(); $this->actingAs($kasir)->get(route('transactions.show',$sale))->assertOk(); $this->actingAs($admin)->get(route('transactions.show',$sale))->assertOk();
    }

    public function test_transaction_index_filters_status_and_scopes_cashier(): void
    {
        [$admin,$kasir,$other,$product,$sale]=$this->saleContext(); $otherSale=$this->actingAs($other)->postJson(route('pos.checkout'),['items'=>[['product_id'=>$product->id,'quantity'=>1]],'payment_method'=>'cash','paid_amount'=>12000])->json('data.id'); $this->actingAs($admin)->post(route('transactions.void',$sale),['void_reason'=>'Tes']); $this->actingAs($kasir)->get(route('transactions.index'))->assertOk()->assertSee($sale->invoice_number); $this->actingAs($kasir)->get(route('transactions.index'))->assertDontSee(Sale::find($otherSale)->invoice_number); $this->actingAs($admin)->get(route('transactions.index',['status'=>'voided']))->assertSee($sale->invoice_number)->assertDontSee(Sale::find($otherSale)->invoice_number);
    }
}
