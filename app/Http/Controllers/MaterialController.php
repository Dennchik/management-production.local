<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Illuminate\View\View;

	class MaterialController extends Controller
	{
		public function index(): View
		{
			$materials = Material::query()
					->orderBy('id')
					->get();

			return view('materials.index', compact('materials'));
		}

		public function create(): View
		{
			$number = Material::query()->count() + 1;

			return view('materials._create', [
					'number' => $number,
			]);
		}

		public function edit(Material $material): View
		{
			$number = Material::query()
					->where('id', '<=', $material->id)
					->count();

			return view('materials._edit', [
					'material' => $material,
					'number' => $number,
			]);
		}

		public function store(Request $request): JsonResponse
		{
			$validated = $request->validate([
					'name' => ['required', 'string', 'max:255'],
					'code' => ['required', 'string', 'max:255'],
					'grammage' => ['nullable', 'numeric', 'min:0'],
					'thickness' => ['nullable', 'numeric', 'min:0'],
					'format' => ['nullable', 'string', 'max:255'],
					'is_active' => ['boolean'],
			]);

			$grammage = $validated['grammage'] ?? null;
			$format = $validated['format'] ?? null;

			$identifier = null;

			if (
					$validated['code'] !== ''
					&& $grammage !== null
					&& $format !== null
			) {
				$grammageValue = (float) $grammage;

				$grammagePart = fmod($grammageValue, 1) === 0.0
						? (string) (int) $grammageValue
						: rtrim(
								rtrim(
										number_format($grammageValue, 2, '.', ''),
										'0'
								),
								'.'
						);

				$formatPart = preg_replace('/\D/', '', $format);

				$identifier = $validated['code'] . $grammagePart . $formatPart;
			}

			$material = Material::create([
					'name' => $validated['name'],
					'code' => $validated['code'],
					'grammage' => $grammage,
					'thickness' => $validated['thickness'] ?? null,
					'format' => $format,
					'identifier' => $identifier,
					'is_active' => $validated['is_active'] ?? false,
			]);

			return response()->json([
					'success' => true,
					'material' => $material,
			]);
		}

		public function show(Material $material): View
		{
			return view('materials._show', [
					'material' => $material,
			]);
		}

		public function update(Request $request, Material $material): JsonResponse
		{
			$validated = $request->validate([
					'name' => ['required', 'string', 'max:255'],
					'code' => ['required', 'string', 'max:255'],
					'grammage' => ['nullable', 'numeric', 'min:0'],
					'thickness' => ['nullable', 'numeric', 'min:0'],
					'format' => ['nullable', 'string', 'max:255'],
					'is_active' => ['boolean'],
			]);

			$grammage = $validated['grammage'] ?? null;
			$format = $validated['format'] ?? null;

			/*
			 * Если данных для генерации нового идентификатора недостаточно,
			 * сохраняем уже существующий идентификатор материала.
			 */
			$identifier = $material->identifier;

			if (
					$validated['code'] !== ''
					&& $grammage !== null
					&& $format !== null
			) {
				$grammageValue = (float) $grammage;

				$grammagePart = fmod($grammageValue, 1) === 0.0
						? (string) (int) $grammageValue
						: rtrim(
								rtrim(
										number_format($grammageValue, 2, '.', ''),
										'0'
								),
								'.'
						);

				$formatPart = preg_replace('/\D/', '', $format);

				$identifier = $validated['code'] . $grammagePart . $formatPart;
			}

			$material->update([
					'name' => $validated['name'],
					'code' => $validated['code'],
					'grammage' => $grammage,
					'thickness' => $validated['thickness'] ?? null,
					'format' => $format,
					'identifier' => $identifier,
					'is_active' => $validated['is_active'] ?? false,
			]);

			return response()->json([
					'success' => true,
					'material' => $material->fresh(),
			]);
		}

		public function delete(Material $material): View
		{
			return view('materials._delete', [
					'material' => $material,
			]);
		}

		public function destroy(Material $material): JsonResponse
		{
			$material->delete();

			return response()->json([
					'success' => true,
					'message' => 'Материал успешно удалён.',
			]);
		}
	}