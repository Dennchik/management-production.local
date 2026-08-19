<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
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

			$recentReceipts = MaterialReceipt::with([
					'material',
					'roll',
					'user',
			])
					->latest()
					->take(10)
					->get();

			return view('dashboard.index', compact(
					'materialsCount',
					'rollsCount',
					'totalWeight',
					'recentReceipts',
			));
		}
	}