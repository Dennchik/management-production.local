<?php

	namespace App\Http\Controllers;

	use App\Models\Material;

	class LaminationController extends Controller
	{
		public function index()
		{
			$laminations = collect();

			return view('lamination.index', compact('laminations'));
		}

		public function create()
		{
			$baseMaterials = Material::query()
					->where('lamination_base_allowed', true)
					->orderBy('name')
					->orderBy('identifier')
					->get();

			$laminationMaterials = Material::query()
					->where('lamination_allowed', true)
					->where('lamination_base_allowed', false)
					->orderBy('name')
					->orderBy('identifier')
					->get();

			return view('lamination.create', compact(
					'baseMaterials',
					'laminationMaterials'
			));
		}

		public function store()
		{
			return redirect()
					->route('lamination.index')
					->with('success', 'Задание на ламинацию создано.');
		}
	}