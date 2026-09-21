<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaleCartRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }
    public function rules(): array { return ['items'=>'required|array|min:1','items.*.product_id'=>'required|uuid|exists:products,id|distinct','items.*.quantity'=>'required|integer|min:1|max:9999','discount_type'=>'nullable|in:nominal,percent','discount_value'=>'nullable|integer|min:0','payment_method'=>($this->is('pos/checkout') ? 'required' : 'nullable').'|in:cash,qris,transfer,debit','paid_amount'=>'nullable|integer|min:0','payment_reference'=>'nullable|string|max:100','note'=>'nullable|string|max:255','label'=>'nullable|string|max:100','draft_id'=>'nullable|uuid|exists:sales,id']; }
    public function withValidator($validator): void { $validator->after(function ($validator): void { if ($this->input('discount_type') === 'percent' && (int) $this->input('discount_value', 0) > 100) $validator->errors()->add('discount_value', 'Persentase diskon maksimal 100%.'); }); }
    public function messages(): array { return ['items.required'=>'Keranjang wajib diisi.','items.min'=>'Keranjang tidak boleh kosong.','items.*.product_id.exists'=>'Produk tidak ditemukan.','items.*.quantity.required'=>'Jumlah produk wajib diisi.','items.*.quantity.min'=>'Jumlah produk minimal 1.','discount_value.integer'=>'Nilai diskon harus berupa angka.','payment_method.required'=>'Metode pembayaran wajib dipilih.']; }
}
