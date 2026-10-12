<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Student Grade Portal' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    <div id="toastContainer" class="toast-container-custom"></div>

    @php
        $unreadCount = Auth::user()->unreadNotifications()->count();
        $recentNotifications = Auth::user()->notifications()->latest()->take(5)->get();
    @endphp

    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container-fluid">
                <a class="navbar-brand fw-bold" href="{{ route('dashboard') }}">Student Grade Portal</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto align-items-lg-center">
                        <li class="nav-item dropdown me-lg-2">
                            <a class="nav-link position-relative text-white" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-bell fs-5"></i>
                                @if ($unreadCount > 0)
                                    <span id="bellCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $unreadCount }}</span>
                                @endif
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end p-0" style="min-width: 340px;">
                                <li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                    <span class="fw-semibold">Notifications</span>
                                    @if ($unreadCount > 0)
                                        <form method="POST" action="{{ route('notifications.readAll') }}">
                                            @csrf
                                            <button type="submit" class="btn btn-link btn-sm p-0">Mark all as read</button>
                                        </form>
                                    @endif
                                </li>
                                @forelse ($recentNotifications as $n)
                                    <li>
                                        <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item py-2 text-wrap {{ $n->read_at ? '' : 'fw-semibold bg-light' }}">
                                                <div class="small">{{ $n->data['message'] ?? 'Notification' }}</div>
                                                <div class="small text-muted">{{ $n->created_at->diffForHumans() }}</div>
                                            </button>
                                        </form>
                                    </li>
                                @empty
                                    <li class="px-3 py-3 text-muted small">No notifications yet.</li>
                                @endforelse
                            </ul>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">Logout</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar vh-100 p-3">
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('grade-items.*') ? 'active' : '' }}" href="{{ route('grade-items.index') }}">Subjects</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('grade-transactions.*') ? 'active' : '' }}" href="{{ route('grade-transactions.index') }}">Transactions</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">Reports</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('profile.edit') }}">Profile</a>
                        </li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="nav-link text-danger border-0 bg-transparent w-100 text-start">Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if ($errors->has('delete'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ $errors->first('delete') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
</body>
</html>