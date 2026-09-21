<?php

	namespace App\Http\Controllers;

	use App\Models\Role;
	use App\Models\User;
	use Illuminate\Http\Request;
	use Illuminate\Validation\Rule;
	use Illuminate\View\View;

	class UserController extends Controller
	{
		public function index(): View
		{
			return view('users.index', [
					'users' => User::query()->with(['role'])->orderBy('id')->get(),
			]);
		}

		public function create(): View
		{
			return view('users.edit', [
					'user' => new User(),
					'roles' => Role::query()->orderBy('name')->get(),
			]);
		}

		public function store(Request $request)
		{
			$data = $this->validateUser($request);

			User::create($data);

			return redirect()->route('users.index')->with('success', 'Пользователь создан.');
		}

		public function edit(User $user): View
		{
			return view('users.edit', [
					'user' => $user,
					'roles' => Role::query()->orderBy('name')->get(),
			]);
		}

		public function update(Request $request, User $user)
		{
			$data = $this->validateUser($request, $user);

			if (($data['password'] ?? '') === '') {
				unset($data['password']);
			}

			$user->update($data);

			return redirect()->route('users.index')->with('success', 'Пользователь обновлён.');
		}

		/**
		 * Карточка текущего пользователя — доступна каждому.
		 */
		public function profile(Request $request): View
		{
			return view('users.profile', [
					'user' => $request->user(),
			]);
		}

		/**
		 * Смена собственного пароля: проверяем текущий пароль.
		 * Пароли других пользователей меняет администратор в карточке пользователя.
		 */
		public function updatePassword(Request $request)
		{
			$data = $request->validate([
					'current_password' => ['required', 'string', 'current_password:web'],
					'password' => ['required', 'string', 'min:3', 'confirmed'],
			], [
					'current_password.current_password' => 'Неверный текущий пароль.',
					'password.min' => 'Новый пароль должен содержать не менее :min символов.',
					'password.confirmed' => 'Новый пароль и подтверждение не совпадают.',
			]);

			$request->user()->update(['password' => $data['password']]);

			return redirect()->route('profile.show')->with('success', 'Пароль изменён.');
		}

		private function validateUser(Request $request, ?User $user = null): array
		{
			$passwordRule = $user === null ? ['required', 'string', 'min:3'] : ['nullable', 'string', 'min:3'];

			return $request->validate([
					'login' => ['required', 'string', 'max:255', Rule::unique('users', 'login')->ignore($user?->id)],
					'full_name' => ['required', 'string', 'max:255'],
					'display_name' => ['nullable', 'string', 'max:255'],
					'password' => $passwordRule,
					'role_id' => ['nullable', 'integer', 'exists:roles,id'],
			], [
					'login.required' => 'Укажите логин.',
					'login.unique' => 'Такой логин уже занят.',
					'full_name.required' => 'Укажите ФИО.',
					'password.min' => 'Пароль должен содержать не менее :min символов.',
			]);
		}
	}
