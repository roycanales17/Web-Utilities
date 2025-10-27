<?php

    namespace Http\Controller\Authentication;

    use App\Headers\Redirect;
    use App\Headers\Request;
    use App\Http\Controller;
    use Http\Services\Auth;

    class Register extends Controller
    {
        public function index(Auth $auth): Redirect|string
        {
            if ($auth->check()) {
                return redirect('/dashboard');
            }

            return view('auth.register');
        }

        public function create(Request $req, Auth $auth): Redirect
        {
            $response = $req->validate([
                'name' => 'required|string',
                'email' => 'required|string|email',
                'password' => 'required|string|password',
            ]);

            $name = $req->input('name');
            $email = $req->input('email');

            // Handle validation errors first
            if (!$response->isSuccess()) {
                return $this->redirectErrors('/register', $response->getErrors(), [
                    'name' => $name,
                    'email' => $email,
                ]);
            }

            // Attempt registration
            if ($auth->register($name, $email, $req->input('password'))) {
                return $this->redirectSuccess('/login', 'Account created successfully. You can now log in.');
            }

            // Registration failed
            return $this->redirectFail('/register', 'Failed to register account.', [
                'name' => $name,
                'email' => $email,
            ]);
        }
    }
