<nav class="navbar navbar-expand-lg main-navbar sticky">
    <div class="form-inline mr-auto">
        <ul class="navbar-nav mr-3">
            <li>
                <a href="#" data-toggle="sidebar" class="nav-link nav-link-lg collapse-btn" aria-label="Buka atau tutup menu">
                    <i data-feather="align-justify"></i>
                </a>
            </li>
            <li>
                <a href="#" class="nav-link nav-link-lg fullscreen-btn" aria-label="Layar penuh">
                    <i data-feather="maximize"></i>
                </a>
            </li>
        </ul>
    </div>
    <ul class="navbar-nav navbar-right">
        <li class="dropdown">
            <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user antre-profile" aria-label="Buka menu pengguna">
                <img alt="Logo profil Antre-In" src="{{ url('assets/logo/antre-in-icon-light.png') }}" class="rounded-circle mr-2" width="36" height="36">
                <span class="antre-profile__copy">
                    <span class="antre-profile__name">{{ auth()->user()->name }}</span>
                    <small>{{ auth()->user()->role->value === 'admin' ? 'Administrator' : 'Kasir' }}</small>
                </span>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <div class="dropdown-title">{{ auth()->user()->role->value === 'admin' ? 'Administrator' : 'Kasir' }}</div>
                <div class="dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item has-icon text-danger">
                        <i class="fas fa-sign-out-alt"></i> Keluar
                    </button>
                </form>
            </div>
        </li>
    </ul>
</nav>
