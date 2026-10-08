<?php

	namespace App\Http\Controllers;

	use App\Models\Catalog;
	use App\Models\Material;
	use App\Models\MaterialIssue;
	use App\Models\MaterialReceipt;
	use App\Models\MaterialRoll;
	use Illuminate\Support\Collection;
	use Illuminate\View\View;

	class DashboardController extends Controller
	{
		/**
		 * Отображает главную страницу системы.
		 *
		 * Группы «Состояния производства» не зашиты кодами: полуфабрикаты (ПФ)
		 * определяются по каталогу «МК» (и подкаталогам), праймированность —
		 * по названию материала. Сырьё — всё остальное.
		 */
		public function index(): View
		{
			$materialsCount = Material::count();
			$rollsCount = MaterialRoll::count();
			$totalWeight = MaterialRoll::sum('weight');

			$pfCodes = $this->pfCodes();
			$primedCodes = $this->primedCodes($pfCodes);
			$unprimedCodes = $pfCodes->diff($primedCodes)->values();
			$rawCodes = Material::query()
					->when($pfCodes->isNotEmpty(), fn ($query) => $query->whereNotIn('code', $pfCodes))
					->pluck('code')
					->unique()
					->values();

			$productionStats = collect([
					[
							'label' => 'Сырье на складе',
							'codes' => $rawCodes,
					],
					[
							'label' => 'ПФ не праймированный',
							'codes' => $unprimedCodes,
					],
					[
							'label' => 'ПФ праймированный',
							'codes' => $primedCodes,
					],
					[
							'label' => 'ПФ на резку',
							'codes' => $pfCodes,
					],
					[
							'label' => 'ПФ на печать',
							'codes' => $pfCodes,
					],
			])->map(function (array $stat) {
				[$weight, $rolls] = $this->rollsStatsByCodes($stat['codes']);

				return $stat + [
						'weight' => $weight,
						'rolls' => $rolls,
				];
			});

			//* Материалы с низким остатком (< 50 кг)
			$lowStockMaterials = Material::withSum('rolls', 'weight')
				->get()
				->filter(function ($material) {
					return $material->rolls_sum_weight > 0 && $material->rolls_sum_weight < 50;
				});

			//* Последние операции (приходы и расходы)
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
				'productionStats',
				'lowStockMaterials',
				'recentOperations'
			));
		}

		/**
		 * Коды материалов-полуфабрикатов: каталог «МК» и его подкаталоги.
		 */
		private function pfCodes(): Collection
		{
			$pfCatalogIds = Catalog::query()
					->where('name', 'like', 'МК%')
					->pluck('id');

			if ($pfCatalogIds->isEmpty()) {
				return collect();
			}

			return Material::query()
					->whereIn('catalog_id', $pfCatalogIds)
					->pluck('code')
					->filter()
					->unique()
					->values();
		}

		/**
		 * Коды праймированных ПФ: в названии есть «праймированный»,
		 * но нет «не праймированный».
		 */
		private function primedCodes(Collection $pfCodes): Collection
		{
			if ($pfCodes->isEmpty()) {
				return collect();
			}

			return Material::query()
					->whereIn('code', $pfCodes)
					->where('name', 'like', '%праймированный%')
					->where('name', 'not like', '%не праймированный%')
					->pluck('code')
					->unique()
					->values();
		}

		/**
		 * Вес и число рулонов по набору кодов материалов.
		 *
		 * @return array{0: float, 1: int}
		 */
		private function rollsStatsByCodes(Collection $codes): array
		{
			if ($codes->isEmpty()) {
				return [0.0, 0];
			}

			$query = MaterialRoll::query()
					->whereHas('material', static fn ($query) => $query->whereIn('code', $codes));

			return [
					(float) $query->sum('weight'),
					$query->count(),
			];
		}
	}
