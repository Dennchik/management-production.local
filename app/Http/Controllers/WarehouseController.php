<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
	use Illuminate\Http\Request;
	use Illuminate\View\View;

	class WarehouseController extends Controller
	{
		/**
		 * Отображает остатки материалов на складе.
		 */
		public function index(Request $request): View
		{
			$search = $request->input('search');
			$format = $request->input('format');
			$stock = $request->input('stock');
			$code = $request->input('code');

			$materialsQuery = Material::query()
				->withCount([
					'rolls as rolls_count' => function ($query) {
						$query->where('weight', '>', 0);
					},
				])
				->withSum('rolls as total_weight', 'weight');

			if ($search) {
				$materialsQuery->where(function ($query) use ($search) {
					$query
						->where('name', 'ilike', "%{$search}%")
						->orWhere('identifier', 'ilike', "%{$search}%");
				});
			}

			if ($format) {
				$materialsQuery->where('format', $format);
			}

			if ($code) {
				$materialsQuery->where('code', $code);
			}

			if ($stock === 'available') {
				$materialsQuery->whereHas('rolls', function ($query) {
					$query->where('weight', '>', 0);
				});
			}

			if ($stock === 'empty') {
				$materialsQuery->whereDoesntHave('rolls', function ($query) {
					$query->where('weight', '>', 0);
				});
			}

			$materials = $materialsQuery
				->orderBy('name')
				->get();

			$formats = Material::query()
				->select('format')
				->distinct()
				->orderBy('format')
				->pluck('format');

			$materialTypes = Material::query()
				->select('code')
				->distinct()
				->orderBy('code')
				->pluck('code');

			return view('warehouse.index', compact(
				'materials',
				'formats',
				'materialTypes',
				'search',
				'format',
				'stock',
				'code'
			));
		}
	}