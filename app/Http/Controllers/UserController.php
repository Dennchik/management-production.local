<?php

	namespace App\Http\Controllers;

	use App\Models\Role;
	use App\Models\User;
	use Illuminate\Http\Request;
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

		private function validateUser(Request $request, ?User $user = null): array
		{
			$passwordRule = $user === null ? ['required', 'string', 'min:3'] : ['nullable', 'string', 'min:3'];

			return $request->validate([
					'name' => ['required', 'string', 'max:255'],
					'password' => $passwordRule,
				'role_id' => ['nullable', 'integer', 'exists:roles,id'],
		]);
		}
	}
