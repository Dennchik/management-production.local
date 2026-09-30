<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\Material;
use App\Models\MaterialRoll;
use App\Models\ProductionOperation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialsCatalogsTrashTest extends TestCase
{
	use RefreshDatabase;

	/**
	 * Действующий пользователь с правом materials (все действия).
	 */
	private function materialManager(string $login = 'manager'): User
	{
		return $this->makeUserWithPermissions([
				'materials' => ['view', 'create', 'edit', 'delete'],
		], 'Менеджер материалов ' . uniqid(), $login . uniqid());
	}

	private function createCatalog(array $attributes = []): Catalog
	{
		return Catalog::create(array_merge([
				'name' => 'Каталог ' . uniqid(),
				'parent_id' => null,
				'sort_order' => 100,
				'is_active' => true,
		], $attributes));
	}

	private function createMaterial(array $attributes = []): Material
	{
		return Material::create(array_merge([
				'name' => 'Материал ' . uniqid(),
				'code' => 'PAP',
				'grammage' => 80,
				'thickness' => null,
				'format' => 640,
				'identifier' => null,
				'catalog_id' => null,
				'is_active' => true,
		], $attributes));
	}

	/*
	|--------------------------------------------------------------------------
	| Материалы: доступ
	|--------------------------------------------------------------------------
	*/

	public function test_guest_is_redirected_to_login_from_materials_pages(): void
	{
		$this->get(route('materials.create'))->assertRedirect(route('login'));
		$this->post(route('materials.store'))->assertRedirect(route('login'));
		$this->get(route('trash.index'))->assertRedirect(route('login'));
	}

	public function test_materials_routes_require_permission(): void
	{
		$user = $this->makeUserWithPermissions([], 'Без прав '.uniqid(), 'norights'.uniqid());
		$material = $this->createMaterial();

		$this->actingAs($user);

		$this->get(route('materials.create'))->assertForbidden();
		$this->get(route('materials.create-choice'))->assertForbidden();
		$this->post(route('materials.store'), ['name' => 'X', 'code' => 'X1'])->assertForbidden();
		$this->get(route('materials.show', $material))->assertForbidden();
		$this->get(route('materials.edit', $material))->assertForbidden();
		$this->put(route('materials.update', $material), ['name' => 'X', 'code' => 'X1'])->assertForbidden();
		$this->delete(route('materials.destroy', $material))->assertForbidden();
	}

	public function test_action_specific_permission_is_enforced(): void
	{
		// Только просмотр: прав на создание нет.
		$user = $this->makeUserWithPermissions(['materials' => 'view'], 'Только просмотр '.uniqid(), 'viewer'.uniqid());

		$this->actingAs($user)
				->post(route('materials.store'), ['name' => 'X', 'code' => 'X1'])
				->assertForbidden();
	}

	/*
	|--------------------------------------------------------------------------
	| Материалы: страницы
	|--------------------------------------------------------------------------
	*/

	public function test_create_renders_with_sequential_number(): void
	{
		$this->actingAs($this->materialManager());

		$this->createMaterial();
		$this->createMaterial();

		$this->get(route('materials.create'))
				->assertOk()
				->assertViewIs('materials._create')
				->assertViewHas('number', 3);
	}

	public function test_create_prefills_from_existing_material(): void
	{
		$this->actingAs($this->materialManager());

		$source = $this->createMaterial(['name' => 'Источник']);

		$this->get(route('materials.create', ['prefill_from' => $source->id]))
				->assertOk()
				->assertViewHas('prefill', fn ($prefill) => $prefill->id === $source->id);
	}

	public function test_create_choice_renders(): void
	{
		$this->actingAs($this->materialManager())
				->get(route('materials.create-choice'))
				->assertOk()
				->assertViewIs('materials._create-choice');
	}

	public function test_edit_renders_with_number_of_preceding_materials(): void
	{
		$this->actingAs($this->materialManager());

		$first = $this->createMaterial();
		$second = $this->createMaterial();

		$this->get(route('materials.edit', $second))
				->assertOk()
				->assertViewIs('materials._edit')
				->assertViewHas('material', fn ($m) => $m->id === $second->id)
				->assertViewHas('number', 2);

		$this->get(route('materials.edit', $first))->assertViewHas('number', 1);
	}

	public function test_show_renders_material_with_allowed_operations(): void
	{
		$this->actingAs($this->materialManager());

		$operation = ProductionOperation::create([
				'name' => 'Печать',
				'code' => 'PRINT',
				'is_active' => true,
		]);

		$material = $this->createMaterial();
		$material->allowedOperations()->sync([$operation->id]);

		$this->get(route('materials.show', $material))
				->assertOk()
				->assertViewIs('materials._show')
				->assertViewHas('material', fn ($m) => $m->id === $material->id);
	}

	public function test_delete_confirm_page_renders_rolls_count(): void
	{
		$this->actingAs($this->materialManager());

		$material = $this->createMaterial();
		MaterialRoll::create(['material_id' => $material->id, 'roll_number' => 'R-1', 'weight' => 10]);
		MaterialRoll::create(['material_id' => $material->id, 'roll_number' => 'R-2', 'weight' => 5]);

		$this->get(route('materials.delete', $material))
				->assertOk()
				->assertViewIs('materials._delete')
				->assertViewHas('rollsCount', 2);
	}

	/*
	|--------------------------------------------------------------------------
	| Материалы: store / update / destroy (JSON)
	|--------------------------------------------------------------------------
	*/

	public function test_store_creates_material_and_returns_json(): void
	{
		$catalog = $this->createCatalog();
		$operation = ProductionOperation::create(['name' => 'Резка', 'code' => 'CUT', 'is_active' => true]);

		$this->actingAs($this->materialManager())
				->post(route('materials.store'), [
						'name' => 'Бумага офсетная',
						'code' => 'OF',
						'grammage' => 80,
						'thickness' => null,
						'format' => 640,
						'catalog_id' => $catalog->id,
						'allowed_operations' => [$operation->id],
						'is_active' => true,
				])
				->assertOk()
				->assertJsonPath('success', true)
				->assertJsonPath('material.code', 'OF')
				->assertJsonPath('material.identifier', 'OF80640');

		$material = Material::query()->where('code', 'OF')->firstOrFail();
		$this->assertSame('OF80640', $material->identifier);
		$this->assertTrue($material->is_active);
		$this->assertSame($catalog->id, $material->catalog_id);
		$this->assertSame([$operation->id], $material->allowedOperations()->pluck('production_operations.id')->all());
	}

	public function test_store_without_enough_data_leaves_identifier_null(): void
	{
		$this->actingAs($this->materialManager())
				->post(route('materials.store'), [
						'name' => 'Без формата',
						'code' => 'NF',
						'format' => null,
				])
				->assertOk()
				->assertJsonPath('success', true);

		$this->assertNull(Material::query()->where('code', 'NF')->firstOrFail()->identifier);
	}

	public function test_store_validates_required_fields(): void
	{
		$this->actingAs($this->materialManager())
				->postJson(route('materials.store'), [])
				->assertStatus(422)
				->assertJsonValidationErrors(['name', 'code']);

		$this->assertDatabaseCount('materials', 0);
	}

	public function test_store_rejects_duplicate_identifier(): void
	{
		$this->createMaterial(['code' => 'OF', 'grammage' => 80, 'format' => 640, 'identifier' => 'OF80640']);

		$this->actingAs($this->materialManager())
				->postJson(route('materials.store'), [
						'name' => 'Дубль',
						'code' => 'OF',
						'grammage' => 80,
						'format' => 640,
				])
				->assertStatus(422)
				->assertJsonValidationErrors(['identifier']);
	}

	public function test_store_rejects_identifier_taken_by_trashed_material(): void
	{
		$this->createMaterial([
				'code' => 'OF',
				'grammage' => 80,
				'format' => 640,
				'identifier' => 'OF80640',
		])->delete();

		$this->actingAs($this->materialManager())
				->postJson(route('materials.store'), [
						'name' => 'Дубль корзины',
						'code' => 'OF',
						'grammage' => 80,
						'format' => 640,
				])
				->assertStatus(422)
				->assertJsonValidationErrors(['identifier'])
				->assertJsonPath('message', fn (string $message) => str_contains($message, 'в корзине'));

		$this->assertDatabaseCount('materials', 1);
	}

	public function test_store_rejects_trashed_catalog(): void
	{
		$catalog = $this->createCatalog();
		$catalog->delete();

		$this->actingAs($this->materialManager())
				->postJson(route('materials.store'), [
						'name' => 'X',
						'code' => 'X1',
						'catalog_id' => $catalog->id,
				])
				->assertStatus(422)
				->assertJsonValidationErrors(['catalog_id']);
	}

	public function test_store_saves_name_template_into_settings(): void
	{
		$this->actingAs($this->materialManager())
				->postJson(route('materials.store'), [
						'name' => 'Плёнка | 60 гр',
						'code' => 'PLN',
						'name_template' => 'Название | грамматура',
				])
				->assertOk();

		$this->assertSame('Название | грамматура', Setting::get('materials.name_template'));

		// Следующая форма создания подхватывает сохранённый шаблон.
		$this->get(route('materials.create'))
				->assertOk()
				->assertSee('Название | грамматура', false);
	}

	public function test_store_rejects_unknown_catalog_and_operations(): void
	{
		$this->actingAs($this->materialManager())
				->postJson(route('materials.store'), [
						'name' => 'X',
						'code' => 'X1',
						'catalog_id' => 99999,
				])
				->assertStatus(422)
				->assertJsonValidationErrors(['catalog_id']);

		$this->actingAs($this->materialManager())
				->postJson(route('materials.store'), [
						'name' => 'X',
						'code' => 'X1',
						'allowed_operations' => [99999],
				])
				->assertStatus(422)
				->assertJsonValidationErrors(['allowed_operations.0']);
	}

	public function test_update_refreshes_material_and_identifier(): void
	{
		$this->actingAs($this->materialManager());

		$material = $this->createMaterial([
				'code' => 'OF',
				'grammage' => 80,
				'format' => 640,
				'identifier' => 'OF80640',
				'is_active' => true,
		]);

		$this->put(route('materials.update', $material), [
				'name' => 'Новое имя',
				'code' => 'OF',
				'grammage' => 90,
				'format' => 880,
				'is_active' => false,
		])
				->assertOk()
				->assertJsonPath('success', true)
				->assertJsonPath('material.name', 'Новое имя')
				->assertJsonPath('material.identifier', 'OF90880');

		$material->refresh();
		$this->assertSame('OF90880', $material->identifier);
		$this->assertFalse($material->is_active);
	}

	public function test_update_allows_keeping_own_identifier_but_blocks_foreign(): void
	{
		$this->actingAs($this->materialManager());

		$material = $this->createMaterial(['code' => 'OF', 'grammage' => 80, 'format' => 640, 'identifier' => 'OF80640']);
		$other = $this->createMaterial(['code' => 'FL', 'grammage' => 12, 'format' => 500, 'identifier' => 'FL12500']);

		// Сохранение без изменений данных — идентификатор не считается занятым.
		$this->put(route('materials.update', $material), [
				'name' => $material->name,
				'code' => 'OF',
				'grammage' => 80,
				'format' => 640,
		])->assertOk();

		// Данные, совпадающие с другим материалом, отклоняются.
		$this->putJson(route('materials.update', $material), [
				'name' => $material->name,
				'code' => 'FL',
				'grammage' => 12,
				'format' => 500,
		])->assertStatus(422)->assertJsonValidationErrors(['identifier']);
	}

	public function test_destroy_sends_material_and_zero_weight_rolls_to_trash(): void
	{
		$this->actingAs($this->materialManager());

		$material = $this->createMaterial();
		$emptyRoll = MaterialRoll::create(['material_id' => $material->id, 'roll_number' => 'R-1', 'weight' => 0]);

		$this->delete(route('materials.destroy', $material))
				->assertOk()
				->assertJsonPath('success', true)
				->assertJsonPath('message', 'Материал удалён в корзину.');

		$this->assertSoftDeleted($material);
		$this->assertSoftDeleted($emptyRoll->refresh());
	}

	public function test_destroy_is_blocked_while_material_has_rolls_in_stock(): void
	{
		$this->actingAs($this->materialManager());

		$material = $this->createMaterial();
		$inStock = MaterialRoll::create(['material_id' => $material->id, 'roll_number' => 'R-1', 'weight' => 25]);

		$this->deleteJson(route('materials.destroy', $material))
				->assertStatus(422)
				->assertJsonPath('success', false);

		$this->assertNotSoftDeleted($material);
		$this->assertDatabaseHas('material_rolls', ['id' => $inStock->id, 'deleted_at' => null]);
	}

	/*
	|--------------------------------------------------------------------------
	| Каталоги
	|--------------------------------------------------------------------------
	*/

	public function test_catalogs_index_requires_permission_and_renders_paths(): void
	{
		$parent = $this->createCatalog(['name' => 'Бумага']);
		$this->createCatalog(['name' => 'Офсетная', 'parent_id' => $parent->id]);

		$this->actingAs($this->makeUserWithPermissions(['materials' => 'view'], 'Просмотр каталогов '.uniqid(), 'catalog-viewer'.uniqid()))
				->get(route('catalogs.index'))
				->assertOk()
				->assertViewIs('catalogs.index')
				->assertViewHas('hierarchyEnabled', true)
				->assertViewHas('paths', fn (array $paths) => $paths[$parent->id] === 'Бумага'
						&& isset($paths[$parent->id + 1]));

		$this->actingAs($this->makeUserWithPermissions([], 'Без прав '.uniqid(), 'no-catalog'.uniqid()))
				->get(route('catalogs.index'))
				->assertForbidden();
	}

	public function test_catalog_create_page_excludes_self_and_descendants_from_parents(): void
	{
		$this->actingAs($this->materialManager());

		$parent = $this->createCatalog(['name' => 'Родитель']);
		$child = $this->createCatalog(['name' => 'Потомок', 'parent_id' => $parent->id]);

		$this->get(route('catalogs.edit', $parent))
				->assertOk()
				->assertViewIs('catalogs._edit')
				// Сам каталог и все его потомки исключаются из списка родителей.
				->assertViewHas('parents', fn ($parents) => !$parents->contains('id', $parent->id)
						&& !$parents->contains('id', $child->id));
	}

	public function test_catalog_store_creates_catalog_and_returns_json(): void
	{
		$parent = $this->createCatalog();

		$this->actingAs($this->materialManager())
				->postJson(route('catalogs.store'), [
						'name' => 'Новый каталог',
						'parent_id' => $parent->id,
						'sort_order' => 10,
						'is_active' => true,
				])
				->assertOk()
				->assertJsonPath('success', true)
				->assertJsonPath('catalog.name', 'Новый каталог')
				->assertJsonPath('catalog.parent_id', $parent->id);

		$this->assertDatabaseHas('catalogs', ['name' => 'Новый каталог', 'parent_id' => $parent->id]);
	}

	public function test_catalog_store_defaults_sort_order_and_is_active(): void
	{
		$this->actingAs($this->materialManager())
				->postJson(route('catalogs.store'), ['name' => 'Простой'])
				->assertOk();

		$catalog = Catalog::query()->where('name', 'Простой')->firstOrFail();
		$this->assertSame(500, $catalog->sort_order);
		$this->assertFalse($catalog->is_active);
	}

	public function test_catalog_store_validates_name(): void
	{
		$this->actingAs($this->materialManager())
				->postJson(route('catalogs.store'), ['name' => ''])
				->assertStatus(422)
				->assertJsonValidationErrors(['name']);
	}

	public function test_catalog_store_rejects_unknown_parent(): void
	{
		$this->actingAs($this->materialManager())
				->postJson(route('catalogs.store'), ['name' => 'X', 'parent_id' => 99999])
				->assertStatus(422)
				->assertJsonValidationErrors(['parent_id']);
	}

	public function test_catalog_show_renders_full_path(): void
	{
		$this->actingAs($this->materialManager());

		$parent = $this->createCatalog(['name' => 'Род']);
		$child = $this->createCatalog(['name' => 'Ребёнок', 'parent_id' => $parent->id]);

		$this->get(route('catalogs.show', $child))
				->assertOk()
				->assertViewIs('catalogs._show')
				->assertViewHas('path', 'Род / Ребёнок');
	}

	public function test_catalog_update_changes_fields(): void
	{
		$this->actingAs($this->materialManager());

		$catalog = $this->createCatalog(['name' => 'Старое', 'sort_order' => 1, 'is_active' => true]);
		$parent = $this->createCatalog(['name' => 'Новый родитель']);

		$this->putJson(route('catalogs.update', $catalog), [
				'name' => 'Новое',
				'parent_id' => $parent->id,
				'sort_order' => 7,
				'is_active' => false,
		])
				->assertOk()
				->assertJsonPath('success', true)
				->assertJsonPath('catalog.name', 'Новое')
				->assertJsonPath('catalog.parent_id', $parent->id);

		$catalog->refresh();
		$this->assertSame(7, $catalog->sort_order);
		$this->assertFalse($catalog->is_active);
	}

	public function test_catalog_update_cannot_select_itself_as_parent(): void
	{
		$this->actingAs($this->materialManager());

		$catalog = $this->createCatalog(['name' => 'Себе родня']);

		$this->put(route('catalogs.update', $catalog), ['name' => $catalog->name, 'parent_id' => $catalog->id])
				->assertStatus(422);

		$this->assertNull($catalog->refresh()->parent_id);
	}

	public function test_catalog_update_cannot_select_own_descendant_as_parent(): void
	{
		$this->actingAs($this->materialManager());

		$parent = $this->createCatalog(['name' => 'Вершина']);
		$child = $this->createCatalog(['name' => 'Середина', 'parent_id' => $parent->id]);
		$grandchild = $this->createCatalog(['name' => 'Низ', 'parent_id' => $child->id]);

		// Потомок второго уровня не может стать родителем вершины.
		$this->put(route('catalogs.update', $parent), ['name' => $parent->name, 'parent_id' => $grandchild->id])
				->assertStatus(422);

		$this->assertNull($parent->refresh()->parent_id);
	}

	public function test_catalog_hierarchy_toggle_returns_json(): void
	{
		$this->actingAs($this->materialManager());

		$response = $this->postJson(route('catalogs.hierarchy-toggle'));

		$response->assertOk()->assertJsonPath('success', true);

		/*
		 * Фактическое поведение: Catalog::hierarchyEnabled() жёстко возвращает true,
		 * поэтому ответ всегда hierarchy_enabled = false (инвертированное значение),
		 * в настройку сохраняется '0', но сама иерархия остаётся включённой.
		 */
		$response->assertJsonPath('hierarchy_enabled', false);
		$this->assertSame('0', Setting::get(Catalog::HIERARCHY_SETTING_KEY));
		$this->assertTrue(Catalog::hierarchyEnabled());
	}

	public function test_catalog_destroy_blocked_while_nested_catalogs_exist(): void
	{
		$this->actingAs($this->materialManager());

		$parent = $this->createCatalog();
		$this->createCatalog(['name' => 'Вложенный', 'parent_id' => $parent->id]);

		$this->deleteJson(route('catalogs.destroy', $parent))
				->assertStatus(422)
				->assertJsonPath('success', false)
				->assertJsonPath('message', 'Сначала удалите вложенные каталоги.');

		$this->assertNotSoftDeleted($parent);
	}

	public function test_catalog_destroy_blocked_while_materials_in_stock(): void
	{
		$this->actingAs($this->materialManager());

		$catalog = $this->createCatalog();
		$material = $this->createMaterial(['catalog_id' => $catalog->id]);
		MaterialRoll::create(['material_id' => $material->id, 'roll_number' => 'R-1', 'weight' => 10]);

		$this->deleteJson(route('catalogs.destroy', $catalog))
				->assertStatus(422)
				->assertJsonPath('success', false);

		$this->assertNotSoftDeleted($catalog);
		$this->assertNotSoftDeleted($material);
	}

	public function test_catalog_destroy_moves_catalog_with_empty_weight_materials_to_trash(): void
	{
		$this->actingAs($this->materialManager());

		$catalog = $this->createCatalog();
		$material = $this->createMaterial(['catalog_id' => $catalog->id]);
		$roll = MaterialRoll::create(['material_id' => $material->id, 'roll_number' => 'R-1', 'weight' => 0]);

		$this->deleteJson(route('catalogs.destroy', $catalog))
				->assertOk()
				->assertJsonPath('success', true)
				->assertJsonPath('message', 'Каталог удалён в корзину. Вместе с ним удалено материалов: 1.');

		$this->assertSoftDeleted($catalog);
		$this->assertSoftDeleted($material);
		$this->assertSoftDeleted($roll->refresh());
	}

	public function test_catalog_destroy_with_no_materials_reports_plain_message(): void
	{
		$this->actingAs($this->materialManager());

		$catalog = $this->createCatalog();

		$this->deleteJson(route('catalogs.destroy', $catalog))
				->assertOk()
				->assertJsonPath('success', true)
				->assertJsonPath('message', 'Каталог удалён в корзину.');
	}

	public function test_catalog_delete_confirm_page_lists_deletable_materials(): void
	{
		$this->actingAs($this->materialManager());

		$catalog = $this->createCatalog();
		$deletable = $this->createMaterial(['catalog_id' => $catalog->id]);
		MaterialRoll::create(['material_id' => $deletable->id, 'roll_number' => 'R-1', 'weight' => 0]);
		$inStock = $this->createMaterial(['catalog_id' => $catalog->id]);
		MaterialRoll::create(['material_id' => $inStock->id, 'roll_number' => 'R-2', 'weight' => 5]);

		$this->get(route('catalogs.delete', $catalog))
				->assertOk()
				->assertViewIs('catalogs._delete')
				->assertViewHas('deletableMaterials', fn ($materials) => $materials->contains('id', $deletable->id)
						&& !$materials->contains('id', $inStock->id));
	}

	/*
	|--------------------------------------------------------------------------
	| Корзина
	|--------------------------------------------------------------------------
	*/

	public function test_trash_index_requires_permission_and_lists_trashed_records(): void
	{
		$catalog = $this->createCatalog();
		$material = $this->createMaterial(['catalog_id' => $catalog->id]);
		$orphan = $this->createMaterial(['name' => 'Одиночка']);

		$catalog->delete();
		$material->delete();
		$orphan->delete();

		$this->actingAs($this->makeUserWithPermissions(['materials' => 'view'], 'Корзинщик '.uniqid(), 'trash-viewer'.uniqid()))
				->get(route('trash.index'))
				->assertOk()
				->assertViewIs('trash.index')
				->assertViewHas('catalogs', fn ($catalogs) => $catalogs->contains('id', $catalog->id))
				->assertViewHas('materials', fn ($materials) => $materials->pluck('id')->contains($material->id)
						&& $materials->pluck('id')->contains($orphan->id));

		$this->actingAs($this->makeUserWithPermissions([], 'Без прав '.uniqid(), 'no-trash'.uniqid()))
				->get(route('trash.index'))
				->assertForbidden();
	}

	public function test_restore_material_brings_back_material_and_its_rolls(): void
	{
		$this->actingAs($this->materialManager('restorer'));

		$material = $this->createMaterial(['name' => 'Удалённый']);
		$roll = MaterialRoll::create(['material_id' => $material->id, 'roll_number' => 'R-1', 'weight' => 3]);

		/*
		 * Прямое удаление модели не трогает рулоны: они уходят в корзину
		 * только при удалении через контроллер. Удаляем рулон вручную.
		 */
		$material->delete();
		$roll->delete();

		$this->assertSoftDeleted($material);
		$this->assertSoftDeleted($roll->refresh());

		$this->postJson(route('trash.restore', ['type' => 'materials', 'id' => $material->id]))
				->assertOk()
				->assertJsonPath('success', true)
				->assertJsonPath('message', 'Запись восстановлена из корзины.');

		$this->assertNotSoftDeleted($material->refresh());
		$this->assertNotSoftDeleted($roll->refresh());
	}

	public function test_restore_catalog_also_restores_its_materials_and_rolls(): void
	{
		$this->actingAs($this->materialManager('restorer'));

		$catalog = $this->createCatalog();
		$material = $this->createMaterial(['catalog_id' => $catalog->id]);
		$roll = MaterialRoll::create(['material_id' => $material->id, 'roll_number' => 'R-1', 'weight' => 0]);

		// Удаление каталога моделью не каскадируется: удаляем вручную всё поддерево.
		$catalog->delete();
		$material->delete();
		$roll->delete();

		$this->assertSoftDeleted($catalog);
		$this->assertSoftDeleted($material);
		$this->assertSoftDeleted($roll->refresh());

		$this->postJson(route('trash.restore', ['type' => 'catalogs', 'id' => $catalog->id]))
				->assertOk()
				->assertJsonPath('success', true);

		$this->assertNotSoftDeleted($catalog->refresh());
		$this->assertNotSoftDeleted($material->refresh());
		$this->assertNotSoftDeleted($roll->refresh());
	}

	public function test_restore_unknown_type_or_id_fails_gracefully(): void
	{
		$this->actingAs($this->materialManager('restorer'));

		$this->postJson(route('trash.restore', ['type' => 'widgets', 'id' => 1]))
				->assertStatus(422)
				->assertJsonPath('success', false);

		$this->postJson(route('trash.restore', ['type' => 'materials', 'id' => 99999]))
				->assertStatus(422)
				->assertJsonPath('success', false);

		$this->postJson(route('trash.restore', ['type' => 'materials', 'id' => $this->createMaterial()->id]))
				->assertStatus(422)
				->assertJsonPath('success', false);
	}

	public function test_destroy_material_removes_it_and_rolls_permanently(): void
	{
		$this->actingAs($this->materialManager('purger'));

		$material = $this->createMaterial(['name' => 'Навсегда']);
		$roll = MaterialRoll::create(['material_id' => $material->id, 'roll_number' => 'R-1', 'weight' => 0]);

		$material->delete();

		$this->deleteJson(route('trash.destroy', ['type' => 'materials', 'id' => $material->id]))
				->assertOk()
				->assertJsonPath('success', true)
				->assertJsonPath('message', 'Запись удалена навсегда.');

		$this->assertDatabaseMissing('materials', ['id' => $material->id]);
		$this->assertDatabaseMissing('material_rolls', ['id' => $roll->id]);
	}

	public function test_destroy_catalog_removes_nested_trashed_materials_permanently(): void
	{
		$this->actingAs($this->materialManager('purger'));

		$catalog = $this->createCatalog();
		$material = $this->createMaterial(['catalog_id' => $catalog->id]);
		$roll = MaterialRoll::create(['material_id' => $material->id, 'roll_number' => 'R-1', 'weight' => 0]);

		// Удаление каталога моделью не каскадируется: удаляем вручную всё поддерево.
		$catalog->delete();
		$material->delete();
		$roll->delete();

		$this->deleteJson(route('trash.destroy', ['type' => 'catalogs', 'id' => $catalog->id]))
				->assertOk()
				->assertJsonPath('success', true);

		$this->assertDatabaseMissing('catalogs', ['id' => $catalog->id]);
		$this->assertDatabaseMissing('materials', ['id' => $material->id]);
		$this->assertDatabaseMissing('material_rolls', ['id' => $roll->id]);
	}

	public function test_destroy_does_not_touch_materials_of_other_catalogs(): void
	{
		$this->actingAs($this->materialManager('purger'));

		$catalog = $this->createCatalog();
		$otherCatalog = $this->createCatalog();
		$otherMaterial = $this->createMaterial(['catalog_id' => $otherCatalog->id]);
		$otherCatalog->delete();
		// Модельное удаление не каскадируется — удаляем материал вручную.
		$otherMaterial->delete();
		$catalog->delete();

		$this->deleteJson(route('trash.destroy', ['type' => 'catalogs', 'id' => $catalog->id]))->assertOk();

		$this->assertSoftDeleted($otherMaterial->refresh());
	}

	public function test_destroy_unknown_type_or_id_fails_gracefully(): void
	{
		$this->actingAs($this->materialManager('purger'));

		$this->deleteJson(route('trash.destroy', ['type' => 'widgets', 'id' => 1]))
				->assertStatus(422)
				->assertJsonPath('success', false);

		$this->deleteJson(route('trash.destroy', ['type' => 'catalogs', 'id' => 99999]))
				->assertStatus(422)
				->assertJsonPath('success', false);
	}

	public function test_trash_restore_and_destroy_require_delete_permission(): void
	{
		$user = $this->makeUserWithPermissions(['materials' => 'view'], 'Только просмотр '.uniqid(), 'view-only'.uniqid());
		$material = $this->createMaterial();
		$material->delete();

		$this->actingAs($user);

		$this->post(route('trash.restore', ['type' => 'materials', 'id' => $material->id]))->assertForbidden();
		$this->delete(route('trash.destroy', ['type' => 'materials', 'id' => $material->id]))->assertForbidden();

		$this->assertSoftDeleted($material->refresh());
	}

	/*
	|--------------------------------------------------------------------------
	| Полный цикл: удаление в корзину и восстановление
	|--------------------------------------------------------------------------
	*/

	public function test_full_roundtrip_delete_then_restore_catalog_tree(): void
	{
		$this->actingAs($this->materialManager('fullcycle'));

		$parent = $this->createCatalog(['name' => 'Вершина']);
		$child = $this->createCatalog(['name' => 'Ветка', 'parent_id' => $parent->id]);
		$material = $this->createMaterial(['catalog_id' => $child->id]);
		$roll = MaterialRoll::create(['material_id' => $material->id, 'roll_number' => 'R-9', 'weight' => 0]);

		// Удаление листа невозможно, пока есть вложенный каталог.
		$this->deleteJson(route('catalogs.destroy', $parent))->assertStatus(422);

		// Удаляем снизу вверх.
		$this->deleteJson(route('catalogs.destroy', $child))->assertOk();
		$this->deleteJson(route('catalogs.destroy', $parent))->assertOk();

		$this->assertSoftDeleted($parent);
		$this->assertSoftDeleted($child);
		$this->assertSoftDeleted($material);
		$this->assertSoftDeleted($roll->refresh());

		/*
		 * Фактическое поведение: восстановление каталога возвращает сам каталог
		 * и его материалы (с рулонами), но НЕ вложенные каталоги — они остаются
		 * в корзине и восстанавливаются отдельным запросом.
		 */
		$this->postJson(route('trash.restore', ['type' => 'catalogs', 'id' => $parent->id]))->assertOk();

		$this->assertNotSoftDeleted($parent->refresh());
		$this->assertSoftDeleted($child->refresh());
		$this->assertSame($parent->id, $child->refresh()->parent_id);

		// Восстановление вложенного каталога возвращает его материалы и рулоны.
		$this->postJson(route('trash.restore', ['type' => 'catalogs', 'id' => $child->id]))->assertOk();

		$this->assertNotSoftDeleted($child->refresh());
		$this->assertSame($parent->id, $child->refresh()->parent_id);
		$this->assertNotSoftDeleted($material->refresh());
		$this->assertNotSoftDeleted($roll->refresh());
	}
}
