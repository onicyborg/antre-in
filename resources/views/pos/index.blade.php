@extends('layouts.app')

@section('title', 'Kasir')

@push('styles')<link rel="stylesheet" href="{{ url('css/pos.css') }}">@endpush

@section('content')
<div id="pos-app" class="section-body" data-products-url="{{ route('pos.products') }}" data-checkout-url="{{ route('pos.checkout') }}" data-drafts-url="{{ route('pos.drafts.index') }}" data-csrf="{{ csrf_token() }}" data-tax-percent="{{ $setting->tax_percent }}">
    <div id="pos-alerts" aria-live="polite"></div>
    <div class="section-header"><h1>Kasir</h1><div class="section-header-breadcrumb"><div class="breadcrumb-item active">Dashboard</div><div class="breadcrumb-item">Kasir</div></div></div>
    <div class="row pos-layout">
        <div class="col-lg-7"><div class="card"><div class="card-header"><h4>Daftar Produk</h4></div><div class="card-body">
            <div class="form-row"><div class="form-group col-md-9"><label for="pos-search">Cari produk</label><input id="pos-search" class="form-control" placeholder="Nama, SKU, atau barcode"></div><div class="form-group col-md-3"><label for="pos-category">Kategori</label><select id="pos-category" class="form-control"><option value="">Semua</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></div></div>
            <div id="pos-products" class="pos-products-grid" aria-live="polite"></div><div id="pos-pagination" class="d-flex justify-content-center mt-3"></div>
        </div></div></div>
        <div class="col-lg-5"><div class="card pos-cart-card"><div class="card-header"><h4>Keranjang</h4><button type="button" id="pos-clear" class="btn btn-sm btn-outline-danger">Kosongkan</button></div><div class="card-body"><div id="cart-items"></div>
            <div class="border-top pt-3 mt-3"><div class="form-row"><div class="form-group col-5"><label for="discount-type">Diskon</label><select id="discount-type" class="form-control"><option value="nominal">Nominal</option><option value="percent">Persen</option></select></div><div class="form-group col-7"><label for="discount-value">Nilai diskon</label><input id="discount-value" type="number" min="0" class="form-control" value="0"></div></div>
            <dl class="row mb-0"><dt class="col-7">Subtotal</dt><dd id="summary-subtotal" class="col-5 text-right">Rp 0</dd><dt class="col-7">Diskon</dt><dd id="summary-discount" class="col-5 text-right">Rp 0</dd><dt class="col-7">Pajak ({{ $setting->tax_percent }}%)</dt><dd id="summary-tax" class="col-5 text-right">Rp 0</dd><dt class="col-7 font-weight-bold">Total</dt><dd id="summary-total" class="col-5 text-right font-weight-bold">Rp 0</dd></dl></div></div><div class="card-footer pos-cart-actions"><div class="btn-group btn-block"><button type="button" id="pos-drafts" class="btn btn-outline-primary">Draft <span id="draft-count" class="badge badge-primary">0</span></button><button type="button" id="pos-save-draft" class="btn btn-warning" disabled>Simpan Draft</button><button type="button" id="pos-pay" class="btn btn-success" disabled>Bayar</button></div></div></div></div>
    </div>
</div>
<div class="modal fade" id="payment-modal" tabindex="-1" role="dialog"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Pembayaran</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div><div class="modal-body"><div class="form-group"><label for="payment-method">Metode pembayaran</label><select id="payment-method" class="form-control"><option value="cash">Tunai</option><option value="qris">QRIS</option><option value="transfer">Transfer</option><option value="debit">Debit</option></select></div><div class="form-group"><label for="paid-amount">Jumlah bayar</label><input id="paid-amount" type="number" class="form-control"><div class="mt-2"><button class="btn btn-sm btn-outline-secondary quick-pay" data-value="exact">Uang pas</button><button class="btn btn-sm btn-outline-secondary quick-pay" data-value="20000">20.000</button><button class="btn btn-sm btn-outline-secondary quick-pay" data-value="50000">50.000</button><button class="btn btn-sm btn-outline-secondary quick-pay" data-value="100000">100.000</button></div></div><div class="form-group"><label for="payment-reference">Referensi pembayaran</label><input id="payment-reference" class="form-control"></div><div class="text-right">Kembalian: <strong id="payment-change">Rp 0</strong></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button><button type="button" id="confirm-payment" class="btn btn-primary">Selesaikan transaksi</button></div></div></div></div>
<div class="modal fade" id="success-modal" tabindex="-1" role="dialog"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Transaksi berhasil</h5></div><div class="modal-body"><p id="success-message"></p></div><div class="modal-footer"><a id="print-receipt" target="_blank" class="btn btn-outline-primary">Cetak Struk</a><button id="new-transaction" class="btn btn-primary" data-dismiss="modal">Transaksi Baru</button></div></div></div></div>
<div class="modal fade" id="modalSaveDraft" tabindex="-1" role="dialog"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Simpan Draft</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div><div class="modal-body"><label for="draft-label">Label pelanggan (opsional)</label><input id="draft-label" class="form-control" maxlength="100" placeholder="mis. Ibu baju merah"></div><div class="modal-footer"><button class="btn btn-secondary" data-dismiss="modal">Batal</button><button id="confirm-save-draft" class="btn btn-warning">Simpan</button></div></div></div></div>
<div class="modal fade" id="modalDraftList" tabindex="-1" role="dialog"><div class="modal-dialog modal-lg" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Daftar Draft</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div><div class="modal-body"><div id="draft-list" class="draft-list"></div></div></div></div></div>
<div class="modal fade" id="posProductImageModal" tabindex="-1" role="dialog" aria-labelledby="posProductImageModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-xl" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="posProductImageModalLabel">Preview gambar produk</h5><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div><div class="modal-body"><img id="posProductImagePreview" src="" alt="Preview gambar produk"></div></div></div></div>
@endsection

@push('scripts')
<script src="{{ url('js/pos.js') }}"></script>
<script>
    (function () {
        var alerts = document.getElementById('pos-alerts');
        if (!alerts || !window.Swal || !window.MutationObserver) return;
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (!node.classList || !node.classList.contains('alert')) return;
                    var clone = node.cloneNode(true);
                    clone.querySelectorAll('button').forEach(function (button) { button.remove(); });
                    var type = node.className.indexOf('alert-danger') !== -1 ? 'error' : (node.className.indexOf('alert-warning') !== -1 ? 'warning' : (node.className.indexOf('alert-success') !== -1 ? 'success' : 'info'));
                    node.remove();
                    window.Swal.fire({
                        icon: type,
                        title: clone.textContent.trim(),
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: type === 'error' ? 5000 : 3500,
                        timerProgressBar: true
                    });
                });
            });
        });
        observer.observe(alerts, { childList: true });
    }());
</script>
@endpush
