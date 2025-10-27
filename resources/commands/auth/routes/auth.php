<?php

    use Http\Controller\Authentication\{ForgotPassword, RecoverAccount, Login, Register};
    use Http\Services\Auth;
    use App\Headers\Request;
    use App\Routes\Route;

    /**
     * ------------------------------------------------------------
     * AUTHENTICATION ROUTES
     * ------------------------------------------------------------
     */

    // Login
    Route::get('/login', [Login::class, 'index'])->name('login');
    Route::post('/login', [Login::class, 'submit']);

    // Registration
    Route::get('/register',[Register::class, 'index'])->name('register');
    Route::post('/register', [Register::class, 'create']);

    // Account Recovery
    Route::get('/forgot-password', [ForgotPassword::class, 'index'])->name('forgot-password');
    Route::post('/forgot-password', [ForgotPassword::class, 'sendPasswordResetEmail']);

    // Reset Password
    Route::get('/recover-account/{token}', [RecoverAccount::class, 'index'])->name('recover-account');
    Route::post('/recover-account/{token}', [RecoverAccount::class, 'recover']);

    // Logout
    Route::get('/logout', function (Auth $auth) {
        $auth->logout();
        return redirect('/login')->with('message:success', 'You have been logged out.');
    });

    // Protected Routes
    Route::middleware([Auth::class, 'check'])->group(function ()
    {
        // Dashboard
        Route::get('/dashboard', fn (Auth $auth) => view('auth.dashboard', ['user' => $auth->user()]));
    })
    ->unauthorized(fn() => redirect('/login', 401)->with('error', 'Login first to access this page.'));










    /**
     * ------------------------------------------------------------
     * API TOKEN AUTH (Optional)
     * ------------------------------------------------------------
     */

    Route::post('/api/token', function (Request $req, Auth $auth) {
        $email = $req->input('email');
        $password = $req->input('password');

        if ($auth->login($email, $password)) {
            $token = $auth->issueToken($auth->id());
            return response()->json(['api_token' => $token]);
        }

        return response(['error' => 'Invalid credentials.'], 401)->json();
    });

    // Example API-protected route
    Route::get('/api/user', function (Auth $auth) {
        if (!$auth->check()) {
            return response(['error' => 'Unauthorized.'], 401)->json();
        }

        return response(['user' => $auth->user()])->json();
    });
