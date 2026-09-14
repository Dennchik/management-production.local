<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Validation\ValidationException;
	use Illuminate\View\View;

	class AuthController extends Controller
	{
		public function showLogin(): View
		{
			return view('auth.login');
		}

		public function login(Request $request)
		{
			$credentials = $request->validate([
					'name' => ['required', 'string'],
					'password' => ['required', 'string'],
			]);

			if (!Auth::attempt($credentials, true)) {
				throw ValidationException::withMessages([
						'name' => 'Неверное имя пользователя или пароль.',
				]);
			}

			$request->session()->regenerate();

			return redirect()->intended(route('dashboard'));
		}

		public function logout(Request $request)
		{
			Auth::logout();

			$request->session()->invalidate();
			$request->session()->regenerateToken();

			return redirect()->route('login');
		}
	}
