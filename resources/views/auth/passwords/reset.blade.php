<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password | {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            padding: 2rem;
        }
        .login-box {
            width: 100%;
            max-width: 400px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo a {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
        }
        .login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        .login-box-msg {
            text-align: center;
            color: #6b7280;
            margin-bottom: 1.5rem;
        }
        .input-group {
            margin-bottom: 1rem;
        }
        .input-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        .input-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
        }
        .input-group input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .btn {
            display: inline-block;
            width: 100%;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-primary {
            background: #6366f1;
            color: white;
        }
        .btn-primary:hover {
            background: #4f46e5;
        }
        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
        }
        .form-footer a {
            color: #6366f1;
            text-decoration: none;
        }
        .form-footer a:hover {
            text-decoration: underline;
        }
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-box">
        <div class="login-logo"><a href="/">{{ config('app.name') }}</a></div>
        <div class="login-card">
            <p class="login-box-msg">Reset your password</p>
            @foreach ($errors->all() as $error)
                <div class="alert alert-danger">{{ $error }}</div>
            @endforeach
            <form action="{{ route('password.update') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" placeholder="Enter your email" value="{{ $email ?? old('email') }}" required>
                </div>
                <div class="input-group">
                    <label for="password">New Password</label>
                    <input type="password" name="password" id="password" placeholder="Enter new password" required>
                </div>
                <div class="input-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Confirm password" required>
                </div>
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </form>
        </div>
    </div>
</body>
</html>
