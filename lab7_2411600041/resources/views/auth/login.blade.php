<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Grade Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="login-page d-flex align-items-center justify-content-center">

    <div class="card login-card" style="width: 100%; max-width: 420px;">
        <div class="card-header text-center py-4">
            <h3 class="text-white fw-bold mb-0">Student Grade Portal</h3>
        </div>
        <div class="card-body p-4">

            @session('status')
                <div class="alert alert-success">
                    {{ $value }}
                </div>
            @endsession

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" type="password" class="form-control" name="password" required autocomplete="current-password">
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember_me" name="remember">
                    <label class="form-check-label" for="remember_me">Remember me</label>
                </div>

                <div class="d-flex align-items-center justify-content-between">
                    @if (Route::has('password.request'))
                        <a class="small" href="{{ route('password.request') }}" style="color: var(--theme-secondary);">Forgot your password?</a>
                    @endif

                    <button type="submit" class="btn" style="background-color: var(--theme-primary); color: white;">Log in</button>
                </div>
            </form>

            <div class="text-center mt-3">
                <span class="small text-muted">Don't have an account?</span>
                <a href="{{ route('register') }}" class="small" style="color: var(--theme-accent);">Register</a>
            </div>

        </div>
    </div>

</body>
</html>