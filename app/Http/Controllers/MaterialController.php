<?php

	namespace App\Http\Controllers;

	use App\Models\Catalog;
	use App\Models\Material;
	use App\Models\ProductionOperation;
	use App\Models\Setting;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Illuminate\Validation\Rule;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Validation\ValidationException;
	use Illuminate\View\View;

	class MaterialController extends Controller
	{
		/** Ключ настройки шаблона названия материала. */
		public const NAME_TEMPLATE_SETTING_KEY = 'materials.name_template';

		public const DEFAULT_NAME_TEMPLATE = 'Название | грамматура | толщина | формат';

		public function create(): View
		{
			$number = Material::query()->count() + 1;

			// Предзаполнение данными существующего материала —
			// так из линии резки создаётся новый формат того же материала.
			$prefillFrom = request('prefill_from');

			// Копирование: форма создания, заполненная данными источника
			// целиком, включая формат и статус.
			$copyFrom = request('copy_from');

			$sourceId = $copyFrom !== null && $copyFrom !== ''
					? $copyFrom
					: $prefillFrom;

			$prefill = $sourceId !== null && $sourceId !== ''
					? Material::query()->findOrFail((int) $sourceId)
					: null;

			return view('materials._create', [
					'number' => $number,
					'nameTemplate' => $this->nameTemplate(),
					'catalogOptions' => $this->catalogOptions(),
					'selectedCatalogId' => $prefill?->catalog_id ?? request('catalog'),
					'productionLines' => $this->productionLines(),
					'prefill' => $prefill,
					'isCopy' => $copyFrom !== null && $copyFrom !== '',
					'selectedOperationIds' => $prefill?->allowedOperations()
						->pluck('production_operations.id')
						->all() ?? [],
			]);
		}

		/**
		 * Окно выбора: создать каталог или материал.
		 */
		public function createChoice(): View
		{
			return view('materials._create-choice');
		}

		public function edit(Material $material): View
		{
			$number = Material::query()
					->where('id', '<=', $material->id)
					->count();

			return view('materials._edit', [
					'material' => $material,
					'number' => $number,
					'nameTemplate' => $this->nameTemplate(),
					'catalogOptions' => $this->catalogOptions(),
					'productionLines' => $this->productionLines(),
					'selectedOperationIds' => $material->allowedOperations()->pluck(
							'production_operations.id'
					)->all(),
			]);
		}

		public function store(Request $request): JsonResponse
		{
			$validated = $this->validateMaterial($request);

			$identifier = $this->materialIdentifier($validated);

			$this->ensureIdentifierIsFree($identifier);

			$this->saveNameTemplate($validated);

			$material = Material::create([
					'name' => $validated['name'],
					'code' => $validated['code'],
					'grammage' => $validated['grammage'] ?? null,
					'thickness' => $validated['thickness'] ?? null,
					'format' => $validated['format'] ?? null,
					'identifier' => $identifier,
					'catalog_id' => $validated['catalog_id'] ?? null,
					'is_active' => $validated['is_active'] ?? false,
			]);

			$material->allowedOperations()->sync($validated['allowed_operations'] ?? []);

			return response()->json([
					'success' => true,
					'material' => $material,
			]);
		}

		public function show(Material $material): View
		{
			return view('materials._show', [
				'material' => $material->load('allowedOperations'),
			]);
		}

		public function update(Request $request, Material $material): JsonResponse
		{
			$validated = $this->validateMaterial($request);

			$this->saveNameTemplate($validated);

			/*
			 * Идентификатор пересчитывается при каждом сохранении;
			 * если данных (код + грамматура/толщина + формат) недостаточно,
			 * он остаётся пустым.
			 */
			$identifier = $this->materialIdentifier($validated);

			$this->ensureIdentifierIsFree($identifier, $material->id);

			$material->update([
					'name' => $validated['name'],
					'code' => $validated['code'],
					'grammage' => $validated['grammage'] ?? null,
					'thickness' => $validated['thickness'] ?? null,
					'format' => $validated['format'] ?? null,
					'identifier' => $identifier,
					'catalog_id' => $validated['catalog_id'] ?? null,
					'is_active' => $validated['is_active'] ?? false,
			]);

			$material->allowedOperations()->sync($validated['allowed_operations'] ?? []);

			return response()->json([
					'success' => true,
					'material' => $material->fresh(),
			]);
		}

		public function delete(Material $material): View
		{
			return view('materials._delete', [
					'material' => $material,
					'rollsCount' => $material->rolls()->count(),
			]);
		}

		public function destroy(Material $material): JsonResponse
		{
			$material->load('rolls');

			/*
			 * Удаление запрещено, пока у материала есть рулоны
			 * в наличии: сначала корректировочный ордер.
			 */
			if ($material->rolls->contains(static fn ($roll) => (float) $roll->weight > 0)) {
				return response()->json([
						'success' => false,
						'message' => 'У материала есть рулоны в наличии, требуется корректировочный ордер.',
				], 422);
			}

			// Материал удаляется в корзину вместе со своими рулонами.
			DB::transaction(function () use ($material) {
				$material->rolls()->delete();
				$material->delete();
			});

			return response()->json([
					'success' => true,
					'message' => 'Материал удалён в корзину.',
			]);
		}

		/**
		 * Общие правила валидации формы материала (создание и редактирование).
		 */
		private function validateMaterial(Request $request): array
		{
			return $request->validate([
					'name' => ['required', 'string', 'max:255'],
					'code' => ['required', 'string', 'max:10'],
					'grammage' => ['nullable', 'numeric', 'min:0'],
					'thickness' => ['nullable', 'numeric', 'min:0'],
					'format' => ['nullable', 'integer', 'min:0', 'max:65535'],
					// Удалённые (в корзине) каталоги нельзя выбирать.
					'catalog_id' => [
							'nullable',
							'integer',
							Rule::exists('catalogs', 'id')->whereNull('deleted_at'),
					],
					'allowed_operations' => ['nullable', 'array'],
					'allowed_operations.*' => ['integer', 'exists:production_operations,id'],
					'is_active' => ['boolean'],
					'name_template' => ['nullable', 'string', 'max:255'],
			]);
		}

		/**
		 * Запоминает шаблон названия материала как глобальную настройку.
		 */
		private function saveNameTemplate(array $validated): void
		{
			if (array_key_exists('name_template', $validated)
					&& $validated['name_template'] !== null
					&& trim($validated['name_template']) !== '') {
				Setting::set(
						self::NAME_TEMPLATE_SETTING_KEY,
						trim($validated['name_template'])
				);
			}
		}

		/**
		 * Текущий шаблон названия материала.
		 */
		private function nameTemplate(): string
		{
			return Setting::get(
					self::NAME_TEMPLATE_SETTING_KEY,
					self::DEFAULT_NAME_TEMPLATE
			);
		}

		/**
		 * Идентификатор из данных формы материала.
		 */		private function materialIdentifier(array $validated): ?string
		{
			return Material::composeIdentifier(
				$validated['code'],
				$validated['grammage'] ?? null,
				$validated['thickness'] ?? null,
				$validated['format'] ?? null
			);
		}

		/**
		 * Гарантирует, что вычисленный идентификатор не занят другим материалом.
		 */
		private function ensureIdentifierIsFree(?string $identifier, ?int $ignoreId = null): void
		{
			if ($identifier === null) {
				return;
			}

			// Уникальный индекс в БД действует и на записи в корзине,
			// поэтому проверяем с withTrashed и объясняем это пользователю.
			$trashed = Material::withTrashed()
					->where('identifier', $identifier)
					->whereNotNull('deleted_at')
					->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
					->first();

			if ($trashed !== null) {
				throw ValidationException::withMessages([
						'identifier' => 'Материал с идентификатором «' . $identifier . '» находится в корзине. Восстановите его или удалите из корзины безвозвратно.',
				]);
			}

			$exists = Material::query()
					->where('identifier', $identifier)
					->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
					->exists();

			if ($exists) {
				throw ValidationException::withMessages([
						'identifier' => 'Материал с идентификатором «' . $identifier . '» уже существует.',
				]);
			}
		}

		/**
		 * Каталоги для выбора в форме материала.
		 */
		private function catalogOptions(): array
		{
			return Catalog::selectableParents()
					->mapWithKeys(static fn (Catalog $catalog) => [
							$catalog->id => Catalog::pathMap()[$catalog->id] ?? $catalog->name,
					])
					->all();
		}

		/**
		 * Активные технологические линии для блока разрешённых операций.
		 */
		private function productionLines()
		{
			return ProductionOperation::query()
					->where('is_active', true)
					->orderBy('id')
					->get();
		}
	}
