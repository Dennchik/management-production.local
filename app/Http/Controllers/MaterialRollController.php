<?php

	namespace App\Http\Controllers;

	use App\Models\MaterialRoll;
	use Illuminate\View\View;

	class MaterialRollController extends Controller
	{
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

			/*
			 * Первоначальный вес рулона определяется
			 * по приходным позициям.
			 */
			$initialWeight = (float)$roll->receiptItems->sum('weight');

			/*
			 * Текущий остаток хранится непосредственно
			 * в физическом рулоне.
			 */
			$currentWeight = (float)$roll->weight;

			/*
			 * Общий расход рулона.
			 */
			$issuedWeight = max(
				0,
				$initialWeight - $currentWeight
			);

			/*
			 * Количество операций расхода.
			 */
			$issuesCount = $roll->issues->count();

			/*
			 * Формируем единую историю движений рулона.
			 */
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

			/*
			 * Рассчитываем остаток после каждой операции.
			 */
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
	}