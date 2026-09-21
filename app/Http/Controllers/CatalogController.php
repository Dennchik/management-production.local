<?php

	namespace App\Http\Controllers;

	use App\Models\Catalog;
	use App\Models\Setting;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Illuminate\View\View;

	class CatalogController extends Controller
	{
		public function index(): View
		{
			$hierarchyEnabled = Catalog::hierarchyEnabled();

			$catalogs = Catalog::query()
					->withCount('materials')
					->orderBy('sort_order')
					->orderBy('name')
					->get();

			$paths = Catalog::pathMap();

			return view('catalogs.index', [
					'catalogs' => $catalogs,
					'paths' => $paths,
					'hierarchyEnabled' => $hierarchyEnabled,
			]);
		}

		public function create(): View
		{
			$selectedParentId = request('parent');

			return view('catalogs._create', [
					'parents' => Catalog::selectableParents(),
					'selectedParentId' => $selectedParentId !== null ? (int) $selectedParentId : null,
			]);
		}

		public function store(Request $request): JsonResponse
		{
			$validated = $this->validateCatalog($request);

			$catalog = Catalog::create([
					'name' => $validated['name'],
					'parent_id' => $validated['parent_id'] ?? null,
					'sort_order' => $validated['sort_order'] ?? 500,
					'is_active' => $validated['is_active'] ?? false,
			]);

			return response()->json([
					'success' => true,
					'catalog' => $catalog,
			]);
		}

		public function show(Catalog $catalog): View
		{
			return view('catalogs._show', [
					'catalog' => $catalog,
					'path' => Catalog::pathMap()[$catalog->id] ?? $catalog->name,
			]);
		}

		public function edit(Catalog $catalog): View
		{
			return view('catalogs._edit', [
					'catalog' => $catalog,
					'parents' => Catalog::selectableParents($catalog->id),
			]);
		}

		public function update(Request $request, Catalog $catalog): JsonResponse
		{
			$validated = $this->validateCatalog($request, $catalog);

			$catalog->update([
					'name' => $validated['name'],
					'parent_id' => $validated['parent_id'] ?? null,
					'sort_order' => $validated['sort_order'] ?? $catalog->sort_order,
					'is_active' => $validated['is_active'] ?? false,
			]);

			return response()->json([
					'success' => true,
					'catalog' => $catalog->fresh(),
			]);
		}

		public function delete(Catalog $catalog): View
		{
			return view('catalogs._delete', [
					'catalog' => $catalog,
			]);
		}

		public function destroy(Catalog $catalog): JsonResponse
		{
			if (Catalog::descendantIds($catalog->id)->isNotEmpty()) {
				return response()->json([
						'success' => false,
						'message' => 'Сначала удалите вложенные каталоги.',
				], 422);
			}

			if ($catalog->materials()->exists()) {
				return response()->json([
						'success' => false,
						'message' => 'В каталоге есть материалы. Сначала уберите их из каталога.',
				], 422);
			}

			$catalog->delete();

			return response()->json([
					'success' => true,
					'message' => 'Каталог успешно удалён.',
			]);
		}

		public function toggleHierarchy(): JsonResponse
		{
			$enabled = !Catalog::hierarchyEnabled();

			Setting::set(Catalog::HIERARCHY_SETTING_KEY, $enabled ? '1' : '0');

			return response()->json([
					'success' => true,
					'hierarchy_enabled' => $enabled,
			]);
		}

		/**
		 * Валидация данных каталога, включая защиту от циклической вложенности.
		 */
		private function validateCatalog(Request $request, ?Catalog $catalog = null): array
		{
			$validated = $request->validate([
					'name' => ['required', 'string', 'max:255'],
					'parent_id' => ['nullable', 'integer', 'exists:catalogs,id'],
					'sort_order' => ['nullable', 'integer', 'min:0'],
					'is_active' => ['boolean'],
			]);

			$parentId = $validated['parent_id'] ?? null;

			if ($catalog !== null && $parentId !== null) {
				if ($parentId === $catalog->id || Catalog::descendantIds($catalog->id)->contains($parentId)) {
					abort(422, 'Нельзя вложить каталог в самого себя или в своего потомка.');
				}
			}

			// При выключенной иерархии вложенность запрещена.
			if (!Catalog::hierarchyEnabled()) {
				$validated['parent_id'] = null;
			}

			return $validated;
		}
	}
