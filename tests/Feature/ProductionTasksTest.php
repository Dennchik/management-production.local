<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\MaterialIssue;
use App\Models\MaterialReceipt;
use App\Models\MaterialReceiptItem;
use App\Models\MaterialRoll;
use App\Models\ProductionLine;
use App\Models\ProductionOperation;
use App\Models\ProductionTask;
use App\Models\ProductionTaskInput;
use App\Models\ProductionTaskOutput;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductionTasksTest extends TestCase
{
	use RefreshDatabase;

	private User $admin;

	protected function setUp(): void
	{
		parent::setUp();

		$this->admin = $this->makeAdmin('admin');
		$this->actingAs($this->admin);
	}

	/* ------------------------------------------------------------------
	 | Фабрики
	 ------------------------------------------------------------------ */

	private function makeMaterial(string $name = 'Материал'): Material
	{
		return Material::create([
			'name' => $name,
			'code' => strtoupper(bin2hex(random_bytes(5))),
			'is_active' => true,
		]);
	}

	private function makeLine(bool $cutting = false, string $code = 'lamination'): array
	{
		$operation = ProductionOperation::create([
			'name' => 'Операция',
			'code' => $code,
			'is_active' => true,
			'is_cutting' => $cutting,
		]);

		$line = ProductionLine::create([
			'name' => 'Линия',
			'production_operation_id' => $operation->id,
		]);

		return [$operation, $line];
	}

	private function makeTask(array $attributes = []): ProductionTask
	{
		$material = $attributes['material'] ?? $this->makeMaterial('Продукция');

		return ProductionTask::create([
			'number' => $attributes['number'] ?? 1,
			'material_id' => $material->id,
			'production_line_id' => $attributes['production_line_id'] ?? null,
			'quantity' => $attributes['quantity'] ?? 100,
			'operator_id' => $attributes['operator_id'] ?? null,
			'status' => $attributes['status'] ?? 'pending',
			'created_by' => $this->admin->id,
			'comment' => $attributes['comment'] ?? null,
		]);
	}

	private function attachMaterials(ProductionTask $task, array $inputIds = [], array $outputIds = []): void
	{
		foreach ($inputIds as $id) {
			DB::table('production_task_material')->insert([
				'task_id' => $task->id,
				'material_id' => $id,
				'direction' => 'input',
			]);
		}

		foreach ($outputIds as $id) {
			DB::table('production_task_material')->insert([
				'task_id' => $task->id,
				'material_id' => $id,
				'direction' => 'output',
			]);
		}
	}

	private function makeRoll(Material $material, float $weight, string $number = 'R-1'): MaterialRoll
	{
		return MaterialRoll::create([
			'material_id' => $material->id,
			'roll_number' => $number,
			'weight' => $weight,
		]);
	}

	private function validPayload(array $overrides = []): array
	{
		[, $line] = $this->makeLine();

		$input = $this->makeMaterial('Сырьё');
		$output = $this->makeMaterial('Продукция');

		return array_replace([
			'number' => '',
			'production_line_id' => $line->id,
			'materials' => [$input->id],
			'output_materials' => [$output->id],
			'quantity' => 100,
			'operator_id' => null,
			'comment' => 'Тестовая задача',
		], $overrides);
	}

	private function startTask(ProductionTask $task): void
	{
		$this->post(route('tasks.start', $task))->assertRedirect();
		$task->refresh();
	}

	/* ------------------------------------------------------------------
	 | Доступ и страницы
	 ------------------------------------------------------------------ */

	public function test_index_denied_without_tasks_permission(): void
	{
		$this->actingAs($this->makeUserWithPermissions([], 'Без прав', 'noroles'))
			->get(route('tasks.index'))
			->assertForbidden();
	}

	public function test_index_allowed_with_tasks_permission(): void
	{
		$task = $this->makeTask();

		$this->get(route('tasks.index'))
			->assertOk()
			->assertSee((string) $task->number, false);
	}

	public function test_guest_is_redirected_to_login(): void
	{
		// setUp логинит админа — для гостевого сценария выходим
		$this->app['auth']->logout();

		$this->get('/tasks')->assertStatus(302)->assertRedirectToRoute('login');
	}

	public function test_create_page_renders_for_admin(): void
	{
		// Замечание: без активных материалов страница падает с 500 —
		// второй @include задачи (выход) не передаёт emptyText в partial
		// production.operations._line-materials-table.
		$this->makeMaterial('Есть материал');

		$this->get(route('tasks.create'))
			->assertOk();
	}

	public function test_create_denied_without_create_permission(): void
	{
		$this->actingAs($this->makeUserWithPermissions(['tasks' => 'view'], 'Только просмотр', 'viewer'))
			->get(route('tasks.create'))
			->assertForbidden();
	}

	public function test_show_renders_for_user_with_view_permission(): void
	{
		$task = $this->makeTask();

		$this->get(route('tasks.show', $task))->assertOk();
	}

	/* ------------------------------------------------------------------
	 | Создание задачи: валидация и номер
	 ------------------------------------------------------------------ */

	public function test_store_creates_task_with_auto_number_and_pending_status(): void
	{
		$response = $this->post(route('tasks.store'), $this->validPayload());

		$task = ProductionTask::query()->firstOrFail();

		$response->assertRedirect(route('tasks.show', $task));
		$this->assertSame('pending', $task->status);
		$this->assertSame(1, (int) $task->number);
		$this->assertSame($this->admin->id, $task->created_by);
		$this->assertSame(100.0, (float) $task->quantity);
	}

	public function test_store_auto_number_continues_after_existing(): void
	{
		$this->makeTask(['number' => 7]);

		$this->post(route('tasks.store'), $this->validPayload());

		$this->assertDatabaseHas('production_tasks', ['number' => 8]);
	}

	public function test_store_requires_quantity(): void
	{
		$this->from(route('tasks.create'))
			->post(route('tasks.store'), $this->validPayload(['quantity' => null]))
			->assertSessionHasErrors('quantity');

		$this->assertDatabaseCount('production_tasks', 0);
	}

	public function test_store_requires_output_material(): void
	{
		$this->from(route('tasks.create'))
			->post(route('tasks.store'), $this->validPayload(['output_materials' => []]))
			->assertSessionHasErrors('output_materials');

		$this->assertDatabaseCount('production_tasks', 0);
	}

	public function test_store_rejects_duplicate_number_within_year(): void
	{
		$this->makeTask(['number' => 3]);

		$this->from(route('tasks.create'))
			->post(route('tasks.store'), $this->validPayload(['number' => '3']))
			->assertSessionHasErrors('number');

		$this->assertSame(1, ProductionTask::count());
	}

	public function test_store_rejects_invalid_operator(): void
	{
		$this->post(route('tasks.store'), $this->validPayload(['operator_id' => 99999]))
			->assertSessionHasErrors('operator_id');
	}

	public function test_store_syncs_input_and_output_materials(): void
	{
		$this->post(route('tasks.store'), $this->validPayload());

		$task = ProductionTask::query()->firstOrFail();

		$this->assertSame(1, $task->inputMaterials()->count());
		$this->assertSame(1, $task->outputMaterials()->count());
		// Выходной материал задачи — первый из выбранных
		$this->assertSame(
			Material::where('name', 'Продукция')->first()->id,
			$task->material_id
		);
	}

	/* ------------------------------------------------------------------
	 | Редактирование
	 ------------------------------------------------------------------ */

	public function test_edit_denied_without_edit_permission(): void
	{
		$task = $this->makeTask();

		$this->actingAs($this->makeUserWithPermissions(['tasks' => 'view'], 'Просмотр', 'viewer'))
			->get(route('tasks.edit', $task))
			->assertForbidden();
	}

	public function test_update_pending_task_changes_fields(): void
	{
		$task = $this->makeTask();
		$this->attachMaterials($task, [$this->makeMaterial('Сырьё')->id], [$task->material_id]);

		$this->put(route('tasks.update', $task), [
			'number' => (string) $task->number,
			'production_line_id' => $task->production_line_id,
			'materials' => [$task->inputMaterials()->first()->id],
			'output_materials' => [$task->material_id],
			'quantity' => 250,
			'comment' => 'Обновлено',
		])->assertRedirect(route('tasks.show', $task));

		$task->refresh();
		$this->assertSame(250.0, (float) $task->quantity);
		$this->assertSame('Обновлено', $task->comment);
	}

	public function test_update_of_in_progress_task_only_touches_quantity_operator_comment(): void
	{
		$task = $this->makeTask(['status' => 'pending']);
		$this->attachMaterials($task, [$this->makeMaterial('Сырьё')->id], [$task->material_id]);
		$this->startTask($task);

		$this->put(route('tasks.update', $task), [
			'quantity' => 55,
			'comment' => 'Правка в работе',
		])->assertRedirect(route('tasks.show', $task));

		$task->refresh();
		$this->assertSame(55.0, (float) $task->quantity);
		$this->assertSame('Правка в работе', $task->comment);
		$this->assertSame('in_progress', $task->status);
	}

	public function test_update_cancelled_task_is_blocked(): void
	{
		$task = $this->makeTask(['status' => 'cancelled']);

		$this->put(route('tasks.update', $task), [
			'quantity' => 55,
			'comment' => 'нельзя',
		])->assertStatus(422);
	}

	/* ------------------------------------------------------------------
	 | Старт задачи
	 ------------------------------------------------------------------ */

	public function test_start_moves_pending_task_to_in_progress_and_sets_operator(): void
	{
		$task = $this->makeTask();

		$this->startTask($task);

		$task->refresh();
		$this->assertSame('in_progress', $task->status);
		$this->assertNotNull($task->started_at);
		$this->assertSame($this->admin->id, $task->operator_id);
	}

	public function test_start_creates_first_output_row(): void
	{
		$task = $this->makeTask();

		$this->startTask($task);

		$this->assertDatabaseHas('production_task_outputs', [
			'task_id' => $task->id,
			'material_id' => $task->material_id,
			'roll_number' => $task->number . '/1',
		]);
	}

	public function test_start_is_denied_for_non_pending_task(): void
	{
		foreach (['in_progress', 'done', 'cancelled'] as $status) {
			$task = $this->makeTask(['number' => $status === 'in_progress' ? 1 : 2, 'status' => $status]);

			$this->post(route('tasks.start', $task))->assertStatus(422);
		}
	}

	public function test_start_requires_execute_permission(): void
	{
		$task = $this->makeTask();

		$this->actingAs($this->makeUserWithPermissions(['tasks' => 'view'], 'Просмотр', 'viewer'))
			->post(route('tasks.start', $task))
			->assertForbidden();

		$this->assertSame('pending', $task->fresh()->status);
	}

	/* ------------------------------------------------------------------
	 | Входы задачи: дозабор рулонов и расход
	 ------------------------------------------------------------------ */

	public function test_add_input_creates_reserve_on_roll(): void
	{
		$task = $this->makeTask();
		$inputMaterial = $this->makeMaterial('Сырьё');
		$this->attachMaterials($task, [$inputMaterial->id], [$task->material_id]);
		$roll = $this->makeRoll($inputMaterial, 100);
		$this->startTask($task);

		$this->post(route('tasks.inputs.store', $task), [
			'inputs' => [
				['material_id' => $inputMaterial->id, 'roll_id' => $roll->id, 'used' => 30],
			],
		])->assertRedirect(route('tasks.show', $task));

		$this->assertDatabaseHas('production_task_inputs', [
			'task_id' => $task->id,
			'roll_id' => $roll->id,
			'actual_weight' => 30,
		]);
		// Сам рулон пока не списан
		$this->assertSame(100.0, (float) $roll->fresh()->weight);
	}

	public function test_add_input_requires_material_to_be_task_input(): void
	{
		$task = $this->makeTask();
		$stranger = $this->makeMaterial('Чужой материал');
		$roll = $this->makeRoll($stranger, 100);
		$this->startTask($task);

		$this->from(route('tasks.show', $task))
			->post(route('tasks.inputs.store', $task), [
				'inputs' => [
					['material_id' => $stranger->id, 'roll_id' => $roll->id, 'used' => 10],
				],
		])->assertSessionHasErrors('inputs.0.material_id');

		$this->assertDatabaseCount('production_task_inputs', 0);
	}

	public function test_add_input_rejects_weight_over_available(): void
	{
		$task = $this->makeTask();
		$material = $this->makeMaterial('Сырьё');
		$this->attachMaterials($task, [$material->id], [$task->material_id]);
		$roll = $this->makeRoll($material, 50);
		$this->startTask($task);

		$this->post(route('tasks.inputs.store', $task), [
			'inputs' => [
				['material_id' => $material->id, 'roll_id' => $roll->id, 'used' => 60],
			],
		])->assertSessionHasErrors('inputs.0.roll_id');

		$this->assertDatabaseCount('production_task_inputs', 0);
	}

	public function test_add_input_denied_unless_in_progress(): void
	{
		$task = $this->makeTask();
		$material = $this->makeMaterial('Сырьё');
		$this->attachMaterials($task, [$material->id], [$task->material_id]);
		$roll = $this->makeRoll($material, 50);

		$this->post(route('tasks.inputs.store', $task), [
			'inputs' => [
				['material_id' => $material->id, 'roll_id' => $roll->id, 'used' => 10],
			],
		])->assertStatus(422);
	}

	public function test_save_input_updates_consumed_weight(): void
	{
		$task = $this->makeTask();
		$material = $this->makeMaterial('Сырьё');
		$this->attachMaterials($task, [$material->id], [$task->material_id]);
		$roll = $this->makeRoll($material, 100);
		$this->startTask($task);

		$input = ProductionTaskInput::create([
			'task_id' => $task->id,
			'material_id' => $material->id,
			'roll_id' => $roll->id,
			'actual_weight' => 10,
		]);

		$this->putJson(route('tasks.inputs.update', [$task, $input]), ['used' => 45])
			->assertOk()
			->assertJsonPath('used', 45)
			->assertJsonPath('remaining', 55);

		$this->assertSame(45.0, (float) $input->fresh()->actual_weight);
	}

	public function test_save_input_rejects_negative_consumption(): void
	{
		$task = $this->makeTask();
		$material = $this->makeMaterial('Сырьё');
		$this->attachMaterials($task, [$material->id], [$task->material_id]);
		$roll = $this->makeRoll($material, 100);
		$this->startTask($task);

		$input = ProductionTaskInput::create([
			'task_id' => $task->id,
			'material_id' => $material->id,
			'roll_id' => $roll->id,
			'actual_weight' => 10,
		]);

		$this->putJson(route('tasks.inputs.update', [$task, $input]), ['used' => -5])
			->assertStatus(422);

		$this->assertSame(10.0, (float) $input->fresh()->actual_weight);
	}

	public function test_save_input_rejects_consumption_over_available_for_other_tasks(): void
	{
		$material = $this->makeMaterial('Сырьё');
		$roll = $this->makeRoll($material, 50);

		// Другая задача в работе резервует 45 кг
		$other = $this->makeTask(['number' => 2]);
		$this->attachMaterials($other, [$material->id], [$other->material_id]);
		$this->startTask($other);
		ProductionTaskInput::create([
			'task_id' => $other->id,
			'material_id' => $material->id,
			'roll_id' => $roll->id,
			'actual_weight' => 45,
		]);

		$task = $this->makeTask(['number' => 3]);
		$this->attachMaterials($task, [$material->id], [$task->material_id]);
		$this->startTask($task);
		$input = ProductionTaskInput::create([
			'task_id' => $task->id,
			'material_id' => $material->id,
			'roll_id' => $roll->id,
			'actual_weight' => 1,
		]);

		$this->putJson(route('tasks.inputs.update', [$task, $input]), ['used' => 10])
			->assertStatus(422);
	}

	public function test_save_input_of_foreign_task_is_not_found(): void
	{
		$taskA = $this->makeTask(['number' => 1]);
		$taskB = $this->makeTask(['number' => 2]);
		$material = $this->makeMaterial('Сырьё');
		$this->attachMaterials($taskA, [$material->id], [$taskA->material_id]);
		$this->startTask($taskA);

		$input = ProductionTaskInput::create([
			'task_id' => $taskB->id,
			'material_id' => $material->id,
			'actual_weight' => 1,
		]);

		$this->putJson(route('tasks.inputs.update', [$taskA, $input]), ['used' => 5])
			->assertNotFound();
	}

	/* ------------------------------------------------------------------
	 | Выходы задачи: строки продукции
	 ------------------------------------------------------------------ */

	public function test_store_output_creates_row_with_sequential_number(): void
	{
		$task = $this->makeTask();
		$this->startTask($task);

		$this->postJson(route('tasks.outputs.store', $task))
			->assertOk()
			->assertJsonPath('success', true);

		$this->assertDatabaseHas('production_task_outputs', [
			'task_id' => $task->id,
			'roll_number' => $task->number . '/2',
		]);
	}

	public function test_store_output_denied_unless_in_progress(): void
	{
		$task = $this->makeTask();

		$this->postJson(route('tasks.outputs.store', $task))->assertStatus(422);
	}

	public function test_update_output_saves_number_and_weight(): void
	{
		$task = $this->makeTask();
		$this->startTask($task);
		$output = $task->outputs()->firstOrFail();

		$this->putJson(route('tasks.outputs.update', [$task, $output]), [
			'roll_number' => 'РУЛОН-77',
			'actual_weight' => '42.5',
		])->assertOk()
			->assertJsonPath('made', 42.5)
			->assertJsonPath('left', 57.5);

		$this->assertSame('РУЛОН-77', $output->fresh()->roll_number);
		$this->assertSame(42.5, (float) $output->fresh()->actual_weight);
	}

	public function test_update_output_of_foreign_task_is_not_found(): void
	{
		$taskA = $this->makeTask(['number' => 1]);
		$taskB = $this->makeTask(['number' => 2]);
		$this->startTask($taskA);

		$output = ProductionTaskOutput::create([
			'task_id' => $taskB->id,
			'material_id' => $taskB->material_id,
			'roll_number' => 'x',
		]);

		$this->putJson(route('tasks.outputs.update', [$taskA, $output]), [
			'roll_number' => 'y',
		])->assertNotFound();
	}

	public function test_destroy_output_removes_row_but_keeps_last_one(): void
	{
		$task = $this->makeTask();
		$this->startTask($task);
		$first = $task->outputs()->firstOrFail();

		$this->deleteJson(route('tasks.outputs.destroy', [$task, $first]))
			->assertStatus(422)
			->assertJsonPath('message', 'У задачи должна остаться хотя бы одна строка продукции.');

		$this->postJson(route('tasks.outputs.store', $task))->assertOk();
		$second = $task->outputs()->orderByDesc('id')->first();

		$this->deleteJson(route('tasks.outputs.destroy', [$task, $second]))->assertOk();
		$this->assertDatabaseMissing('production_task_outputs', ['id' => $second->id]);
		$this->assertDatabaseHas('production_task_outputs', ['id' => $first->id]);
	}

	/* ------------------------------------------------------------------
	 | Завершение: списание сырья и оприходование продукции
	 ------------------------------------------------------------------ */

	public function test_complete_requires_remaining_for_every_taken_roll(): void
	{
		$task = $this->makeTask();
		$material = $this->makeMaterial('Сырьё');
		$this->attachMaterials($task, [$material->id], [$task->material_id]);
		$roll = $this->makeRoll($material, 100);
		$this->startTask($task);
		ProductionTaskInput::create([
			'task_id' => $task->id,
			'material_id' => $material->id,
			'roll_id' => $roll->id,
			'actual_weight' => 30,
		]);
		$output = $task->outputs()->firstOrFail();
		$output->update(['actual_weight' => 50]);

		$this->from(route('tasks.show', $task))
			->post(route('tasks.complete', $task))
			->assertSessionHasErrors('inputs');

		$this->assertSame('in_progress', $task->fresh()->status);
		$this->assertSame(100.0, (float) $roll->fresh()->weight);
	}

	public function test_complete_requires_at_least_one_output_with_weight(): void
	{
		$task = $this->makeTask();
		$this->startTask($task);

		$this->from(route('tasks.show', $task))
			->post(route('tasks.complete', $task))
			->assertSessionHasErrors('outputs');

		$this->assertSame('in_progress', $task->fresh()->status);
	}

	public function test_complete_issues_material_and_receipts_output(): void
	{
		$task = $this->makeTask();
		$material = $this->makeMaterial('Сырьё');
		$this->attachMaterials($task, [$material->id], [$task->material_id]);
		$roll = $this->makeRoll($material, 100, 'СЫРЬЁ-1');
		$this->startTask($task);

		$input = ProductionTaskInput::create([
			'task_id' => $task->id,
			'material_id' => $material->id,
			'roll_id' => $roll->id,
			'actual_weight' => 30,
		]);

		$output = $task->outputs()->firstOrFail();
		$output->update(['roll_number' => 'ГОТОВОЕ-1', 'actual_weight' => 50]);

		$this->post(route('tasks.complete', $task), [
			'inputs' => [
				['id' => $input->id, 'remaining' => '70'],
			],
		])->assertRedirect(route('tasks.show', $task));

		$task->refresh();
		$this->assertSame('done', $task->status);
		$this->assertNotNull($task->completed_at);

		// Списание: рулон 100 → 70, расход зафиксирован, повторное списание исключено
		$this->assertSame(70.0, (float) $roll->fresh()->weight);
		$this->assertDatabaseHas('material_issues', [
			'roll_id' => $roll->id,
			'weight' => 30,
		]);
		$input->refresh();
		$this->assertNotNull($input->issued_at);
		$this->assertSame(30.0, (float) $input->actual_weight);

		// Продукция: новый рулон на выходном материале задачи
		$output->refresh();
		$newRoll = MaterialRoll::query()->findOrFail($output->roll_id);
		$this->assertSame($task->material_id, $newRoll->material_id);
		$this->assertSame('ГОТОВОЕ-1', $newRoll->roll_number);
		$this->assertSame(50.0, (float) $newRoll->weight);

		// Приходный ордер с позицией
		$this->assertSame(1, MaterialReceipt::count());
		$this->assertDatabaseHas('material_receipt_items', [
			'roll_id' => $newRoll->id,
			'weight' => 50,
		]);
	}

	public function test_complete_without_consumption_only_receipts_output(): void
	{
		$task = $this->makeTask();
		$this->startTask($task);

		$output = $task->outputs()->firstOrFail();
		$output->update(['actual_weight' => 100]);

		$this->post(route('tasks.complete', $task))->assertRedirect(route('tasks.show', $task));

		$this->assertSame('done', $task->fresh()->status);
		$this->assertSame(0, MaterialIssue::count());
		$this->assertSame(1, MaterialRoll::count());
	}

	public function test_complete_priming_inherits_input_roll_number_with_pr_suffix(): void
	{
		[, $line] = $this->makeLine(false, 'priming');

		$task = $this->makeTask([
			'production_line_id' => $line->id,
		]);

		$rawMaterial = $this->makeMaterial('Плёнка-основа');
		$this->attachMaterials($task, [$rawMaterial->id], [$task->material_id]);

		$roll = $this->makeRoll($rawMaterial, 100, 'СЫРЬЁ-5');
		$this->startTask($task);

		$input = ProductionTaskInput::create([
			'task_id' => $task->id,
			'material_id' => $rawMaterial->id,
			'roll_id' => $roll->id,
			'actual_weight' => 30,
		]);

		$output = $task->outputs()->firstOrFail();
		$output->update(['actual_weight' => 95]);

		$this->post(route('tasks.complete', $task), [
			'inputs' => [
				['id' => $input->id, 'remaining' => '5'],
			],
		])->assertRedirect(route('tasks.show', $task));

		$newRoll = MaterialRoll::query()->findOrFail($output->fresh()->roll_id);
		$this->assertSame('СЫРЬЁ-5 ПР', $newRoll->roll_number);
	}

	public function test_complete_marks_short_task(): void
	{
		$task = $this->makeTask(['quantity' => 100]);
		$this->startTask($task);

		$output = $task->outputs()->firstOrFail();
		$output->update(['actual_weight' => 80]);

		$this->post(route('tasks.complete', $task))->assertRedirect(route('tasks.show', $task));

		$task->refresh();
		$this->assertSame('done', $task->status);
		$this->assertTrue($task->isShort());
		$this->assertSame('Завершена', $task->statusLabel());
	}

	public function test_complete_denied_unless_in_progress(): void
	{
		$task = $this->makeTask(['status' => 'pending']);

		$this->post(route('tasks.complete', $task))->assertStatus(422);
	}

	/* ------------------------------------------------------------------
	 | Отмена
	 ------------------------------------------------------------------ */

	public function test_cancel_only_from_pending_status(): void
	{
		$task = $this->makeTask();

		$this->post(route('tasks.cancel', $task))
			->assertRedirect(route('tasks.show', $task))
			->assertSessionHas('success');

		$this->assertSame('cancelled', $task->fresh()->status);

		// Повторная отмена (уже не «Ожидает») запрещена
		$this->post(route('tasks.cancel', $task))->assertStatus(422);
	}

	public function test_cancel_denied_for_started_task(): void
	{
		$task = $this->makeTask();
		$this->startTask($task);

		$this->post(route('tasks.cancel', $task))->assertStatus(422);
		$this->assertSame('in_progress', $task->fresh()->status);
	}

	public function test_cancel_requires_cancel_permission(): void
	{
		$task = $this->makeTask();

		$this->actingAs($this->makeUserWithPermissions(['tasks' => 'view'], 'Просмотр', 'viewer'))
			->post(route('tasks.cancel', $task))
			->assertForbidden();

		$this->assertSame('pending', $task->fresh()->status);
	}

	/* ------------------------------------------------------------------
	 | Смена статуса
	 ------------------------------------------------------------------ */

	public function test_status_change_requires_status_permission(): void
	{
		$task = $this->makeTask();
		$this->startTask($task);

		$this->actingAs($this->makeUserWithPermissions(['tasks' => ['view', 'execute']], 'Оператор', 'operator'))
			->post(route('tasks.status', $task), ['status' => 'pending'])
			->assertForbidden();

		$this->assertSame('in_progress', $task->fresh()->status);
	}

	public function test_status_change_with_permission_moves_task_back_to_pending(): void
	{
		$task = $this->makeTask();
		$this->startTask($task);

		$this->post(route('tasks.status', $task), ['status' => 'pending'])
			->assertRedirect(route('tasks.show', $task));

		$task->refresh();
		$this->assertSame('pending', $task->status);
		$this->assertNull($task->started_at);
	}

	public function test_status_reopening_done_task_sets_in_progress_and_keeps_outputs(): void
	{
		$task = $this->makeTask(['status' => 'pending']);
		$this->startTask($task);
		$output = $task->outputs()->firstOrFail();
		$output->update(['actual_weight' => 100]);
		$this->post(route('tasks.complete', $task))->assertRedirect();

		$this->post(route('tasks.status', $task), ['status' => 'in_progress'])
			->assertRedirect(route('tasks.show', $task));

		$task->refresh();
		$this->assertSame('in_progress', $task->status);
		$this->assertNull($task->completed_at);
		// Строка продукции с рулоном сохранилась, дубль не создан
		$this->assertSame(1, $task->outputs()->count());
	}

	public function test_operator_without_status_permission_can_resume_short_done_task(): void
	{
		$task = $this->makeTask(['quantity' => 100]);
		$this->startTask($task);
		$task->outputs()->firstOrFail()->update(['actual_weight' => 40]);
		$this->post(route('tasks.complete', $task))->assertRedirect();

		$this->actingAs($this->makeUserWithPermissions(['tasks' => ['view', 'execute']], 'Оператор', 'operator'))
			->post(route('tasks.status', $task), ['status' => 'in_progress'])
			->assertRedirect(route('tasks.show', $task));

		$this->assertSame('in_progress', $task->fresh()->status);
	}

	public function test_operator_without_status_permission_cannot_resume_fully_done_task(): void
	{
		$task = $this->makeTask(['quantity' => 100]);
		$this->startTask($task);
		$task->outputs()->firstOrFail()->update(['actual_weight' => 100]);
		$this->post(route('tasks.complete', $task))->assertRedirect();

		$this->actingAs($this->makeUserWithPermissions(['tasks' => ['view', 'execute']], 'Оператор', 'operator'))
			->post(route('tasks.status', $task), ['status' => 'in_progress'])
			->assertForbidden();
	}

	public function test_status_change_validates_status_value(): void
	{
		$task = $this->makeTask();

		$this->from(route('tasks.show', $task))
			->post(route('tasks.status', $task), ['status' => 'cancelled'])
			->assertSessionHasErrors('status');

		$this->assertSame('pending', $task->fresh()->status);
	}

	/* ------------------------------------------------------------------
	 | Страница задачи в работе
	 ------------------------------------------------------------------ */

	public function test_show_for_in_progress_task_creates_missing_output_row(): void
	{
		$task = $this->makeTask();
		$this->startTask($task);
		$task->outputs()->delete();

		$this->get(route('tasks.show', $task))->assertOk();

		$this->assertDatabaseHas('production_task_outputs', [
			'task_id' => $task->id,
			'roll_number' => $task->number . '/1',
		]);
	}

	public function test_show_lists_available_rolls_for_in_progress_task(): void
	{
		$task = $this->makeTask();
		$material = $this->makeMaterial('Сырьё');
		$this->attachMaterials($task, [$material->id], [$task->material_id]);
		$roll = $this->makeRoll($material, 100, 'СЫРЬЁ-9');
		$this->startTask($task);

		$this->get(route('tasks.show', $task))
			->assertOk()
			->assertSee('СЫРЬЁ-9', false);
	}
}
