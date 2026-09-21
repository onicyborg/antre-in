@extends('layouts.app')

@section('title', 'Riwayat Stok')

@push('styles')
    <link rel="stylesheet" href="{{ asset('bundles/datatables/datatables.min.css') }}">
@endpush
@push('vendor-scripts')
    <script src="{{ asset('bundles/datatables/datatables.min.js') }}"></script>
@endpush

@section('content')
<div class="section-header"><h1>Riwayat Stok</h1><div class="section-header-breadcrumb"><div class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></div><div class="breadcrumb-item"><a href="{{ route('stock.index') }}">Stok</a></div><div class="breadcrumb-item active">Riwayat Stok</div></div></div>
<div class="section-body"><div class="card"><div class="card-header"><h4>Filter riwayat</h4></div><div class="card-body"><form method="GET" action="{{ route('stock.movements') }}"><div class="form-row align-items-end"><div class="form-group col-12 col-lg-4"><label for="movement_period">Rentang tanggal</label><input id="movement_period" name="period" class="form-control daterange" value="{{ $period }}" autocomplete="off"></div><div class="form-group col-12 col-lg-3"><label for="movement_product">Produk</label><select id="movement_product" name="product_id" class="form-control"><option value="">Semua produk</option>@foreach ($products as $product)<option value="{{ $product->id }}" @selected(request('product_id') === $product->id)>{{ $product->sku }} - {{ $product->name }}</option>@endforeach</select></div><div class="form-group col-12 col-lg-3"><label for="movement_type">Tipe</label><select id="movement_type" name="type" class="form-control"><option value="">Semua tipe</option>@foreach ($types as $type)<option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ str_replace('_', ' ', ucfirst($type->value)) }}</option>@endforeach</select></div><div class="form-group col-12 col-lg-2"><button class="btn btn-primary mr-2" type="submit"><i class="fas fa-filter"></i> Terapkan</button><a class="btn btn-light" href="{{ route('stock.movements') }}">Reset</a></div></div></form></div></div>
<div class="card"><div class="card-header"><h4>Pergerakan stok</h4></div><div class="card-body"><div class="table-responsive"><table class="table table-striped" id="movements-table"><thead><tr><th>Waktu</th><th>Produk</th><th>Tipe</th><th>Perubahan</th><th>Sebelum</th><th>Sesudah</th><th>Pelaku</th><th>Referensi</th><th>Catatan</th></tr></thead><tbody>@forelse ($movements as $movement)<tr><td data-order="{{ $movement->created_at->timestamp }}">{{ $movement->created_at->format('d/m/Y H:i') }}</td><td>{{ $movement->product->name }}</td><td>{{ str_replace('_', ' ', ucfirst($movement->type->value)) }}</td><td class="{{ $movement->quantity_change < 0 ? 'text-danger' : 'text-success' }}">{{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}</td><td>{{ $movement->stock_before }}</td><td>{{ $movement->stock_after }}</td><td>{{ $movement->user->name }}</td><td>{{ $movement->sale_id ?: '-' }}</td><td>{{ $movement->note ?: '-' }}</td></tr>@empty<tr><td colspan="9" class="text-center py-4">Belum ada riwayat stok pada rentang ini.</td></tr>@endforelse</tbody></table></div></div></div></div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    $('#movements-table').DataTable({ pageLength: 10, order: [[0, 'desc']], responsive: true, dom: 'Bfrtip', buttons: ['copy', 'csv', 'excel', 'pdf', 'print'] });
});
</script>
@endpush
