<?php

	namespace App\Providers;

	use App\Models\ProductionOperation;
	use Illuminate\Support\Facades\View;
	use Illuminate\Support\ServiceProvider;

	class AppServiceProvider extends ServiceProvider
	{
		/**
		 * Register any application services.
		 */
		public function register(): void
		{
			//
		}

		/**
		 * Bootstrap any application services.
		 */
		public function boot(): void
		{
			View::composer('layouts.sidebar', function ($view) {
				$productionOperations = ProductionOperation::query()
						->where('is_active', true)
						->orderBy('id')
						->get();

				$view->with('productionOperations', $productionOperations);
			});
		}
	}