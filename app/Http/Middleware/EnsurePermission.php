<?php

	namespace App\Http\Middleware;

	use App\Models\Role;
	use Closure;
	use Illuminate\Http\Request;

	class EnsurePermission
	{
		public function handle(Request $request, Closure $next, string $object, string $action = 'view')
		{
			$user = $request->user();

			if ($user !== null && !$user->may($object, $action)) {
				abort(403, 'Недостаточно прав.');
			}

			return $next($request);
		}
	}
