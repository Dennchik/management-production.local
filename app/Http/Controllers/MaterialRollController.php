<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
	use App\Models\MaterialRoll;
	use Illuminate\Http\Request;
	use Illuminate\View\View;

	class MaterialRollController extends Controller
	{
		/**
		 * Список физических рулонов.
		 */
		public function index(Request $request): View
		{
			$search = trim($request->input('search', ''));
			$materialId = $request->input('material_id', '');
			$identifier = $request->input('identifier', '');

			$rolls = MaterialRoll::with('material')
					->when($search, function ($query) use ($search) {
						$query->where('roll_number', 'ilike', "%{$search}%");
					})
					->when($materialId, function ($query) use ($materialId) {
						$query->where('material_id', $materialId);
					})
					->when($identifier, function ($query) use ($identifier) {
						$query->whereHas('material', function ($query) use ($identifier) {
							$query->where('identifier', $identifier);
						});
					})
					->where('weight', '>', 0)
					->orderBy('roll_number')
					->get();

			$materials = Material::query()
					->whereHas('rolls', function ($query) {
						$query->where('weight', '>', 0);
					})
					->orderBy('name')
					->get();

			$identifiers = Material::query()
					->whereHas('rolls', function ($query) {
						$query->where('weight', '>', 0);
					})
					->whereNotNull('identifier')
					->where('identifier', '!=', '')
					->orderBy('identifier')
					->pluck('identifier')
					->unique()
					->values();

			return view('material-rolls.index', compact(
					'rolls',
					'search',
					'materialId',
					'identifier',
					'materials',
					'identifiers',
			));
		}

		/**
		 * Карточка физического рулона.
		 */
		public function show(MaterialRoll $roll): View
		{
			$roll->load([
					'material',
					'receiptItems.receipt.user',
					'issues.user',
			]);

			$initialWeight = (float)$roll->receiptItems->sum('weight');
			$currentWeight = (float)$roll->weight;

			$issuedWeight = max(
					0,
					$initialWeight - $currentWeight
			);

			$issuesCount = $roll->issues->count();

			$movements = collect();

			foreach ($roll->receiptItems as $item) {
				$movements->push([
						'type' => 'receipt',
						'date' => $item->created_at,
						'weight' => (float)$item->weight,
						'comment' => $item->receipt?->comment,
						'user' => $item->receipt?->user?->name,
				]);
			}

			foreach ($roll->issues as $issue) {
				$movements->push([
						'type' => 'issue',
						'date' => $issue->created_at,
						'weight' => (float)$issue->weight,
						'comment' => $issue->comment,
						'user' => $issue->user?->name,
				]);
			}

			$movements = $movements
					->sortBy('date')
					->values();

			$movementWeight = 0;

			$movements = $movements->map(function (array $movement) use (
					&$movementWeight
			) {
				if ($movement['type'] === 'receipt') {
					$movementWeight += $movement['weight'];
				} else {
					$movementWeight -= $movement['weight'];
				}

				$movement['balance'] = max(0, $movementWeight);

				return $movement;
			});

			return view('material-rolls.show', compact(
					'roll',
					'initialWeight',
					'currentWeight',
					'issuedWeight',
					'issuesCount',
					'movements',
			));
		}

		/**
		 * Получить список рулонов по идентификатору материала.
		 */
		public function getRollsByMaterial(Request $request)
		{
			$materialId = $request->input('material_id');

			if (!$materialId) {
				return response()->json([]);
			}

			$rolls = MaterialRoll::where('material_id', $materialId)
					->where('weight', '>', 0)
					->orderBy('roll_number')
					->get([
							'id',
							'roll_number',
							'weight',
					]);

			return response()->json($rolls);
		}
	}
