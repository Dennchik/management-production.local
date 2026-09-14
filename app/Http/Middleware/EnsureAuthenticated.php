<?php

	namespace App\Http\Middleware;

	use Closure;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Redirect;

	class EnsureAuthenticated
	{
		public function handle(Request $request, Closure $next)
		{
			if (!auth()->check()) {
				if ($request->expectsJson() || $request->ajax()) {
					abort(401);
				}

				return Redirect::route('login');
			}

			return $next($request);
		}
	}
