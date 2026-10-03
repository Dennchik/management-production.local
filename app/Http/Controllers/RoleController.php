<?php

	namespace App\Http\Controllers;

	use App\Models\Role;
	use Illuminate\Http\Request;
	use Illuminate\View\View;

	class RoleController extends Controller
	{
		public function index(): View
		{
			return view('roles.index', [
					'roles' => Role::query()->with('permissions')->orderBy('id')->get(),
					'objects' => Role::OBJECTS,
					'actions' => Role::ACTIONS,
			]);
		}

		public function edit(Role $role): View
		{
			return view('roles.edit', [
					'role' => $role->load('permissions'),
					'objects' => Role::OBJECTS,
					'actions' => Role::ACTIONS,
			]);
		}

		public function update(Request $request, Role $role)
		{
			$role->syncPermissions($request->input('permissions', []));

			return redirect()
					->route('roles.index')
					->with('success', 'Права роли обновлены.');
		}
	}
