<?php

	namespace App\Providers;

	use App\Models\ProductionOperation;
	use Illuminate\Database\Eloquent\Builder;
	use Illuminate\Database\Query\Builder as QueryBuilder;
	use Illuminate\Support\Facades\DB;
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
			/*
			 * Регистронезависимый LIKE, работающий и на PostgreSQL,
			 * и на MySQL/MariaDB: ilike есть только в PostgreSQL.
			 * Колонки передаются литералами из кода, не от пользователя.
			 */
			$registerIlike = function (string $column, $value, string $boolean = 'and') {
				/** @var \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $this */

				if (DB::connection()->getDriverName() === 'pgsql') {
					return $this->where($column, 'ilike', $value, $boolean);
				}

				return $this->whereRaw(
					'lower(' . $column . ') like lower(?)',
					[$value],
					$boolean
				);
			};

			Builder::macro('whereIlike', $registerIlike);
			QueryBuilder::macro('whereIlike', $registerIlike);

			Builder::macro('orWhereIlike', function (string $column, $value) {
				/** @var \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $this */

				return $this->whereIlike($column, $value, 'or');
			});

			QueryBuilder::macro('orWhereIlike', function (string $column, $value) {
				/** @var \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $this */

				return $this->whereIlike($column, $value, 'or');
			});

			View::composer('layouts.sidebar', function ($view) {
				$productionOperations = ProductionOperation::query()
						->where('is_active', true)
						->orderBy('id')
						->get();

				$view->with('productionOperations', $productionOperations);
			});
		}
	}