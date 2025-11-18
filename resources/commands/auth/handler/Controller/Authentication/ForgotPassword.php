<?php

	namespace Handler\Controller\Authentication;

	use App\Utilities\Redirect;
    use App\Utilities\Request;
    use App\Http\Controller;
    use Http\Services\Auth;

    class ForgotPassword extends Controller
	{
		public function index(Auth $auth): Redirect|string
        {
            if ($auth->check()) {
                return redirect('/dashboard');
            }

            return view('auth.forgot-password');
		}

		public function sendPasswordResetEmail(Request $req, Auth $auth): Redirect
        {
            $email = $req->input('email');
            $token = $auth->sendPasswordReset($email);

            if ($token) {
                return $this->redirectSuccess('/login', 'Reset password link has been sent to your email.');
            }

            return $this->redirectFail('/forgot-password','Email not found.', [
                'email' => $email
            ]);
		}
	}
