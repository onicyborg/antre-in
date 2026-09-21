<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_category_and_unit_can_be_created_and_updated(): void
    {
        $categoryResponse = $this->actingAs($this->admin)->post(route('categories.store'), ['name' => 'Minuman']);
        $categoryResponse->assertRedirect(route('categories.index'));
        $category = Category::query()->firstOrFail();

        $this->actingAs($this->admin)->put(route('categories.update', $category), ['name' => 'Minuman Dingin'])
            ->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Minuman Dingin']);

        $this->actingAs($this->admin)->post(route('units.store'), ['name' => 'pcs'])
            ->assertRedirect(route('units.index'));
        $unit = Unit::query()->firstOrFail();
        $this->actingAs($this->admin)->put(route('units.update', $unit), ['name' => 'buah'])
            ->assertRedirect(route('units.index'));
        $this->assertDatabaseHas('units', ['id' => $unit->id, 'name' => 'buah']);
    }

    public function test_category_and_unit_delete_are_rejected_when_used_by_product(): void
    {
        $category = Category::factory()->create(['name' => 'Dipakai']);
        $unit = Unit::factory()->create(['name' => 'paket']);
        Product::factory()->create(['category_id' => $category->id, 'unit_id' => $unit->id]);

        $this->actingAs($this->admin)->delete(route('categories.destroy', $category))
            ->assertSessionHas('error', 'Kategori tidak dapat dihapus karena masih dipakai produk.');
        $this->actingAs($this->admin)->delete(route('units.destroy', $unit))
            ->assertSessionHas('error', 'Satuan tidak dapat dihapus karena masih dipakai produk.');
    }

    public function test_user_cannot_delete_or_deactivate_self_and_related_user_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin)->delete(route('users.destroy', $this->admin))
            ->assertSessionHas('error', 'Anda tidak dapat menghapus akun sendiri.');

        $this->actingAs($this->admin)->put(route('users.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => 'admin',
            'password' => '',
            'is_active' => 0,
        ])->assertSessionHas('error', 'Anda tidak dapat menonaktifkan atau mengubah peran akun sendiri.');

        $related = User::factory()->kasir()->create();
        Sale::factory()->create(['user_id' => $related->id]);

        $this->actingAs($this->admin)->delete(route('users.destroy', $related))
            ->assertSessionHas('error', 'Pengguna tidak dapat dihapus karena sudah memiliki transaksi atau log aktivitas. Nonaktifkan saja.');
    }
}
