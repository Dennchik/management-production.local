<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\Material;
use App\Models\ProductionLine;
use App\Models\ProductionOperation;
use App\Models\ProductionOperationComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductionOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeOperator(string $login = 'operator'): User
    {
        return $this->makeUserWithPermissions([
            'operations' => ['view', 'create', 'edit', 'delete'],
        ], 'Оператор производства', $login);
    }

    protected function makeMaterial(array $attributes = []): Material
    {
        static $sequence = 0;
        $sequence++;

        return Material::create(array_merge([
            'name' => 'Материал ' . $sequence,
            'code' => 'M' . $sequence,
            'format' => 700,
            'identifier' => 'ID-' . $sequence,
            'is_active' => true,
        ], $attributes));
    }

    protected function makeOperation(array $attributes = []): ProductionOperation
    {
        static $sequence = 0;
        $sequence++;

        return ProductionOperation::create(array_merge([
            'name' => 'Операция ' . $sequence,
            'code' => 'OP-' . $sequence,
            'is_active' => true,
            'is_cutting' => false,
        ], $attributes));
    }

    protected function makeComponent(ProductionOperation $operation, Material $material, string $direction, array $attributes = []): ProductionOperationComponent
    {
        return ProductionOperationComponent::create(array_merge([
            'operation_id' => $operation->id,
            'material_id' => $material->id,
            'direction' => $direction,
            'quantity' => null,
            'unit' => 'kg',
            'is_required' => true,
            'sort_order' => 0,
        ], $attributes));
    }

    /*
    |--------------------------------------------------------------------------
    | Доступ (аутентификация и права)
    |--------------------------------------------------------------------------
    */

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('production.operations.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_forbids_user_without_permission(): void
    {
        $user = $this->makeUserWithPermissions(['operations' => []], 'Без прав', 'noroles');
        $this->actingAs($user);

        $this->get(route('production.operations.index'))
            ->assertForbidden();
    }

    public function test_store_forbids_user_without_create_permission(): void
    {
        $user = $this->makeUserWithPermissions(['operations' => ['view']], 'Только просмотр', 'viewer');
        $this->actingAs($user);

        $this->postJson(route('production.operations.store'), [
            'name' => 'Операция',
            'code' => 'OP-X',
        ])->assertForbidden();

        $this->assertDatabaseMissing('production_operations', ['code' => 'OP-X']);
    }

    public function test_destroy_forbids_user_without_delete_permission(): void
    {
        $user = $this->makeUserWithPermissions(['operations' => ['view']], 'Только просмотр', 'viewer2');
        $this->actingAs($user);

        $operation = $this->makeOperation();

        $this->deleteJson(route('production.operations.destroy', $operation))
            ->assertForbidden();

        $this->assertDatabaseHas('production_operations', ['id' => $operation->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD операций
    |--------------------------------------------------------------------------
    */

    public function test_index_shows_operations(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();

        $this->get(route('production.operations.index'))
            ->assertOk()
            ->assertViewIs('production.operations.index')
            ->assertViewHas('operations', function ($operations) use ($operation) {
                return $operations->contains($operation);
            });
    }

    public function test_create_page_shows_active_materials(): void
    {
        $this->actingAs($this->makeOperator());

        $active = $this->makeMaterial(['is_active' => true]);
        $inactive = $this->makeMaterial(['is_active' => false]);

        $this->get(route('production.operations.create'))
            ->assertOk()
            ->assertViewIs('production.operations.create')
            ->assertViewHas('materials', function ($materials) use ($active, $inactive) {
                return $materials->contains($active) && !$materials->contains($inactive);
            });
    }

    public function test_store_creates_operation_with_components(): void
    {
        $this->actingAs($this->makeOperator());

        $input = $this->makeMaterial();
        $output = $this->makeMaterial();

        $response = $this->postJson(route('production.operations.store'), [
            'name' => 'Резка бумаги',
            'code' => 'CUT-1',
            'description' => 'Описание',
            'is_active' => true,
            'is_cutting' => true,
            'inputs' => [
                [
                    'material_id' => $input->id,
                    'quantity' => 1.5,
                    'unit' => 'kg',
                    'is_required' => true,
                    'sort_order' => 0,
                ],
            ],
            'outputs' => [
                [
                    'material_id' => $output->id,
                    'quantity' => 2,
                    'unit' => 'pcs',
                    'is_required' => false,
                    'sort_order' => 1,
                    'comment' => 'готовая продукция',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $operation = ProductionOperation::where('code', 'CUT-1')->firstOrFail();
        $this->assertSame('Резка бумаги', $operation->name);
        $this->assertTrue($operation->is_cutting);

        $this->assertDatabaseHas('production_operation_components', [
            'operation_id' => $operation->id,
            'material_id' => $input->id,
            'direction' => 'input',
            'unit' => 'kg',
        ]);

        $this->assertDatabaseHas('production_operation_components', [
            'operation_id' => $operation->id,
            'material_id' => $output->id,
            'direction' => 'output',
            'is_required' => false,
            'comment' => 'готовая продукция',
        ]);
    }

    public function test_store_links_output_operations(): void
    {
        $this->actingAs($this->makeOperator());

        $next = $this->makeOperation();

        $this->postJson(route('production.operations.store'), [
            'name' => 'Печать',
            'code' => 'PRINT-1',
            'output_operations' => [$next->id],
        ])->assertOk();

        $operation = ProductionOperation::where('code', 'PRINT-1')->firstOrFail();

        $this->assertTrue($operation->outputOperations->contains($next));
    }

    public function test_store_validation_fails_without_name_and_code(): void
    {
        $this->actingAs($this->makeOperator());

        $this->postJson(route('production.operations.store'), [])
            ->assertJsonValidationErrors(['name', 'code']);
    }

    public function test_store_rejects_duplicate_code(): void
    {
        $this->actingAs($this->makeOperator());

        $this->makeOperation(['code' => 'DUP']);

        $this->postJson(route('production.operations.store'), [
            'name' => 'Другая операция',
            'code' => 'DUP',
        ])->assertJsonValidationErrors(['code']);
    }

    public function test_store_rejects_nonexistent_input_material(): void
    {
        $this->actingAs($this->makeOperator());

        $this->postJson(route('production.operations.store'), [
            'name' => 'Операция',
            'code' => 'BAD-1',
            'inputs' => [
                ['material_id' => 99999, 'unit' => 'kg'],
            ],
        ])->assertJsonValidationErrors(['inputs.0.material_id']);
    }

    public function test_show_displays_operation(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();
        $other = $this->makeOperation();
        $operation->outputOperations()->sync([$other->id]);

        $this->get(route('production.operations.show', $operation))
            ->assertOk()
            ->assertViewIs('production.operations._show')
            ->assertViewHas('operation', fn ($viewOperation) => $viewOperation->id === $operation->id);
    }

    public function test_edit_page_excludes_self_from_operations_list(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();
        $other = $this->makeOperation();

        $this->get(route('production.operations.edit', $operation))
            ->assertOk()
            ->assertViewIs('production.operations.edit')
            ->assertViewHas('operationsList', function ($operationsList) use ($operation, $other) {
                return $operationsList->contains($other) && !$operationsList->contains($operation);
            });
    }

    public function test_update_changes_fields_and_syncs_components(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation(['name' => 'Старое имя', 'code' => 'UPD-1']);
        $input = $this->makeMaterial();
        $output = $this->makeMaterial();
        $newOutput = $this->makeMaterial();

        $kept = $this->makeComponent($operation, $input, 'input', ['quantity' => 1, 'unit' => 'kg']);
        $removed = $this->makeComponent($operation, $output, 'output', ['unit' => 'pcs']);

        $response = $this->putJson(route('production.operations.update', $operation), [
            'name' => 'Новое имя',
            'code' => 'UPD-1',
            'description' => 'новое описание',
            'is_active' => false,
            'is_cutting' => true,
            'inputs' => [
                [
                    'id' => $kept->id,
                    'material_id' => $input->id,
                    'quantity' => 5,
                    'unit' => 'kg',
                ],
            ],
            'outputs' => [
                [
                    'material_id' => $newOutput->id,
                    'unit' => 'pcs',
                ],
            ],
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $operation->refresh();
        $this->assertSame('Новое имя', $operation->name);
        $this->assertSame('новое описание', $operation->description);
        $this->assertFalse($operation->is_active);
        $this->assertTrue($operation->is_cutting);

        $this->assertDatabaseHas('production_operation_components', [
            'id' => $kept->id,
            'quantity' => 5,
        ]);

        // Компонент, не попавший в запрос, удаляется.
        $this->assertDatabaseMissing('production_operation_components', ['id' => $removed->id]);

        $this->assertDatabaseHas('production_operation_components', [
            'operation_id' => $operation->id,
            'material_id' => $newOutput->id,
            'direction' => 'output',
        ]);
    }

    public function test_update_cannot_link_operation_to_itself_as_output(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();
        $other = $this->makeOperation();

        $this->putJson(route('production.operations.update', $operation), [
            'name' => $operation->name,
            'code' => $operation->code,
            'output_operations' => [$operation->id, $other->id],
        ])->assertOk();

        $operation->refresh();

        $this->assertFalse($operation->outputOperations->contains($operation));
        $this->assertTrue($operation->outputOperations->contains($other));
    }

    public function test_update_keeps_code_of_other_operation_unique(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation(['code' => 'ONE']);
        $this->makeOperation(['code' => 'TWO']);

        $this->putJson(route('production.operations.update', $operation), [
            'name' => $operation->name,
            'code' => 'TWO',
        ])->assertJsonValidationErrors(['code']);
    }

    public function test_delete_confirm_page_renders(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();

        $this->get(route('production.operations.delete', $operation))
            ->assertOk()
            ->assertViewIs('production.operations._delete')
            ->assertViewHas('operation', fn ($viewOperation) => $viewOperation->id === $operation->id);
    }

    public function test_destroy_deletes_operation_with_relations(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();
        $material = $this->makeMaterial();

        $component = $this->makeComponent($operation, $material, 'input');

        $line = ProductionLine::create([
            'name' => 'Линия 1',
            'production_operation_id' => $operation->id,
        ]);

        DB::table('material_production_operation')->insert([
            'material_id' => $material->id,
            'production_operation_id' => $operation->id,
        ]);

        $this->deleteJson(route('production.operations.destroy', $operation))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('production_operations', ['id' => $operation->id]);
        $this->assertDatabaseMissing('production_operation_components', ['id' => $component->id]);
        $this->assertDatabaseMissing('production_lines', ['id' => $line->id]);
        $this->assertDatabaseMissing('material_production_operation', [
            'material_id' => $material->id,
            'production_operation_id' => $operation->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Производственные линии (nested routes)
    |--------------------------------------------------------------------------
    */

    public function test_lines_page_lists_operation_lines(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();
        $line = ProductionLine::create([
            'name' => 'Линия А',
            'production_operation_id' => $operation->id,
        ]);

        $this->get(route('production.lines.show', $operation))
            ->assertOk()
            ->assertViewIs('production.operations.lines')
            ->assertViewHas('productionLines', function ($lines) use ($line) {
                return $lines->contains($line);
            });
    }

    public function test_create_line_page_shows_allowed_materials(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();
        $allowed = $this->makeMaterial();
        $notAllowed = $this->makeMaterial();

        DB::table('material_production_operation')->insert([
            'material_id' => $allowed->id,
            'production_operation_id' => $operation->id,
        ]);

        $this->get(route('production.lines.create', $operation))
            ->assertOk()
            ->assertViewIs('production.operations.create-line')
            ->assertViewHas('materials', function ($materials) use ($allowed, $notAllowed) {
                return $materials->contains($allowed) && !$materials->contains($notAllowed);
            });
    }

    public function test_store_line_creates_line_with_input_and_output_materials(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();
        $input = $this->makeMaterial();
        $output = $this->makeMaterial();

        $this->post(route('production.lines.store', $operation), [
            'name' => 'Линия Б',
            'materials' => [
                (string) $input->id,
                '', // пустые строки отбрасываются
            ],
            'output_materials' => [
                (string) $output->id,
            ],
        ])->assertRedirect(route('production.lines.show', $operation));

        $line = ProductionLine::where('name', 'Линия Б')->firstOrFail();
        $this->assertSame($operation->id, $line->production_operation_id);

        $this->assertDatabaseHas('production_line_material', [
            'production_line_id' => $line->id,
            'material_id' => $input->id,
            'direction' => 'input',
        ]);

        $this->assertDatabaseHas('production_line_material', [
            'production_line_id' => $line->id,
            'material_id' => $output->id,
            'direction' => 'output',
        ]);

        $this->assertTrue($line->inputMaterials->contains($input));
        $this->assertTrue($line->outputMaterials->contains($output));
    }

    public function test_store_line_requires_name(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();

        $this->from(route('production.lines.show', $operation))
            ->post(route('production.lines.store', $operation), [
                'name' => '',
            ])
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseMissing('production_lines', [
            'production_operation_id' => $operation->id,
        ]);
    }

    public function test_cutting_operation_keeps_only_one_input_material(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation(['is_cutting' => true]);
        $first = $this->makeMaterial();
        $second = $this->makeMaterial();

        $this->post(route('production.lines.store', $operation), [
            'name' => 'Резальная линия',
            'materials' => [(string) $first->id, (string) $second->id],
        ])->assertRedirect();

        $line = ProductionLine::where('name', 'Резальная линия')->firstOrFail();

        $this->assertSame(1, $line->inputMaterials()->count());
        $this->assertTrue($line->inputMaterials->contains($first));
        $this->assertFalse($line->inputMaterials->contains($second));
    }

    public function test_update_line_replaces_materials_of_each_direction(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();
        $oldInput = $this->makeMaterial();
        $newInput = $this->makeMaterial();
        $oldOutput = $this->makeMaterial();
        $newOutput = $this->makeMaterial();

        $line = ProductionLine::create([
            'name' => 'Линия В',
            'production_operation_id' => $operation->id,
        ]);

        DB::table('production_line_material')->insert([
            ['production_line_id' => $line->id, 'material_id' => $oldInput->id, 'direction' => 'input'],
            ['production_line_id' => $line->id, 'material_id' => $oldOutput->id, 'direction' => 'output'],
        ]);

        $this->put(route('production.lines.line.update', [$operation, $line]), [
            'name' => 'Линия В-2',
            'materials' => [(string) $newInput->id],
            'output_materials' => [(string) $newOutput->id],
        ])->assertRedirect(route('production.lines.line.show', [$operation, $line]));

        $line->refresh();
        $this->assertSame('Линия В-2', $line->name);

        $this->assertDatabaseMissing('production_line_material', [
            'production_line_id' => $line->id,
            'material_id' => $oldInput->id,
        ]);
        $this->assertDatabaseMissing('production_line_material', [
            'production_line_id' => $line->id,
            'material_id' => $oldOutput->id,
        ]);

        $this->assertDatabaseHas('production_line_material', [
            'production_line_id' => $line->id,
            'material_id' => $newInput->id,
            'direction' => 'input',
        ]);
        $this->assertDatabaseHas('production_line_material', [
            'production_line_id' => $line->id,
            'material_id' => $newOutput->id,
            'direction' => 'output',
        ]);
    }

    public function test_line_can_have_same_material_as_input_and_output(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();
        $material = $this->makeMaterial();

        $this->post(route('production.lines.store', $operation), [
            'name' => 'Линия Г',
            'materials' => [(string) $material->id],
            'output_materials' => [(string) $material->id],
        ])->assertRedirect();

        $line = ProductionLine::where('name', 'Линия Г')->firstOrFail();

        $this->assertTrue($line->inputMaterials->contains($material));
        $this->assertTrue($line->outputMaterials->contains($material));
    }

    public function test_show_line_page_renders(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();
        $line = ProductionLine::create([
            'name' => 'Линия Д',
            'production_operation_id' => $operation->id,
        ]);

        $this->get(route('production.lines.line.show', [$operation, $line]))
            ->assertOk()
            ->assertViewIs('production.operations.show-line')
            ->assertViewHas('productionLine', fn ($viewLine) => $viewLine->id === $line->id);
    }

    public function test_delete_line_confirm_page_and_destroy(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();
        $line = ProductionLine::create([
            'name' => 'Линия Е',
            'production_operation_id' => $operation->id,
        ]);

        $this->get(route('production.lines.delete', $line))
            ->assertOk()
            ->assertViewIs('production.operations._delete-line')
            ->assertViewHas('productionLine', fn ($viewLine) => $viewLine->id === $line->id);

        $this->deleteJson(route('production.lines.destroy', $line))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('redirect', route('production.lines.show', $operation));

        $this->assertDatabaseMissing('production_lines', ['id' => $line->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Разрешённые каталоги и материалы
    |--------------------------------------------------------------------------
    */

    public function test_allowed_catalogs_page_lists_root_catalogs_and_materials(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();

        $root = Catalog::create(['name' => 'Корень', 'parent_id' => null, 'is_active' => true]);
        $material = $this->makeMaterial(['catalog_id' => $root->id]);
        // На корневой странице показываются только материалы без каталога.
        $uncategorized = $this->makeMaterial(['catalog_id' => null]);

        $this->get(route('production.operations.allowed-catalogs', $operation))
            ->assertOk()
            ->assertViewIs('production.operations.allowed-catalogs')
            ->assertViewHas('catalogs', fn ($catalogs) => $catalogs->contains($root))
            ->assertViewHas('materials', fn ($materials) => $materials->contains($uncategorized)
                && !$materials->contains($material))
            ->assertViewHas('hierarchyEnabled', true);

        // Материалы каталога видны при входе в сам каталог.
        $this->get(route('production.operations.allowed-catalogs', [
            'productionOperation' => $operation,
            'catalog' => $root->id,
        ]))
            ->assertOk()
            ->assertViewHas('materials', fn ($materials) => $materials->contains($material));
    }

    public function test_allowed_catalogs_computes_catalog_states(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();

        $parent = Catalog::create(['name' => 'Родитель', 'parent_id' => null]);
        $child = Catalog::create(['name' => 'Ребёнок', 'parent_id' => $parent->id]);

        $allowedMaterial = $this->makeMaterial(['catalog_id' => $child->id]);
        $deniedMaterial = $this->makeMaterial(['catalog_id' => $parent->id]);

        DB::table('material_production_operation')->insert([
            'material_id' => $allowedMaterial->id,
            'production_operation_id' => $operation->id,
        ]);

        $this->get(route('production.operations.allowed-catalogs', $operation))
            ->assertOk()
            ->assertViewHas('catalogStates', function ($states) use ($parent) {
                return $states[$parent->id]['state'] === 'partial'
                    && $states[$parent->id]['total'] === 2
                    && $states[$parent->id]['allowed'] === 1;
            });
    }

    public function test_allowed_catalogs_navigates_to_subcatalog(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();

        $root = Catalog::create(['name' => 'Корень', 'parent_id' => null]);
        $child = Catalog::create(['name' => 'Подкаталог', 'parent_id' => $root->id]);

        $childMaterial = $this->makeMaterial(['catalog_id' => $child->id]);

        $this->get(route('production.operations.allowed-catalogs', [
            'productionOperation' => $operation,
            'catalog' => $child->id,
        ]))
            ->assertOk()
            ->assertViewHas('currentCatalog', fn ($catalog) => $catalog->id === $child->id)
            ->assertViewHas('materials', fn ($materials) => $materials->contains($childMaterial))
            ->assertViewHas('breadcrumbs', fn ($breadcrumbs) => $breadcrumbs->last()->id === $child->id
                && $breadcrumbs->first()->id === $root->id);
    }

    public function test_update_allowed_catalogs_grants_material_permission(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();
        $material = $this->makeMaterial();

        $this->postJson(route('production.operations.allowed-catalogs.update', $operation), [
            'material_id' => $material->id,
            'allowed' => true,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('material_production_operation', [
            'material_id' => $material->id,
            'production_operation_id' => $operation->id,
        ]);
    }

    public function test_update_allowed_catalogs_revokes_material_permission(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();
        $material = $this->makeMaterial();

        DB::table('material_production_operation')->insert([
            'material_id' => $material->id,
            'production_operation_id' => $operation->id,
        ]);

        $this->postJson(route('production.operations.allowed-catalogs.update', $operation), [
            'material_id' => $material->id,
            'allowed' => false,
        ])->assertOk();

        $this->assertDatabaseMissing('material_production_operation', [
            'material_id' => $material->id,
            'production_operation_id' => $operation->id,
        ]);
    }

    public function test_update_allowed_catalogs_grants_whole_catalog_subtree(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();

        $root = Catalog::create(['name' => 'Корень', 'parent_id' => null]);
        $child = Catalog::create(['name' => 'Подкаталог', 'parent_id' => $root->id]);

        $rootMaterial = $this->makeMaterial(['catalog_id' => $root->id]);
        $childMaterial = $this->makeMaterial(['catalog_id' => $child->id]);
        $outsideMaterial = $this->makeMaterial(); // без каталога

        $this->postJson(route('production.operations.allowed-catalogs.update', $operation), [
            'catalog_id' => $root->id,
            'allowed' => true,
        ])->assertOk();

        $this->assertDatabaseHas('material_production_operation', [
            'material_id' => $rootMaterial->id,
            'production_operation_id' => $operation->id,
        ]);
        $this->assertDatabaseHas('material_production_operation', [
            'material_id' => $childMaterial->id,
            'production_operation_id' => $operation->id,
        ]);
        $this->assertDatabaseMissing('material_production_operation', [
            'material_id' => $outsideMaterial->id,
            'production_operation_id' => $operation->id,
        ]);
    }

    public function test_update_allowed_catalogs_revokes_whole_catalog_subtree(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();

        $root = Catalog::create(['name' => 'Корень', 'parent_id' => null]);

        $material = $this->makeMaterial(['catalog_id' => $root->id]);

        DB::table('material_production_operation')->insert([
            'material_id' => $material->id,
            'production_operation_id' => $operation->id,
        ]);

        $this->postJson(route('production.operations.allowed-catalogs.update', $operation), [
            'catalog_id' => $root->id,
            'allowed' => false,
        ])->assertOk();

        $this->assertDatabaseMissing('material_production_operation', [
            'material_id' => $material->id,
            'production_operation_id' => $operation->id,
        ]);
    }

    public function test_update_allowed_catalogs_is_idempotent_for_repeated_grants(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();
        $material = $this->makeMaterial();

        foreach ([1, 2] as $pass) {
            $this->postJson(route('production.operations.allowed-catalogs.update', $operation), [
                'material_id' => $material->id,
                'allowed' => true,
            ])->assertOk();
        }

        $this->assertSame(1, DB::table('material_production_operation')
            ->where('material_id', $material->id)
            ->where('production_operation_id', $operation->id)
            ->count());
    }

    public function test_update_allowed_catalogs_requires_allowed_flag(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();
        $material = $this->makeMaterial();

        $this->postJson(route('production.operations.allowed-catalogs.update', $operation), [
            'material_id' => $material->id,
        ])->assertJsonValidationErrors(['allowed']);
    }

    public function test_update_allowed_catalogs_requires_catalog_or_material(): void
    {
        $this->actingAs($this->makeOperator());

        $operation = $this->makeOperation();

        $this->postJson(route('production.operations.allowed-catalogs.update', $operation), [
            'allowed' => true,
        ])->assertJsonValidationErrors(['catalog_id', 'material_id']);
    }
}
