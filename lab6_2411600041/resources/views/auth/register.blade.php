<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Student Grade Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="login-page d-flex align-items-center justify-content-center">

    <div class="card login-card" style="width: 100%; max-width: 420px;">
        <div class="card-header text-center py-4">
            <h3 class="text-white fw-bold mb-0">Student Grade Portal</h3>
        </div>
        <div class="card-body p-4">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input id="name" type="text" class="form-control" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autocomplete="username">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" type="password" class="form-control" name="password" required autocomplete="new-password">
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                </div>

                <div class="d-flex align-items-center justify-content-between">
                    <a class="small" href="{{ route('login') }}" style="color: var(--theme-secondary);">Already registered?</a>
                    <button type="submit" class="btn" style="background-color: var(--theme-primary); color: white;">Register</button>
                </div>
            </form>

        </div>
    </div>

</body>
</html>