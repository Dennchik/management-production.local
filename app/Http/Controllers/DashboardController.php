<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
	use App\Models\MaterialIssue;
	use App\Models\MaterialReceipt;
	use App\Models\MaterialRoll;
	use Illuminate\View\View;

	class DashboardController extends Controller
	{
		/**
		 * Отображает главную страницу системы.
		 */
		public function index(): View
		{
			$materialsCount = Material::count();

			$rollsCount = MaterialRoll::count();

			$totalWeight = MaterialRoll::sum('weight');

			/*
			 * Последние приходные ордера.
			 *
			 * Один приходный ордер может содержать
			 * несколько физических рулонов.
			 */
			$receipts = MaterialReceipt::with([
				'items.material',
				'items.roll',
				'user',
			])
				->latest()
				->take(10)
				->get()
				->map(function (MaterialReceipt $receipt) {
					return [
						'type' => 'receipt',
						'date' => $receipt->created_at,
						'operation' => 'Оприходование',
						'receipt' => $receipt,
						'issue' => null,
					];
				});

			/*
			 * Последние операции расхода.
			 */
			$issues = MaterialIssue::with([
				'material',
				'roll',
				'user',
			])
				->latest()
				->take(10)
				->get()
				->map(function (MaterialIssue $issue) {
					return [
						'type' => 'issue',
						'date' => $issue->created_at,
						'operation' => 'Расход',
						'receipt' => null,
						'issue' => $issue,
					];
				});

			/*
			 * Объединяем приходы и расходы в единый список.
			 *
			 * После объединения снова сортируем по дате,
			 * чтобы операции отображались в правильной
			 * хронологической последовательности.
			 */
			$recentOperations = $receipts
				->concat($issues)
				->sortByDesc('date')
				->take(10)
				->values();

			return view('dashboard.index', compact(
				'materialsCount',
				'rollsCount',
				'totalWeight',
				'recentOperations',
			));
		}
	}