<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\MaterialIssue;
use App\Models\MaterialReceipt;
use App\Models\MaterialReceiptItem;
use App\Models\MaterialRoll;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReceiptsIssuesRollsTest extends TestCase
{
    use RefreshDatabase;

    private function loginAdmin(string $login = 'admin'): User
    {
        $user = $this->makeAdmin($login);

        $this->actingAs($user);

        return $user;
    }

    /**
     * Пользователь с правами склада (просмотр + создание ордеров).
     */
    private function loginWarehouseUser(string $login = 'warehouseman'): User
    {
        $user = $this->makeUserWithPermissions(
            [
                'warehouse' => ['view', 'create'],
                'rolls' => ['view'],
            ],
            'Кладовщик',
            $login
        );

        $this->actingAs($user);

        return $user;
    }

    private function makeMaterial(string $name = 'Бумага 80 г/м2'): Material
    {
        return Material::create([
            'name' => $name,
            'code' => 'PP',
            'grammage' => 80,
            'thickness' => null,
            'format' => 640,
            'identifier' => 'PP80640' . substr(md5($name), 0, 4),
            'is_active' => true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Приходные ордера
    |--------------------------------------------------------------------------
    */

    public function test_guest_is_redirected_to_login_from_receipts_index(): void
    {
        $this->get(route('material-receipts.index'))
            ->assertRedirect(route('login'));
    }

    public function test_receipts_index_requires_warehouse_permission(): void
    {
        $this->makeUserWithPermissions(
            ['materials' => ['view']],
            'Без склада',
            'norights'
        );
        $this->actingAs(User::where('login', 'norights')->first());

        $this->get(route('material-receipts.index'))
            ->assertForbidden();
    }

    public function test_receipts_index_lists_receipts_for_permitted_user(): void
    {
        $user = $this->loginWarehouseUser();
        $material = $this->makeMaterial();

        MaterialReceipt::create(['comment' => 'Приход от поставщика', 'user_id' => $user->id]);

        $this->get(route('material-receipts.index'))
            ->assertOk()
            ->assertViewIs('material-receipts.index')
            ->assertViewHas('receipts', function ($receipts) {
                return $receipts->count() === 1
                    && $receipts->first()->comment === 'Приход от поставщика';
            });

        $this->assertDatabaseCount('material_receipts', 1);
        $this->assertDatabaseHas('materials', ['id' => $material->id]);
    }

    public function test_receipts_index_filters_by_date_range(): void
    {
        $user = $this->loginWarehouseUser();

        $old = MaterialReceipt::create(['comment' => 'старый', 'user_id' => $user->id]);
        $old->created_at = now()->subDays(10);
        $old->save();

        MaterialReceipt::create(['comment' => 'свежий', 'user_id' => $user->id]);

        $this->get(route('material-receipts.index', ['date_from' => now()->toDateString()]))
            ->assertOk()
            ->assertViewHas('receipts', fn ($receipts) => $receipts->count() === 1
                && $receipts->first()->comment === 'свежий');
    }

    public function test_receipt_create_page_renders_for_user_with_create_permission(): void
    {
        $this->loginWarehouseUser();
        $material = $this->makeMaterial();

        $this->get(route('material-receipts.create'))
            ->assertOk()
            ->assertViewIs('material-receipts.create')
            ->assertViewHas('materials', fn ($materials) => $materials->contains($material));
    }

    public function test_receipt_create_forbidden_without_create_action(): void
    {
        $user = $this->makeUserWithPermissions(
            ['warehouse' => ['view']],
            'Только просмотр',
            'viewer'
        );
        $this->actingAs($user);

        $this->get(route('material-receipts.create'))
            ->assertForbidden();

        $this->post(route('material-receipts.store'), [])
            ->assertForbidden();
    }

    public function test_store_creates_receipt_rolls_and_items_and_shows_success(): void
    {
        $user = $this->loginWarehouseUser();
        $material = $this->makeMaterial();

        $response = $this->post(route('material-receipts.store'), [
            'material_id' => $material->id,
            'rolls' => [
                ['roll_number' => 'R-001', 'weight' => 120.5],
                ['roll_number' => 'R-002', 'weight' => 79.5],
            ],
            'comment' => 'Партия №17',
        ]);

        $response
            ->assertRedirect(route('material-receipts.create'))
            ->assertSessionHas('success', 'Материал успешно оприходован.');

        $this->assertDatabaseCount('material_receipts', 1);
        $this->assertDatabaseHas('material_receipts', [
            'user_id' => $user->id,
            'comment' => 'Партия №17',
        ]);

        $this->assertDatabaseCount('material_rolls', 2);
        $this->assertDatabaseHas('material_rolls', [
            'material_id' => $material->id,
            'roll_number' => 'R-001',
            'weight' => 120.5,
        ]);
        $this->assertDatabaseHas('material_rolls', [
            'material_id' => $material->id,
            'roll_number' => 'R-002',
            'weight' => 79.5,
        ]);

        $this->assertDatabaseCount('material_receipt_items', 2);
        $receipt = MaterialReceipt::first();
        $this->assertSame(2, $receipt->items()->count());
        $this->assertSame(200.0, (float) $receipt->items()->sum('weight'));
    }

    public function test_store_rejects_duplicate_roll_number_for_same_material(): void
    {
        $this->loginWarehouseUser();
        $material = $this->makeMaterial();

        $this->post(route('material-receipts.store'), [
            'material_id' => $material->id,
            'rolls' => [['roll_number' => 'R-100', 'weight' => 50]],
        ])->assertRedirect(route('material-receipts.create'));

        // Тот же номер для того же материала — ошибка валидации.
        $this->post(route('material-receipts.store'), [
            'material_id' => $material->id,
            'rolls' => [['roll_number' => 'R-100', 'weight' => 50]],
        ])
            ->assertSessionHasErrors(['rolls.0.roll_number']);

        // Тот же номер для другого материала разрешён.
        $other = $this->makeMaterial('Плёнка 20 мкм');
        $this->post(route('material-receipts.store'), [
            'material_id' => $other->id,
            'rolls' => [['roll_number' => 'R-100', 'weight' => 30]],
        ])->assertSessionHasNoErrors();

        // Только два реально созданных рулона: R-100 для обоих материалов.
        $this->assertSame(2, MaterialRoll::count());
        $this->assertSame(2, MaterialRoll::where('roll_number', 'R-100')->count());
    }

    public function test_store_validation_errors(): void
    {
        $this->loginWarehouseUser();
        $material = $this->makeMaterial();

        // Не указан материал.
        $this->post(route('material-receipts.store'), [
            'rolls' => [['roll_number' => 'R-1', 'weight' => 10]],
        ])->assertSessionHasErrors(['material_id']);

        // Несуществующий материал.
        $this->post(route('material-receipts.store'), [
            'material_id' => 99999,
            'rolls' => [['roll_number' => 'R-1', 'weight' => 10]],
        ])->assertSessionHasErrors(['material_id']);

        // Пустой список рулонов.
        $this->post(route('material-receipts.store'), [
            'material_id' => $material->id,
            'rolls' => [],
        ])->assertSessionHasErrors(['rolls']);

        // Нулевой и отрицательный вес.
        $this->post(route('material-receipts.store'), [
            'material_id' => $material->id,
            'rolls' => [['roll_number' => 'R-1', 'weight' => 0]],
        ])->assertSessionHasErrors(['rolls.0.weight']);

        $this->post(route('material-receipts.store'), [
            'material_id' => $material->id,
            'rolls' => [['roll_number' => 'R-1', 'weight' => -5]],
        ])->assertSessionHasErrors(['rolls.0.weight']);

        // Ничего не должно быть создано.
        $this->assertDatabaseCount('material_receipts', 0);
        $this->assertDatabaseCount('material_rolls', 0);
    }

    public function test_store_total_weight_mode_accumulates_into_single_roll(): void
    {
        $user = $this->loginWarehouseUser();
        $material = $this->makeMaterial();

        // Первый приход в режиме общего веса.
        $this->post(route('material-receipts.store'), [
            'mode' => 'total_weight',
            'material_id' => $material->id,
            'rolls' => [
                ['roll_number' => null, 'weight' => 100],
                ['roll_number' => null, 'weight' => 50.5],
            ],
            'comment' => 'Общий вес',
        ])->assertSessionHasNoErrors();

        // Номер в режиме общего веса не обязателен и фиксированный.
        $this->assertDatabaseCount('material_rolls', 1);

        $roll = MaterialRoll::first();
        $this->assertSame('Общий вес', $roll->roll_number);
        $this->assertSame(150.5, (float) $roll->weight);

        // Второй приход суммируется в тот же рулон.
        $this->post(route('material-receipts.store'), [
            'mode' => 'total_weight',
            'material_id' => $material->id,
            'rolls' => [['roll_number' => null, 'weight' => 49.5]],
        ])->assertSessionHasNoErrors();

        $roll->refresh();
        $this->assertSame(200.0, (float) $roll->weight);
        $this->assertSame(2, MaterialReceipt::count());
        $this->assertSame(2, MaterialReceiptItem::count());
        $this->assertSame(200.0, (float) $material->rolls()->sum('weight'));
    }

    public function test_receipt_show_displays_items(): void
    {
        $user = $this->loginWarehouseUser();
        $material = $this->makeMaterial();

        $this->post(route('material-receipts.store'), [
            'material_id' => $material->id,
            'rolls' => [['roll_number' => 'R-777', 'weight' => 42]],
            'comment' => 'Показ',
        ]);

        $receipt = MaterialReceipt::first();

        $this->get(route('material-receipts.show', $receipt))
            ->assertOk()
            ->assertViewIs('material-receipts.show')
            ->assertViewHas('receipt', fn ($r) => $r->id === $receipt->id
                && $r->items->count() === 1);

        // AJAX-запрос получает частичное представление.
        $this->get(route('material-receipts.show', $receipt), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertViewIs('material-receipts._content');
    }

    /*
    |--------------------------------------------------------------------------
    | Расходные ордера
    |--------------------------------------------------------------------------
    */

    public function test_issues_index_requires_permission_and_lists_issues(): void
    {
        $user = $this->loginWarehouseUser();
        $material = $this->makeMaterial();

        $roll = MaterialRoll::create([
            'material_id' => $material->id,
            'roll_number' => 'R-1',
            'weight' => 100,
        ]);

        MaterialIssue::create([
            'material_id' => $material->id,
            'roll_id' => $roll->id,
            'weight' => 10,
            'comment' => 'Списание в цех',
            'user_id' => $user->id,
        ]);

        $this->get(route('material-issues.index'))
            ->assertOk()
            ->assertViewIs('material-issues.index')
            ->assertViewHas('issues', fn ($issues) => $issues->count() === 1
                && $issues->first()->comment === 'Списание в цех');

        // Пользователь без права warehouse не допущен.
        $outsider = $this->makeUserWithPermissions(['materials' => ['view']], 'Чужой', 'outsider');
        $this->actingAs($outsider);

        $this->get(route('material-issues.index'))
            ->assertForbidden();
    }

    public function test_issue_create_page_renders(): void
    {
        $this->loginWarehouseUser();
        $this->makeMaterial();

        $this->get(route('material-issues.create'))
            ->assertOk()
            ->assertViewIs('material-issues.create')
            ->assertViewHas('materials');
    }

    public function test_issue_store_decreases_roll_weight_and_creates_issue_record(): void
    {
        $user = $this->loginWarehouseUser();
        $material = $this->makeMaterial();

        $roll = MaterialRoll::create([
            'material_id' => $material->id,
            'roll_number' => 'R-500',
            'weight' => 100,
        ]);

        $this->post(route('material-issues.store'), [
            'material_id' => $material->id,
            'roll_id' => $roll->id,
            'weight' => 37.25,
            'comment' => 'В производство',
        ])
            ->assertRedirect(route('material-issues.create'))
            ->assertSessionHas('success', 'Материал успешно списан.');

        $roll->refresh();
        $this->assertSame(62.75, (float) $roll->weight);

        $issue = MaterialIssue::first();
        $this->assertNotNull($issue);
        $this->assertSame($material->id, $issue->material_id);
        $this->assertSame($roll->id, $issue->roll_id);
        $this->assertSame(37.25, (float) $issue->weight);
        $this->assertSame('В производство', $issue->comment);
        $this->assertSame($user->id, $issue->user_id);
    }

    public function test_issue_store_marks_roll_issued_when_fully_exhausted(): void
    {
        $this->loginWarehouseUser();
        $material = $this->makeMaterial();

        $roll = MaterialRoll::create([
            'material_id' => $material->id,
            'roll_number' => 'R-FULL',
            'weight' => 25,
        ]);

        $this->post(route('material-issues.store'), [
            'material_id' => $material->id,
            'roll_id' => $roll->id,
            'weight' => 25,
        ])->assertSessionHasNoErrors();

        $roll->refresh();
        // Рулон полностью израсходован: остаток 0 (рулон «выдан»).
        $this->assertSame(0.0, (float) $roll->weight);

        // Рулон с нулевым весом больше не числится на складе:
        // недоступен в AJAX-выдаче и в списке физических рулонов.
        $this->get(route('api.rolls.by-material', ['material_id' => $material->id]))
            ->assertOk()
            ->assertJsonCount(0);

        $this->get(route('material-rolls.index'))
            ->assertOk()
            ->assertViewHas('rolls', fn ($rolls) => $rolls->count() === 0);
    }

    public function test_issue_store_rejects_insufficient_stock(): void
    {
        $this->loginWarehouseUser();
        $material = $this->makeMaterial();

        $roll = MaterialRoll::create([
            'material_id' => $material->id,
            'roll_number' => 'R-SMALL',
            'weight' => 10,
        ]);

        $this->from(route('material-issues.create'))
            ->post(route('material-issues.store'), [
                'material_id' => $material->id,
                'roll_id' => $roll->id,
                'weight' => 10.001,
            ])
            ->assertRedirect(route('material-issues.create'))
            ->assertSessionHasErrors(['weight']);

        // Остаток и список расхода не изменились.
        $roll->refresh();
        $this->assertSame(10.0, (float) $roll->weight);
        $this->assertDatabaseCount('material_issues', 0);
    }

    public function test_issue_store_rejects_roll_of_another_material(): void
    {
        $this->loginWarehouseUser();
        $materialA = $this->makeMaterial('Материал А');
        $materialB = $this->makeMaterial('Материал Б');

        $roll = MaterialRoll::create([
            'material_id' => $materialB->id,
            'roll_number' => 'R-B1',
            'weight' => 100,
        ]);

        $this->from(route('material-issues.create'))
            ->post(route('material-issues.store'), [
                'material_id' => $materialA->id,
                'roll_id' => $roll->id,
                'weight' => 5,
            ])
            ->assertRedirect(route('material-issues.create'))
            ->assertSessionHasErrors(['weight']);

        $roll->refresh();
        $this->assertSame(100.0, (float) $roll->weight);
        $this->assertDatabaseCount('material_issues', 0);
    }

    public function test_issue_store_validation_errors(): void
    {
        $this->loginWarehouseUser();
        $material = $this->makeMaterial();

        $roll = MaterialRoll::create([
            'material_id' => $material->id,
            'roll_number' => 'R-9',
            'weight' => 50,
        ]);

        // Несуществующий рулон.
        $this->post(route('material-issues.store'), [
            'material_id' => $material->id,
            'roll_id' => 99999,
            'weight' => 1,
        ])->assertSessionHasErrors(['roll_id']);

        // Несуществующий материал.
        $this->post(route('material-issues.store'), [
            'material_id' => 99999,
            'roll_id' => $roll->id,
            'weight' => 1,
        ])->assertSessionHasErrors(['material_id']);

        // Нулевой вес.
        $this->post(route('material-issues.store'), [
            'material_id' => $material->id,
            'roll_id' => $roll->id,
            'weight' => 0,
        ])->assertSessionHasErrors(['weight']);

        // Отрицательный вес.
        $this->post(route('material-issues.store'), [
            'material_id' => $material->id,
            'roll_id' => $roll->id,
            'weight' => -1,
        ])->assertSessionHasErrors(['weight']);

        $this->assertDatabaseCount('material_issues', 0);
        $this->assertSame(50.0, (float) $roll->fresh()->weight);
    }

    public function test_issue_show_renders_for_permitted_user(): void
    {
        $user = $this->loginWarehouseUser();
        $material = $this->makeMaterial();

        $roll = MaterialRoll::create([
            'material_id' => $material->id,
            'roll_number' => 'R-SHOW',
            'weight' => 90,
        ]);

        $issue = MaterialIssue::create([
            'material_id' => $material->id,
            'roll_id' => $roll->id,
            'weight' => 10,
            'comment' => 'Показ',
            'user_id' => $user->id,
        ]);

        $this->get(route('material-issues.show', $issue))
            ->assertOk()
            ->assertViewIs('material-issues.show')
            ->assertViewHas('issue', fn ($i) => $i->id === $issue->id);

        $this->get(route('material-issues.show', $issue), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertViewIs('material-issues._content');
    }

    /*
    |--------------------------------------------------------------------------
    | Физические рулоны
    |--------------------------------------------------------------------------
    */

    public function test_rolls_index_requires_rolls_permission(): void
    {
        $this->makeUserWithPermissions(['warehouse' => ['view']], 'Без рулонов', 'norolls');
        $this->actingAs(User::where('login', 'norolls')->first());

        $this->get(route('material-rolls.index'))
            ->assertForbidden();
    }

    public function test_rolls_index_lists_only_rolls_with_positive_weight(): void
    {
        $this->loginWarehouseUser();
        $material = $this->makeMaterial();

        MaterialRoll::create([
            'material_id' => $material->id,
            'roll_number' => 'A-1',
            'weight' => 100,
        ]);
        MaterialRoll::create([
            'material_id' => $material->id,
            'roll_number' => 'B-2',
            'weight' => 0,
        ]);

        $this->get(route('material-rolls.index'))
            ->assertOk()
            ->assertViewIs('material-rolls.index')
            ->assertViewHas('rolls', fn ($rolls) => $rolls->count() === 1
                && $rolls->first()->roll_number === 'A-1');

        // Фильтр по материалу.
        $this->get(route('material-rolls.index', ['material_id' => $material->id]))
            ->assertOk()
            ->assertViewHas('rolls', fn ($rolls) => $rolls->count() === 1);
    }

    public function test_rolls_show_shows_movements_and_balances(): void
    {
        $user = $this->loginWarehouseUser();
        $material = $this->makeMaterial();

        $this->post(route('material-receipts.store'), [
            'material_id' => $material->id,
            'rolls' => [['roll_number' => 'R-MOVE', 'weight' => 100]],
        ]);

        $roll = MaterialRoll::where('roll_number', 'R-MOVE')->first();

        $this->post(route('material-issues.store'), [
            'material_id' => $material->id,
            'roll_id' => $roll->id,
            'weight' => 30,
        ]);

        $response = $this->get(route('material-rolls.show', $roll))
            ->assertOk()
            ->assertViewIs('material-rolls.show');

        $response->assertViewHas('initialWeight', 100.0);
        $response->assertViewHas('currentWeight', 70.0);
        $response->assertViewHas('issuedWeight', 30.0);
        $response->assertViewHas('issuesCount', 1);

        $movements = $response->viewData('movements');
        $this->assertCount(2, $movements);
        // Приход первым, расход вторым, баланс сходится.
        $this->assertSame('receipt', $movements[0]['type']);
        $this->assertSame(100.0, $movements[0]['balance']);
        $this->assertSame('issue', $movements[1]['type']);
        $this->assertSame(70.0, $movements[1]['balance']);
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX API: /api/rolls
    |--------------------------------------------------------------------------
    */

    public function test_api_rolls_returns_rolls_for_material_ordered_and_positive(): void
    {
        $this->loginWarehouseUser();
        $material = $this->makeMaterial();
        $other = $this->makeMaterial('Другой материал');

        MaterialRoll::create(['material_id' => $material->id, 'roll_number' => 'B-2', 'weight' => 20]);
        MaterialRoll::create(['material_id' => $material->id, 'roll_number' => 'A-1', 'weight' => 10]);
        MaterialRoll::create(['material_id' => $material->id, 'roll_number' => 'Z-9', 'weight' => 0]);
        MaterialRoll::create(['material_id' => $other->id, 'roll_number' => 'A-1', 'weight' => 99]);

        $response = $this->getJson(route('api.rolls.by-material', ['material_id' => $material->id]));

        $response->assertOk();
        $rolls = $response->json();

        $this->assertCount(2, $rolls);
        $this->assertSame('A-1', $rolls[0]['roll_number']);
        $this->assertSame(10.0, (float) $rolls[0]['weight']);
        $this->assertSame('B-2', $rolls[1]['roll_number']);
        $this->assertSame(20.0, (float) $rolls[1]['weight']);
    }

    public function test_api_rolls_works_for_any_authenticated_user_without_permission(): void
    {
        // Пользователь без права rolls — всё равно имеет доступ к API.
        $user = $this->makeUserWithPermissions(['materials' => ['view']], 'Оператор', 'operator');
        $this->actingAs($user);

        $material = $this->makeMaterial();
        MaterialRoll::create(['material_id' => $material->id, 'roll_number' => 'R-1', 'weight' => 5]);

        $this->getJson(route('api.rolls.by-material', ['material_id' => $material->id]))
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_api_rolls_returns_empty_array_without_material_id(): void
    {
        $this->loginWarehouseUser();

        $this->getJson(route('api.rolls.by-material'))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_api_rolls_requires_authentication(): void
    {
        $this->post('/logout');

        $this->getJson(route('api.rolls.by-material', ['material_id' => 1]))
            ->assertUnauthorized();
    }

    /*
    |--------------------------------------------------------------------------
    | Полный жизненный цикл: приход -> расход -> склад
    |--------------------------------------------------------------------------
    */

    public function test_full_lifecycle_receipt_then_partial_and_full_issue(): void
    {
        $user = $this->loginWarehouseUser();
        $material = $this->makeMaterial('Фольга 12 мкм');

        // 1. Приход двух рулонов.
        $this->post(route('material-receipts.store'), [
            'material_id' => $material->id,
            'rolls' => [
                ['roll_number' => 'L-1', 'weight' => 200],
                ['roll_number' => 'L-2', 'weight' => 300],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(500.0, (float) $material->rolls()->sum('weight'));
        $this->assertSame(2, $material->rolls()->count());

        // 2. Частичный расход первого рулона.
        $roll1 = MaterialRoll::where('roll_number', 'L-1')->first();
        $this->post(route('material-issues.store'), [
            'material_id' => $material->id,
            'roll_id' => $roll1->id,
            'weight' => 50,
        ])->assertSessionHasNoErrors();

        $this->assertSame(450.0, (float) $material->rolls()->sum('weight'));
        $this->assertSame(150.0, (float) $roll1->fresh()->weight);

        // 3. Полный расход второго рулона — рулон «выдан».
        $roll2 = MaterialRoll::where('roll_number', 'L-2')->first();
        $this->post(route('material-issues.store'), [
            'material_id' => $material->id,
            'roll_id' => $roll2->id,
            'weight' => 300,
        ])->assertSessionHasNoErrors();

        $this->assertSame(0.0, (float) $roll2->fresh()->weight);

        // 4. Итог: один расход, две операции списания, у рулона L-2 остаток 0.
        $this->assertSame(1, MaterialReceipt::count());
        $this->assertSame(2, MaterialReceiptItem::count());
        $this->assertSame(2, MaterialIssue::count());
        $this->assertSame(150.0, (float) $material->rolls()->where('weight', '>', 0)->sum('weight'));

        // 5. В API и на странице рулонов остался только L-1.
        $this->getJson(route('api.rolls.by-material', ['material_id' => $material->id]))
            ->assertOk()
            ->assertJsonCount(1);

        $this->get(route('material-rolls.show', $roll1))
            ->assertOk()
            ->assertViewHas('issuedWeight', 50.0);
    }
}
