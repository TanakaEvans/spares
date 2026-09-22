<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'School Portal') }} - Login</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Login Styles -->
    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --light-bg: #f8fafc;
            --dark-bg: #0f172a;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-container {
            background: var(--card-bg);
            border-radius: 1rem;
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 600px;
        }

        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 400px;
            }
            .login-hero {
                display: none;
            }
        }

        .login-hero {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='m36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            opacity: 0.1;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-logo {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }

        .hero-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .hero-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        .hero-features {
            list-style: none;
            text-align: left;
        }

        .hero-features li {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }

        .hero-features i {
            width: 20px;
            margin-right: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .login-form-container {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h2 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
            background: var(--card-bg);
            font-size: 0.875rem;
            transition: all 0.2s ease;
            color: var(--text-primary);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-control.is-invalid {
            border-color: var(--danger-color);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .invalid-feedback {
            color: var(--danger-color);
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: block;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .form-check input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
        }

        .form-check label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.875rem 1.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        .demo-accounts {
            background: var(--light-bg);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 1rem;
        }

        .demo-accounts h4 {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .demo-accounts p {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin: 0.25rem 0;
        }

        .alert {
            padding: 0.875rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Hero Section -->
        <div class="login-hero">
            <div class="hero-content">
                <div class="hero-logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h1 class="hero-title">School Portal</h1>
                <p class="hero-subtitle">Comprehensive School Management System</p>
                
                <ul class="hero-features">
                    <li><i class="fas fa-users"></i> User & Role Management</li>
                    <li><i class="fas fa-chart-line"></i> Academic Performance Tracking</li>
                    <li><i class="fas fa-calendar-check"></i> Attendance Management</li>
                    <li><i class="fas fa-file-invoice-dollar"></i> Financial Management</li>
                    <li><i class="fas fa-comments"></i> Communication Tools</li>
                    <li><i class="fas fa-shield-alt"></i> Secure & Reliable</li>
                </ul>
            </div>
        </div>

        <!-- Login Form -->
        <div class="login-form-container">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Sign in to your account to continue</p>
            </div>

            <!-- Session Messages -->
            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Login Failed:</strong> {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf

                <!-- Email/Username -->
                <div class="form-group">
                    <label for="login" class="form-label">Email or Username</label>
                    <input 
                        id="login" 
                        class="form-control @error('login') is-invalid @enderror" 
                        type="text" 
                        name="login" 
                        value="{{ old('login') }}" 
                        required 
                        autofocus 
                        autocomplete="username"
                        placeholder="Enter your email or username"
                    >
                    @error('login')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        id="password" 
                        class="form-control @error('password') is-invalid @enderror" 
                        type="password" 
                        name="password" 
                        required 
                        autocomplete="current-password"
                        placeholder="Enter your password"
                    >
                    @error('password')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="form-check">
                    <input id="remember_me" type="checkbox" name="remember">
                    <label for="remember_me">Remember me for 30 days</label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-primary" id="loginBtn">
                    <span id="loginText">Sign In</span>
                    <div id="loginSpinner" class="spinner" style="display: none;"></div>
                </button>
            </form>

            <!-- Demo Accounts -->
            <div class="demo-accounts">
                <h4>Demo Accounts for Testing:</h4>
                <p><strong>Superuser:</strong> admin@schoolportal.com / password123</p>
                <p><strong>Teacher:</strong> teacher@schoolportal.com / password123</p>
                <p><strong>Student:</strong> student@schoolportal.com / password123</p>
                <p><strong>Parent:</strong> parent@schoolportal.com / password123</p>
                <p><strong>Staff:</strong> staff@schoolportal.com / password123</p>
            </div>

            <div class="login-footer">
                <p style="color: var(--text-secondary); font-size: 0.75rem;">
                    © {{ date('Y') }} School Portal. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            const text = document.getElementById('loginText');
            const spinner = document.getElementById('loginSpinner');
            
            btn.disabled = true;
            text.style.display = 'none';
            spinner.style.display = 'block';
        });

        // Auto-fill demo credentials on click
        document.querySelectorAll('.demo-accounts p').forEach(function(p) {
            if (p.innerHTML.includes('@')) {
                p.style.cursor = 'pointer';
                p.addEventListener('click', function() {
                    const email = this.innerHTML.match(/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/)[0];
                    const password = 'password123';
                    
                    document.getElementById('login').value = email;
                    document.getElementById('password').value = password;
                });
            }
        });
    </script>
</body>
</html>
