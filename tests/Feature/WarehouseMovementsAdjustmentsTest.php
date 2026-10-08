<?php

namespace Tests\Feature;

use App\Models\Catalog;
use App\Models\Material;
use App\Models\MaterialAdjustment;
use App\Models\MaterialIssue;
use App\Models\MaterialReceipt;
use App\Models\MaterialReceiptItem;
use App\Models\MaterialRoll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseMovementsAdjustmentsTest extends TestCase
{
    use RefreshDatabase;

    /*
     * Хелперы создания данных.
     */

    private function makeMaterial(array $attributes = []): Material
    {
        static $sequence = 0;
        $sequence++;

        return Material::create(array_merge([
            'name' => 'Бумага мелованная ' . $sequence,
            'code' => 'PAP',
            'grammage' => 80,
            'format' => 700,
            'identifier' => 'PAP8070' . $sequence,
            'is_active' => true,
        ], $attributes));
    }

    private function makeRoll(Material $material, string $number, float $weight): MaterialRoll
    {
        return MaterialRoll::create([
            'material_id' => $material->id,
            'roll_number' => $number,
            'weight' => $weight,
        ]);
    }

    private function makeReceipt(User $user, MaterialRoll $roll, float $weight, ?string $comment = null): MaterialReceiptItem
    {
        $receipt = MaterialReceipt::create([
            'user_id' => $user->id,
            'comment' => $comment,
        ]);

        return MaterialReceiptItem::create([
            'material_receipt_id' => $receipt->id,
            'material_id' => $roll->material_id,
            'roll_id' => $roll->id,
            'weight' => $weight,
        ]);
    }

    private function makeIssue(User $user, MaterialRoll $roll, float $weight, ?string $comment = null): MaterialIssue
    {
        return MaterialIssue::create([
            'material_id' => $roll->material_id,
            'roll_id' => $roll->id,
            'weight' => $weight,
            'comment' => $comment,
            'user_id' => $user->id,
        ]);
    }

    private function warehouseUser(string $login = 'sklad'): User
    {
        return $this->makeUserWithPermissions([
            'warehouse' => ['view', 'create'],
        ], 'Кладовщик', $login);
    }

    /*
     * Доступ: гости и права.
     */

    public function test_guest_is_redirected_to_login_from_warehouse_pages(): void
    {
        $this->get(route('warehouse.index'))
            ->assertRedirect(route('login'));

        $this->get(route('material-movements.index'))
            ->assertRedirect(route('login'));

        $this->get(route('material-adjustments.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_warehouse_permission_gets_403(): void
    {
        $user = $this->makeUserWithPermissions([], 'Без прав', 'norights');

        $this->actingAs($user)
            ->get(route('warehouse.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('material-movements.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('material-adjustments.index'))
            ->assertForbidden();
    }

    public function test_warehouse_create_action_is_required_for_adjustment_create_and_store(): void
    {
        // Только view: список доступен, форма и сохранение — нет.
        $user = $this->makeUserWithPermissions([
            'warehouse' => ['view'],
        ], 'Только просмотр', 'viewer');

        $this->actingAs($user)
            ->get(route('material-adjustments.create'))
            ->assertForbidden();

        $material = $this->makeMaterial();
        $roll = $this->makeRoll($material, 'R-1', 10.0);

        $this->actingAs($user)
            ->post(route('material-adjustments.store'), [
                'rows' => [
                    ['material_id' => $material->id, 'roll_id' => $roll->id, 'adjustment' => 1.5],
                ],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('material_adjustments', 0);
    }

    /*
     * WarehouseController@index — сводка склада.
     */

    public function test_warehouse_index_shows_materials_and_catalog_tree(): void
    {
        $user = $this->warehouseUser();

        $catalog = Catalog::create(['name' => 'Плёночные материалы', 'sort_order' => 1]);
        $inCatalog = $this->makeMaterial(['name' => 'Плёнка BOPP', 'catalog_id' => $catalog->id]);
        $this->makeRoll($inCatalog, 'R-100', 25.5);

        $loose = $this->makeMaterial(['name' => 'Бумага офсетная']);

        $this->actingAs($user)
            ->get(route('warehouse.index'))
            ->assertOk()
            ->assertSee('Плёночные материалы')
            ->assertSee('Плёнка BOPP')
            ->assertSee('Бумага офсетная');
    }

    public function test_warehouse_index_search_shows_flat_filtered_list(): void
    {
        $user = $this->warehouseUser();

        $this->makeMaterial(['name' => 'Плёнка BOPP прозрачная']);
        $this->makeMaterial(['name' => 'Бумага офсетная']);

        $response = $this->actingAs($user)
            ->get(route('warehouse.index', ['search' => 'BOPP']))
            ->assertOk();

        $response->assertSee('Плёнка BOPP прозрачная');
        $this->assertStringNotContainsString('Бумага офсетная', $response->getContent());
    }

    public function test_warehouse_index_search_matches_identifier_too(): void
    {
        $user = $this->warehouseUser();

        $material = $this->makeMaterial(['name' => 'Фольга', 'identifier' => 'FOL00712']);
        $this->makeMaterial(['name' => 'Бумага офсетная']);

        $this->actingAs($user)
            ->get(route('warehouse.index', ['search' => 'FOL']))
            ->assertOk()
            ->assertSee('Фольга');
    }

    public function test_warehouse_index_format_filter(): void
    {
        $user = $this->warehouseUser();

        $this->makeMaterial(['name' => 'Материал 700', 'format' => 700]);
        $this->makeMaterial(['name' => 'Материал 1000', 'format' => 1000]);

        $response = $this->actingAs($user)
            ->get(route('warehouse.index', ['format' => 1000]))
            ->assertOk();

        $response->assertSee('Материал 1000');
        $this->assertStringNotContainsString('Материал 700', $response->getContent());
    }

    public function test_warehouse_index_stock_filters_available_empty_and_low(): void
    {
        $user = $this->warehouseUser();

        $withStock = $this->makeMaterial(['name' => 'С остатком']);
        $this->makeRoll($withStock, 'R-1', 60.0);

        $low = $this->makeMaterial(['name' => 'Мало материала']);
        $this->makeRoll($low, 'R-2', 10.0);

        $empty = $this->makeMaterial(['name' => 'Пустая позиция']);
        // Рулон с нулевым весом не считается остатком.
        $this->makeRoll($empty, 'R-3', 0.0);

        $this->actingAs($user)
            ->get(route('warehouse.index', ['stock' => 'available']))
            ->assertOk()
            ->assertSee('С остатком')
            ->assertSee('Мало материала');

        $response = $this->actingAs($user)
            ->get(route('warehouse.index', ['stock' => 'available']))
            ->getContent();
        $this->assertStringNotContainsString('Пустая позиция', $response);

        $this->actingAs($user)
            ->get(route('warehouse.index', ['stock' => 'empty']))
            ->assertOk()
            ->assertSee('Пустая позиция');

        $response = $this->actingAs($user)
            ->get(route('warehouse.index', ['stock' => 'low']))
            ->getContent();
        $this->assertStringContainsString('Мало материала', $response);
        $this->assertStringNotContainsString('С остатком', $response);
        $this->assertStringNotContainsString('Пустая позиция', $response);
    }

    public function test_warehouse_index_code_filter(): void
    {
        $user = $this->warehouseUser();

        $this->makeMaterial(['name' => 'Бумага', 'code' => 'PAP']);
        $this->makeMaterial(['name' => 'Плёнка', 'code' => 'FIL']);

        $response = $this->actingAs($user)
            ->get(route('warehouse.index', ['code' => 'FIL']))
            ->assertOk();

        $response->assertSee('Плёнка');
        $this->assertStringNotContainsString('Бумага', $response->getContent());

        // Несколько кодов одновременно.
        $response = $this->actingAs($user)
            ->get(route('warehouse.index', ['codes' => ['PAP', 'FIL']]))
            ->assertOk();

        $response->assertSee('Плёнка')->assertSee('Бумага');
    }

    /*
     * WarehouseController@material — карточка материала.
     */

    public function test_material_card_shows_only_positive_rolls_and_total_weight(): void
    {
        $user = $this->warehouseUser();

        $material = $this->makeMaterial(['name' => 'Плёнка BOPP']);
        $this->makeRoll($material, 'R-2', 4.25);
        $this->makeRoll($material, 'R-1', 6.25);
        // Нулевой рулон не учитывается ни в количестве, ни в остатке.
        $this->makeRoll($material, 'R-3', 0.0);

        $this->actingAs($user)
            ->get(route('warehouse.material', $material))
            ->assertOk()
            ->assertSee('R-1')
            ->assertSee('R-2')
            ->assertSee('10.500кг');
    }

    /*
     * MaterialMovementController@index — журнал движения.
     */

    public function test_movements_journal_lists_receipts_and_issues(): void
    {
        $user = $this->warehouseUser();

        $material = $this->makeMaterial(['name' => 'Плёнка BOPP']);
        $receiptRoll = $this->makeRoll($material, 'RCPT-1', 100.0);
        $issueRoll = $this->makeRoll($material, 'ISS-1', 20.0);

        $this->makeReceipt($user, $receiptRoll, 100.0, 'Поставка от поставщика');
        $this->makeIssue($user, $issueRoll, 20.0, 'Отгрузка в цех');

        $this->actingAs($user)
            ->get(route('material-movements.index'))
            ->assertOk()
            ->assertSee('RCPT-1')
            ->assertSee('ISS-1')
            ->assertSee('+100.000')
            ->assertSee('-20.000');
    }

    public function test_movements_journal_type_filter(): void
    {
        $user = $this->warehouseUser();

        $material = $this->makeMaterial();
        $receiptRoll = $this->makeRoll($material, 'RCPT-2', 100.0);
        $issueRoll = $this->makeRoll($material, 'ISS-2', 20.0);

        $this->makeReceipt($user, $receiptRoll, 100.0);
        $this->makeIssue($user, $issueRoll, 20.0);

        $content = $this->actingAs($user)
            ->get(route('material-movements.index', ['type' => 'receipt']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('RCPT-2', $content);
        $this->assertStringNotContainsString('ISS-2', $content);

        $content = $this->actingAs($user)
            ->get(route('material-movements.index', ['type' => 'issue']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('ISS-2', $content);
        $this->assertStringNotContainsString('RCPT-2', $content);
    }

    public function test_movements_journal_material_filter(): void
    {
        $user = $this->warehouseUser();

        $rollA = $this->makeRoll($this->makeMaterial(['name' => 'Материал А']), 'FLTR-A', 10.0);
        $rollB = $this->makeRoll($this->makeMaterial(['name' => 'Материал Б']), 'FLTR-B', 20.0);

        $this->makeReceipt($user, $rollA, 10.0);
        $this->makeReceipt($user, $rollB, 20.0);

        $content = $this->actingAs($user)
            ->get(route('material-movements.index', ['material_id' => $rollA->material_id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('FLTR-A', $content);
        $this->assertStringNotContainsString('FLTR-B', $content);
    }

    public function test_movements_journal_date_filter(): void
    {
        $user = $this->warehouseUser();

        $material = $this->makeMaterial();
        $roll = $this->makeRoll($material, 'DATE-1', 10.0);

        $this->makeReceipt($user, $roll, 10.0);

        $this->actingAs($user)
            ->get(route('material-movements.index', [
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('DATE-1');

        $content = $this->actingAs($user)
            ->get(route('material-movements.index', [
                'date_from' => now()->addDay()->toDateString(),
            ]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Движений материалов пока нет', $content);
    }

    public function test_movements_journal_search_by_material_name(): void
    {
        $user = $this->warehouseUser();

        $rollA = $this->makeRoll($this->makeMaterial(['name' => 'Плёнка металлизированная']), 'SRCH-A', 10.0);
        $rollB = $this->makeRoll($this->makeMaterial(['name' => 'Бумага газетная']), 'SRCH-B', 20.0);

        $this->makeReceipt($user, $rollA, 10.0);
        $this->makeIssue($user, $rollB, 5.0);

        $content = $this->actingAs($user)
            ->get(route('material-movements.index', ['search' => 'металлизированная']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('SRCH-A', $content);
        $this->assertStringNotContainsString('SRCH-B', $content);
    }

    /*
     * MaterialAdjustmentController — ордера корректировки.
     */

    public function test_adjustments_index_groups_batch_rows_into_orders(): void
    {
        $user = $this->warehouseUser();

        $material = $this->makeMaterial();
        $rollA = $this->makeRoll($material, 'R-1', 10.0);
        $rollB = $this->makeRoll($material, 'R-2', 20.0);

        MaterialAdjustment::create([
            'material_id' => $material->id,
            'roll_id' => $rollA->id,
            'weight_before' => 10.0,
            'adjustment' => 1.0,
            'weight_after' => 11.0,
            'batch_id' => 'batch-1',
            'comment' => 'Инвентаризация',
            'user_id' => $user->id,
        ]);
        MaterialAdjustment::create([
            'material_id' => $material->id,
            'roll_id' => $rollB->id,
            'weight_before' => 20.0,
            'adjustment' => -2.0,
            'weight_after' => 18.0,
            'batch_id' => 'batch-1',
            'comment' => 'Инвентаризация',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('material-adjustments.index'))
            ->assertOk()
            ->assertSee('Инвентаризация');
    }

    public function test_adjustments_index_date_filter(): void
    {
        $user = $this->warehouseUser();

        $material = $this->makeMaterial();
        $roll = $this->makeRoll($material, 'R-1', 10.0);

        MaterialAdjustment::create([
            'material_id' => $material->id,
            'roll_id' => $roll->id,
            'weight_before' => 10.0,
            'adjustment' => 1.0,
            'weight_after' => 11.0,
            'comment' => 'Корректировка сегодня',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('material-adjustments.index', [
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Корректировка сегодня');

        $response = $this->actingAs($user)
            ->get(route('material-adjustments.index', [
                'date_from' => now()->addDay()->toDateString(),
            ]))
            ->assertOk();

        $this->assertStringNotContainsString('Корректировка сегодня', $response->getContent());
    }

    public function test_adjustment_create_form_is_available_for_warehouse_create(): void
    {
        $user = $this->warehouseUser();

        $material = $this->makeMaterial(['name' => 'Плёнка BOPP']);

        $this->actingAs($user)
            ->get(route('material-adjustments.create'))
            ->assertOk()
            ->assertSee('Плёнка BOPP');
    }

    public function test_adjustment_store_updates_roll_weight_and_creates_history_row(): void
    {
        $user = $this->warehouseUser();

        $material = $this->makeMaterial();
        $roll = $this->makeRoll($material, 'R-1', 10.5);

        $response = $this->actingAs($user)
            ->post(route('material-adjustments.store'), [
                'rows' => [
                    ['material_id' => $material->id, 'roll_id' => $roll->id, 'adjustment' => -0.75],
                ],
                'comment' => 'Обрыв полотна',
            ]);

        $response->assertRedirect(route('material-adjustments.create'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('material_adjustments', 1);
        $this->assertDatabaseHas('material_adjustments', [
            'material_id' => $material->id,
            'roll_id' => $roll->id,
            'comment' => 'Обрыв полотна',
            'user_id' => $user->id,
        ]);

        $adjustment = MaterialAdjustment::query()->firstOrFail();
        $this->assertEquals(10.5, (float) $adjustment->weight_before);
        $this->assertEquals(-0.75, (float) $adjustment->adjustment);
        $this->assertEquals(9.75, (float) $adjustment->weight_after);
        $this->assertNotNull($adjustment->batch_id);

        $this->assertEquals(9.75, (float) $roll->fresh()->weight);
    }

    public function test_adjustment_store_batch_of_rows_shares_batch_id_and_updates_rolls(): void
    {
        $user = $this->warehouseUser();

        $material = $this->makeMaterial();
        $rollA = $this->makeRoll($material, 'R-1', 10.0);
        $rollB = $this->makeRoll($material, 'R-2', 20.0);

        $this->actingAs($user)
            ->post(route('material-adjustments.store'), [
                'rows' => [
                    ['material_id' => $material->id, 'roll_id' => $rollA->id, 'adjustment' => 1.0],
                    ['material_id' => $material->id, 'roll_id' => $rollB->id, 'adjustment' => -3.0],
                ],
                'comment' => 'Инвентаризация',
            ])
            ->assertRedirect(route('material-adjustments.create'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('material_adjustments', 2);

        $batchIds = MaterialAdjustment::query()->pluck('batch_id')->unique();
        $this->assertCount(1, $batchIds);
        $this->assertNotNull($batchIds->first());

        $this->assertEquals(11.0, (float) $rollA->fresh()->weight);
        $this->assertEquals(17.0, (float) $rollB->fresh()->weight);
    }

    public function test_adjustment_show_lists_all_rows_of_the_batch(): void
    {
        $user = $this->warehouseUser();

        $material = $this->makeMaterial();
        $rollA = $this->makeRoll($material, 'R-1', 10.0);
        $rollB = $this->makeRoll($material, 'R-2', 20.0);

        $first = MaterialAdjustment::create([
            'material_id' => $material->id,
            'roll_id' => $rollA->id,
            'weight_before' => 10.0,
            'adjustment' => 1.0,
            'weight_after' => 11.0,
            'batch_id' => 'batch-show',
            'user_id' => $user->id,
        ]);
        MaterialAdjustment::create([
            'material_id' => $material->id,
            'roll_id' => $rollB->id,
            'weight_before' => 20.0,
            'adjustment' => -2.0,
            'weight_after' => 18.0,
            'batch_id' => 'batch-show',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('material-adjustments.show', $first))
            ->assertOk()
            ->assertSee('R-1')
            ->assertSee('R-2');
    }

    public function test_adjustment_store_validation_errors(): void
    {
        $user = $this->warehouseUser();
        $material = $this->makeMaterial();
        $roll = $this->makeRoll($material, 'R-1', 10.0);

        // Без позиций.
        $this->actingAs($user)
            ->post(route('material-adjustments.store'), [])
            ->assertSessionHasErrors(['rows']);

        // Нулевое отклонение.
        $this->actingAs($user)
            ->post(route('material-adjustments.store'), [
                'rows' => [
                    ['material_id' => $material->id, 'roll_id' => $roll->id, 'adjustment' => 0],
                ],
            ])
            ->assertSessionHasErrors('rows.*.adjustment');

        // Несуществующий материал.
        $this->actingAs($user)
            ->post(route('material-adjustments.store'), [
                'rows' => [
                    ['material_id' => 999999, 'roll_id' => $roll->id, 'adjustment' => 1],
                ],
            ])
            ->assertSessionHasErrors('rows.*.material_id');

        // Несуществующий рулон.
        $this->actingAs($user)
            ->post(route('material-adjustments.store'), [
                'rows' => [
                    ['material_id' => $material->id, 'roll_id' => 999999, 'adjustment' => 1],
                ],
            ])
            ->assertSessionHasErrors('rows.*.roll_id');

        // Дубликат рулона в позициях.
        $this->actingAs($user)
            ->post(route('material-adjustments.store'), [
                'rows' => [
                    ['material_id' => $material->id, 'roll_id' => $roll->id, 'adjustment' => 1],
                    ['material_id' => $material->id, 'roll_id' => $roll->id, 'adjustment' => 2],
                ],
            ])
            ->assertSessionHasErrors('rows.*.roll_id');

        $this->assertDatabaseCount('material_adjustments', 0);
        $this->assertEquals(10.0, (float) $roll->fresh()->weight);
    }

    public function test_adjustment_store_rejects_negative_resulting_weight(): void
    {
        $user = $this->warehouseUser();

        $material = $this->makeMaterial();
        $roll = $this->makeRoll($material, 'R-1', 5.0);

        $this->actingAs($user)
            ->post(route('material-adjustments.store'), [
                'rows' => [
                    ['material_id' => $material->id, 'roll_id' => $roll->id, 'adjustment' => -10.0],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('rows');

        $this->assertDatabaseCount('material_adjustments', 0);
        $this->assertEquals(5.0, (float) $roll->fresh()->weight);
    }

    public function test_adjustment_store_rejects_roll_from_another_material(): void
    {
        $user = $this->warehouseUser();

        $materialA = $this->makeMaterial();
        $materialB = $this->makeMaterial();
        $rollB = $this->makeRoll($materialB, 'R-B', 5.0);

        $this->actingAs($user)
            ->post(route('material-adjustments.store'), [
                'rows' => [
                    ['material_id' => $materialA->id, 'roll_id' => $rollB->id, 'adjustment' => 1.0],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('rows');

        $this->assertDatabaseCount('material_adjustments', 0);
        $this->assertEquals(5.0, (float) $rollB->fresh()->weight);
    }
}
