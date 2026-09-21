@extends('layouts.app')
@section('title', 'Satuan')
@push('styles')<link rel="stylesheet" href="{{ asset('bundles/datatables/datatables.min.css') }}">@endpush
@push('vendor-scripts')<script src="{{ asset('bundles/datatables/datatables.min.js') }}"></script>@endpush
@section('content')
<div class="section-header"><h1>Satuan</h1><div class="section-header-breadcrumb"><div class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></div><div class="breadcrumb-item active">Satuan</div></div></div>
<div class="section-body"><div class="card"><div class="card-header"><h4>Daftar satuan</h4><div class="card-header-action"><button class="btn btn-primary btn-add-unit" type="button" data-toggle="modal" data-target="#modalUnit"><i class="fas fa-plus"></i> Tambah satuan</button></div></div><div class="card-body"><div class="table-responsive"><table class="table table-striped" id="units-table"><thead><tr><th>Nama</th><th>Jumlah produk</th><th>Aksi</th></tr></thead><tbody>@forelse ($units as $unit)<tr><td>{{ $unit->name }}</td><td>{{ $unit->products_count }}</td><td class="text-nowrap"><button class="btn btn-sm btn-outline-primary btn-edit-unit" type="button" data-toggle="modal" data-target="#modalUnit" data-id="{{ $unit->id }}" data-name="{{ $unit->name }}"><i class="fas fa-edit"></i> Edit</button> <button class="btn btn-sm btn-outline-danger btn-delete" type="button" data-toggle="modal" data-target="#modalDelete" data-id="{{ $unit->id }}" data-name="{{ $unit->name }}" data-url="{{ route('units.destroy', $unit) }}"><i class="fas fa-trash"></i> Hapus</button></td></tr>@empty<tr><td colspan="3" class="text-center py-4">Belum ada satuan.</td></tr>@endforelse</tbody></table></div></div></div></div>
<div class="modal fade" id="modalUnit" tabindex="-1" role="dialog" aria-labelledby="modalUnitLabel" aria-hidden="true"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="modalUnitLabel">Tambah satuan</h5><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div><form id="unitForm" method="POST" action="{{ route('units.store') }}"><div class="modal-body">@csrf<input type="hidden" name="_form" id="unit_form" value="{{ old('_form') }}"><input type="hidden" name="_id" id="unit_id" value="{{ old('_id') }}"><input type="hidden" name="_method" id="unit_method" value=""><div class="form-group"><label for="unit_name">Nama satuan</label><input id="unit_name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" maxlength="30" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan</button></div></form></div></div></div>
@include('partials.delete-modal')
@endsection
@push('scripts')
<script>
$(function () {
    $('#units-table').DataTable({ pageLength: 10, order: [[0, 'asc']], responsive: true });
    const form = $('#unitForm');
    $('.btn-add-unit').on('click', function () { form[0].reset(); $('#modalUnitLabel').text('Tambah satuan'); form.attr('action', '{{ route('units.store') }}'); $('#unit_method').val(''); $('#unit_form').val('create'); $('#unit_id').val(''); });
    $('.btn-edit-unit').on('click', function () { const b = $(this); $('#modalUnitLabel').text('Edit satuan'); form.attr('action', '{{ url('/units') }}/' + b.data('id')); $('#unit_method').val('PUT'); $('#unit_form').val('edit'); $('#unit_id').val(b.data('id')); $('#unit_name').val(b.data('name')); });
    $('.btn-delete').on('click', function () { $('#deleteName').text($(this).data('name')); $('#deleteForm').attr('action', $(this).data('url')); });
    @if (old('_form') === 'create' || old('_form') === 'edit') $('#modalUnitLabel').text('{{ old('_form') === 'edit' ? 'Edit satuan' : 'Tambah satuan' }}'); form.attr('action', '{{ old('_form') === 'edit' ? url('/units').'/'.old('_id') : route('units.store') }}'); $('#unit_method').val('{{ old('_form') === 'edit' ? 'PUT' : '' }}'); $('#modalUnit').modal('show'); @endif
});
</script>
@endpush
