<?php

	namespace Handler\Controller\Authentication;

    use App\Headers\Redirect;
    use App\Headers\Request;
    use App\Http\Controller;
    use Http\Services\Auth;

    class Login extends Controller
	{
		public function index(Auth $auth): Redirect|string
        {
            if ($auth->check()) {
                return redirect('/dashboard');
            }

		    return view('auth.login');
		}

        public function submit(Request $req, Auth $auth): Redirect
        {
            $response = $req->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $email = $req->input('email');
            $remember = $req->has('remember');

            // Handle validation errors first
            if (!$response->isSuccess()) {
                return $this->redirectErrors('/login', $response->getErrors(), [
                    'email' => $email,
                    'remember' => $remember,
                ]);
            }

            // Attempt login
            if ($auth->login($email, $req->input('password'), $remember)) {
                return $this->redirect('/dashboard');
            }

            // Invalid credentials
            return $this->redirectFail('/login', 'Invalid credentials.', [
                'email' => $email,
                'remember' => $remember
            ]);
        }
    }
