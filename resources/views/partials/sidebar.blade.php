<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">
            <a href="{{ route('dashboard') }}">
                <img src="{{ url('assets/logo/antre-in-icon-light.png') }}" alt="Logo Antre-In" class="header-logo">
                <span class="logo-name">Antre-In</span>
            </a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm"><a href="{{ route('dashboard') }}">AI</a></div>
        <ul class="sidebar-menu">
            <li class="menu-header">MAIN</li>
            <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('dashboard') }}"><i data-feather="grid"></i><span>Dashboard</span></a>
            </li>
            <li class="menu-header">KASIR</li>
            <li class="{{ request()->routeIs('pos.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ Route::has('pos.index') ? route('pos.index') : '#' }}"><i data-feather="shopping-cart"></i><span>Kasir</span></a>
            </li>
            <li class="{{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ Route::has('transactions.index') ? route('transactions.index') : '#' }}"><i data-feather="file-text"></i><span>Riwayat Transaksi</span></a>
            </li>
            @if (auth()->user()->isAdmin())
                <li class="menu-header">MASTER DATA</li>
                @foreach ([['products.*', 'products.index', 'package', 'Produk'], ['categories.*', 'categories.index', 'tag', 'Kategori'], ['units.*', 'units.index', 'box', 'Satuan']] as [$pattern, $route, $icon, $label])
                    <li class="{{ request()->routeIs($pattern) ? 'active' : '' }}">
                        <a class="nav-link" href="{{ Route::has($route) ? route($route) : '#' }}"><i data-feather="{{ $icon }}"></i><span>{{ $label }}</span></a>
                    </li>
                @endforeach
                <li class="menu-header">INVENTORI</li>
                <li class="{{ request()->routeIs('stock.index') ? 'active' : '' }}"><a class="nav-link" href="{{ Route::has('stock.index') ? route('stock.index') : '#' }}"><i data-feather="archive"></i><span>Stok</span></a></li>
                <li class="{{ request()->routeIs('stock.movements') ? 'active' : '' }}"><a class="nav-link" href="{{ Route::has('stock.movements') ? route('stock.movements') : '#' }}"><i data-feather="activity"></i><span>Riwayat Stok</span></a></li>
                <li class="menu-header">LAPORAN</li>
                @foreach ([['reports.sales', 'fa-chart-line', 'Penjualan'], ['reports.products', 'fa-trophy', 'Produk Terlaris'], ['reports.stock', 'fa-cubes', 'Stok'], ['reports.drafts', 'fa-clipboard-list', 'Draft Transaksi']] as [$route, $icon, $label])
                    <li class="{{ request()->routeIs($route) ? 'active' : '' }}"><a class="nav-link" href="{{ Route::has($route) ? route($route) : '#' }}"><i class="fas {{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span></a></li>
                @endforeach
                <li class="menu-header">PENGATURAN</li>
                @foreach ([['users.index', 'users', 'Pengguna'], ['settings.edit', 'settings', 'Pengaturan Toko'], ['system-logs.index', 'clipboard', 'Log Aktivitas']] as [$route, $icon, $label])
                    <li class="{{ request()->routeIs($route) ? 'active' : '' }}"><a class="nav-link" href="{{ Route::has($route) ? route($route) : '#' }}"><i data-feather="{{ $icon }}"></i><span>{{ $label }}</span></a></li>
                @endforeach
            @endif
        </ul>
    </aside>
</div>
