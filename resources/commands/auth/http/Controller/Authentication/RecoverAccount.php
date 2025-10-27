<?php

	namespace Http\Controller\Authentication;

	use App\Headers\Request;
    use App\Headers\Redirect;
    use App\Http\Controller;
    use Http\Model\Users;
    use Http\Services\Auth;

    class RecoverAccount extends Controller
	{
		public function index(Auth $auth, string $token): Redirect|string
        {
            if ($auth->check()) {
                return redirect('/dashboard');
            }

            if (!($userId = $auth->userIdByResetToken($token))) {
                return $this->redirectFail('/login', "Invalid or expired reset token.");
            }

            return view('auth.recover-account', [
                'token' => $token,
                'name' => Users::_($userId)->name
            ]);
		}

        public function recover(Request $req, Auth $auth, string $token): Redirect
        {
            $newPassword = $req->input('password');
            $confirmPassword = $req->input('password_confirmation');
            $failedLink = route('recover-account', ['token' => $token]);

            if ($newPassword !== $confirmPassword) {
                return $this->redirectFail($failedLink, "Passwords do not match.");
            }

            if ($auth->resetPassword($token, $newPassword)) {
                return $this->redirectSuccess('/login', 'Password reset successfully, please login again.');
            }

            return $this->redirectFail($failedLink, "'Failed to reset password.'");
        }
	}
