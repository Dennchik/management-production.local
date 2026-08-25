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
			//* Общая статистика
			$materialsCount = Material::count();
			$rollsCount = MaterialRoll::count();
			$totalWeight = MaterialRoll::sum('weight');

			//* 1. Сырье на складе (бумага ВП, БЛ и фольга)
			$rawMaterialsWeight = MaterialRoll::whereHas('material', function ($query) {
				$query->whereIn('code', ['13', '14', '15']);
			})->sum('weight');

			$rawMaterialsRolls = MaterialRoll::whereHas('material', function ($query) {
				$query->whereIn('code', ['13', '14', '15']);
			})->count();

			//* 2. ПФ не праймированный (МКНП 3 и 4)
			$unprimedPfWeight = MaterialRoll::whereHas('material', function ($query) {
				$query->whereIn('code', ['30', '40']);
			})->sum('weight');

			$unprimedPfRolls = MaterialRoll::whereHas('material', function ($query) {
				$query->whereIn('code', ['30', '40']);
			})->count();

			//* 3. ПФ праймированный (МК 3 и 4 праймированные)
			$primedPfWeight = MaterialRoll::whereHas('material', function ($query) {
				$query->whereIn('code', ['31', '41']);
			})->sum('weight');

			$primedPfRolls = MaterialRoll::whereHas('material', function ($query) {
				$query->whereIn('code', ['31', '41']);
			})->count();

			//* 4. ПФ на резку (все МК с положительным весом)
			$cuttingPfWeight = MaterialRoll::whereHas('material', function ($query) {
				$query->whereIn('code', ['30', '31', '40', '41']);
			})->sum('weight');

			$cuttingPfRolls = MaterialRoll::whereHas('material', function ($query) {
				$query->whereIn('code', ['30', '31', '40', '41']);
			})->count();

			//* 5. ПФ на печать (пока все МК, позже будет статус)
			$printingPfWeight = $cuttingPfWeight;
			$printingPfRolls = $cuttingPfRolls;

			//* 6. Материалы с низким остатком (< 50 кг)
			$lowStockMaterials = Material::withSum('rolls', 'weight')
				->get()
				->filter(function ($material) {
					return $material->rolls_sum_weight > 0 && $material->rolls_sum_weight < 50;
				});

			//* 7. Последние операции (приходы и расходы)
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

			$recentOperations = $receipts
				->concat($issues)
				->sortByDesc('date')
				->take(10)
				->values();

			return view('dashboard.index', compact(
				'materialsCount',
				'rollsCount',
				'totalWeight',
				'rawMaterialsWeight',
				'rawMaterialsRolls',
				'unprimedPfWeight',
				'unprimedPfRolls',
				'primedPfWeight',
				'primedPfRolls',
				'cuttingPfWeight',
				'cuttingPfRolls',
				'printingPfWeight',
				'printingPfRolls',
				'lowStockMaterials',
				'recentOperations'
			));
		}
	}