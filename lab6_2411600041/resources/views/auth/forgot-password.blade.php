<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Student Grade Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="login-page d-flex align-items-center justify-content-center">

    <div class="card login-card" style="width: 100%; max-width: 420px;">
        <div class="card-header text-center py-4">
            <h3 class="text-white fw-bold mb-0">Forgot Password</h3>
        </div>
        <div class="card-body p-4">

            <p class="small text-muted">Forgot your password? No problem. Just let us know your email address and we will email you a password reset link.</p>

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

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn" style="background-color: var(--theme-primary); color: white;">Email Password Reset Link</button>
                </div>
            </form>

        </div>
    </div>

</body>
</html>