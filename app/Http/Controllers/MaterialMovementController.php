<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
	use App\Models\MaterialIssue;
	use App\Models\MaterialReceiptItem;
	use Illuminate\Http\Request;
	use Illuminate\View\View;

	class MaterialMovementController extends Controller
	{
		/**
		 * Журнал движения материалов.
		 *
		 * Формируется из существующих операций:
		 * - приходных позиций;
		 * - расходных операций.
		 */
		public function index(Request $request): View
		{
			$dateFrom = $request->input('date_from');
			$dateTo = $request->input('date_to');
			$type = $request->input('type');
			$materialId = $request->input('material_id');
			$search = trim($request->input('search', ''));

			/*
			 * Приходы.
			 */
			$receipts = MaterialReceiptItem::with([
				'receipt.user',
				'material',
				'roll',
			])
				->when($dateFrom, function ($query) use ($dateFrom) {
					$query->whereDate('created_at', '>=', $dateFrom);
				})
				->when($dateTo, function ($query) use ($dateTo) {
					$query->whereDate('created_at', '<=', $dateTo);
				})
				->when($materialId, function ($query) use ($materialId) {
					$query->where('material_id', $materialId);
				})
				->when($search, function ($query) use ($search) {
					$query->whereHas('material', function ($query) use ($search) {
						$query
							->where('name', 'ilike', '%' . $search . '%')
							->orWhere(
								'identifier',
								'ilike',
								'%' . $search . '%'
							);
					});
				})
				->get()
				->map(function (MaterialReceiptItem $item) {
					return [
						'id' => $item->receipt->id,
						'type' => 'receipt',
						'date' => $item->created_at,
						'material' => $item->material,
						'roll' => $item->roll,
						'weight' => (float)$item->weight,
						'user' => $item->receipt->user,
						'comment' => $item->receipt->comment,
					];
				});

			/*
			 * Расходы.
			 */
			$issues = MaterialIssue::with([
				'material',
				'roll',
				'user',
			])
				->when($dateFrom, function ($query) use ($dateFrom) {
					$query->whereDate('created_at', '>=', $dateFrom);
				})
				->when($dateTo, function ($query) use ($dateTo) {
					$query->whereDate('created_at', '<=', $dateTo);
				})
				->when($materialId, function ($query) use ($materialId) {
					$query->where('material_id', $materialId);
				})
				->when($search, function ($query) use ($search) {
					$query->whereHas('material', function ($query) use ($search) {
						$query
							->where('name', 'ilike', '%' . $search . '%')
							->orWhere(
								'identifier',
								'ilike',
								'%' . $search . '%'
							);
					});
				})
				->get()
				->map(function (MaterialIssue $issue) {
					return [
						'id' => $issue->id,
						'type' => 'issue',
						'date' => $issue->created_at,
						'material' => $issue->material,
						'roll' => $issue->roll,
						'weight' => (float)$issue->weight,
						'user' => $issue->user,
						'comment' => $issue->comment,
					];
				});

			/*
			 * Объединяем приход и расход
			 * в единую историю движения.
			 */
			$movements = $receipts
				->concat($issues)
				->when($type, function ($collection) use ($type) {
					return $collection->where('type', $type);
				})
				->sortByDesc('date')
				->values();

			/*
			 * Материалы для фильтра.
			 */
			$materials = Material::query()
				->orderBy('name')
				->get();

			return view('material-movements.index', compact(
				'movements',
				'materials',
				'dateFrom',
				'dateTo',
				'type',
				'materialId',
				'search'
			));
		}
	}