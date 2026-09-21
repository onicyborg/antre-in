@extends('layouts.app')

@section('title', 'Pengguna')

@push('styles')
    <link rel="stylesheet" href="{{ asset('bundles/datatables/datatables.min.css') }}">
@endpush
@push('vendor-scripts')
    <script src="{{ asset('bundles/datatables/datatables.min.js') }}"></script>
@endpush

@section('content')
<div class="section-header"><h1>Pengguna</h1><div class="section-header-breadcrumb"><div class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></div><div class="breadcrumb-item active">Pengguna</div></div></div>
<div class="section-body">
    <div class="card">
        <div class="card-header"><h4>Daftar pengguna</h4><div class="card-header-action"><button type="button" class="btn btn-primary btn-add-user" data-toggle="modal" data-target="#modalUser"><i class="fas fa-plus"></i> Tambah pengguna</button></div></div>
        <div class="card-body">
            <div class="table-responsive"><table class="table table-striped" id="users-table"><thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                @forelse ($users as $user)
                    <tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->role->value === 'admin' ? 'Admin' : 'Kasir' }}</td><td><span class="badge badge-{{ $user->is_active ? 'success' : 'secondary' }}">{{ $user->is_active ? 'Aktif' : 'Tidak aktif' }}</span></td><td class="text-nowrap"><button type="button" class="btn btn-sm btn-outline-primary btn-edit-user" data-toggle="modal" data-target="#modalUser" data-id="{{ $user->id }}" data-name="{{ $user->name }}" data-email="{{ $user->email }}" data-role="{{ $user->role->value }}" data-active="{{ $user->is_active ? '1' : '0' }}"><i class="fas fa-edit"></i> Edit</button> <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-toggle="modal" data-target="#modalDelete" data-id="{{ $user->id }}" data-name="{{ $user->name }}" data-url="{{ route('users.destroy', $user) }}"><i class="fas fa-trash"></i> Hapus</button></td></tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4">Belum ada pengguna.</td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUser" tabindex="-1" role="dialog" aria-labelledby="modalUserLabel" aria-hidden="true"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="modalUserLabel">Tambah pengguna</h5><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div><form id="userForm" method="POST" action="{{ route('users.store') }}"><div class="modal-body">@csrf<input type="hidden" name="_form" id="user_form" value="{{ old('_form') }}"><input type="hidden" name="_id" id="user_id" value="{{ old('_id') }}"><input type="hidden" name="_method" id="user_method" value="">
    <div class="form-group"><label for="user_name">Nama</label><input id="user_name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="form-group"><label for="user_email">Email</label><input id="user_email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required autocomplete="email">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="form-group"><label for="user_role">Peran</label><select id="user_role" name="role" class="form-control @error('role') is-invalid @enderror" required><option value="">Pilih peran</option>@foreach ($roles as $role)<option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->value === 'admin' ? 'Admin' : 'Kasir' }}</option>@endforeach</select>@error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="form-group"><label for="user_password">Kata sandi <small class="text-muted" id="passwordHelp">(minimal 8 karakter)</small></label><input id="user_password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password"><div class="form-text text-muted" id="userPasswordHint">Wajib diisi saat membuat pengguna.</div>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="form-group"><div class="custom-control custom-checkbox"><input type="hidden" name="is_active" value="0"><input id="user_active" type="checkbox" name="is_active" value="1" class="custom-control-input" {{ old('is_active', '1') ? 'checked' : '' }}><label class="custom-control-label" for="user_active">Pengguna aktif</label></div>@error('is_active')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
    </div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div></form></div></div></div>
@include('partials.delete-modal')
@endsection

@push('scripts')
<script>
$(function () {
    $('#users-table').DataTable({ pageLength: 10, order: [[0, 'asc']], responsive: true });
    const form = $('#userForm');
    $('.btn-add-user').on('click', function () { form[0].reset(); $('#modalUserLabel').text('Tambah pengguna'); form.attr('action', '{{ route('users.store') }}'); $('#user_method').val(''); $('#user_form').val('create'); $('#user_id').val(''); $('#user_password').prop('required', true); $('#userPasswordHint').text('Wajib diisi saat membuat pengguna.'); });
    $('.btn-edit-user').on('click', function () { const b = $(this); $('#modalUserLabel').text('Edit pengguna'); form.attr('action', '{{ url('/users') }}/' + b.data('id')); $('#user_method').val('PUT'); $('#user_form').val('edit'); $('#user_id').val(b.data('id')); $('#user_name').val(b.data('name')); $('#user_email').val(b.data('email')); $('#user_role').val(b.data('role')); $('#user_active').prop('checked', String(b.data('active')) === '1'); $('#user_password').prop('required', false); $('#userPasswordHint').text('Kosongkan jika tidak ingin mengubah kata sandi.'); });
    $('.btn-delete').on('click', function () { $('#deleteName').text($(this).data('name')); $('#deleteForm').attr('action', $(this).data('url')); });
    @if (old('_form') === 'create' || old('_form') === 'edit') $('#modalUserLabel').text('{{ old('_form') === 'edit' ? 'Edit pengguna' : 'Tambah pengguna' }}'); form.attr('action', '{{ old('_form') === 'edit' ? url('/users').'/'.old('_id') : route('users.store') }}'); $('#user_method').val('{{ old('_form') === 'edit' ? 'PUT' : '' }}'); $('#modalUser').modal('show'); @endif
});
</script>
@endpush
