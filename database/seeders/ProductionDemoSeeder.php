<?php

	namespace Database\Seeders;

	use App\Models\Material;
	use App\Models\MaterialIssue;
	use App\Models\MaterialReceipt;
	use App\Models\MaterialReceiptItem;
	use App\Models\MaterialRoll;
	use App\Models\Order;
	use App\Models\ProductionLine;
	use App\Models\ProductionOperation;
	use App\Models\ProductionTask;
	use App\Models\ProductionTaskInput;
	use App\Models\ProductionTaskOutput;
	use App\Models\User;
	use Illuminate\Database\Seeder;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Str;

	class ProductionDemoSeeder extends Seeder
	{
		/**
		 * Демонстрационные данные склада и производства «на все случаи жизни».
		 *
		 * Заполняет:
		 * - разрешённые материалы технологических линий;
		 * - шаблоны производства (линии праймирования, резки, кэширования) со составами;
		 * - заказы с позициями;
		 * - приходные ордера с рулонами — в том числе рулон «Общий вес»
		 *   и рулоны материалов без формата/грамматуры (клей, МК);
		 * - расходный ордер (одна партия, несколько рулонов);
		 * - задачи всех типов: ожидающие, в работе, выполненные
		 *   (полноценно, с недобором, без списания), отменённая —
		 *   для обычного производства, праймирования и резки.
		 *
		 * Запускается после UserSeeder, MaterialSeeder и
		 * ProductionOperationSeeder (нужны пользователи, материалы, операции).
		 */
		public function run(): void
		{
			$admin = User::query()->where('login', 'admin')->firstOrFail();
			$operator = User::query()->where('login', 'operator')->firstOrFail();
			$manager = User::query()->where('login', 'manager')->firstOrFail();

			DB::transaction(function () use ($admin, $operator, $manager) {
				$m = $this->materials();

				$this->seedAllowedOperations($m);

				$lines = $this->seedLines($m);
				$orders = $this->seedOrders($m);

				$rolls = $this->seedReceipts($m, $admin);

				$this->seedRemainingReceipt($admin);

				$this->seedIssue($rolls, $admin);

				$this->seedTasks($m, $lines, $orders, $rolls, $admin, $operator, $manager);
			});
		}

		/**
		 * Материалы справочника из MaterialSeeder, на которых
		 * строится вся демонстрация.
		 *
		 * @return array<string, Material>
		 */
		private function materials(): array
		{
			return [
				// Сырьё с форматом и грамматурой/толщиной
				'paper' => $this->material('Бумага ВП 60', '13'),
				'laminated' => $this->material('Бумага БЛ 60 (40/20) ламинированная', '14'),
				'foil' => $this->material('Фольга алюминиевая 7 мкм', '15', 7),
				'fpo' => $this->material('Пленка FPO SP (Беларусь)', '1', 65),
				'pergament' => $this->material('Пергамент А64', '2'),
				'bopp' => $this->material('Пленка БОПП прозрачная', '9', 30),

				// Материал вообще без формата, грамматуры и толщины
				'glue' => $this->material('Клей 1-но компонентный', '22'),

				// МК: грамматуры нет, у части — только толщина
				'mk3raw' => $this->material('МК 3 не праймированный', '30'),
				'mk4raw' => $this->material('МК 4 не праймированный', '40'),
				'mk4raw076' => $this->material('МК 4 не праймированный 0,76', '40', 0.76),
				'mk3primed' => $this->material('МК 3 праймированный не резаный', '31'),
				'mk4primed' => $this->material('МК 4 праймированный не резаный', '41'),
				'mk3cut' => $this->material('МК 3 праймированный резаный', '32'),
				'mk4cut' => $this->material('МК 4 праймированный резаный', '42'),
			];
		}

		private function material(string $name, string $code, ?float $thickness = null): Material
		{
			return Material::query()
				->where('name', $name)
				->where('code', $code)
				->where('thickness', $thickness)
				->firstOrFail();
		}

		/**
		 * Разрешённые материалы технологических линий.
		 */
		private function seedAllowedOperations(array $m): void
		{
			$operations = ProductionOperation::query()
				->whereIn('code', ['laminating', 'priming', 'cutting'])
				->get()
				->keyBy('code');

			$allowed = [
				'laminating' => [$m['paper'], $m['foil'], $m['fpo'], $m['laminated']],
				'priming' => [$m['mk3raw'], $m['mk4raw'], $m['mk4raw076'], $m['mk3primed'], $m['mk4primed']],
				'cutting' => [$m['mk3primed'], $m['mk4primed'], $m['mk3cut'], $m['mk4cut']],
			];

			foreach ($allowed as $code => $materials) {
				foreach ($materials as $material) {
					DB::table('material_production_operation')->updateOrInsert(
						[
							'material_id' => $material->id,
							'production_operation_id' => $operations[$code]->id,
						],
						[]
					);
				}
			}
		}

		/**
		 * Шаблоны производства со составами вход/выход
		 * (как их пишет контроллер линий — без формата в пивоте).
		 *
		 * @return array<string, ProductionLine>
		 */
		private function seedLines(array $m): array
		{
			$operations = ProductionOperation::query()
				->whereIn('code', ['laminating', 'priming', 'cutting'])
				->get()
				->keyBy('code');

			$definitions = [
				'priming' => [
					'name' => 'Линия праймирования №1',
					'input' => [$m['mk3raw'], $m['mk4raw']],
					'output' => [$m['mk3primed'], $m['mk4primed']],
				],
				'cutting' => [
					'name' => 'Линия резки №1',
					'input' => [$m['mk3primed'], $m['mk4primed']],
					'output' => [$m['mk3cut'], $m['mk4cut']],
				],
				'laminating' => [
					'name' => 'Линия кэширования №1',
					'input' => [$m['paper'], $m['foil'], $m['fpo']],
					'output' => [$m['laminated']],
				],
			];

			$lines = [];

			foreach ($definitions as $code => $definition) {
				$line = ProductionLine::updateOrCreate(
					['name' => $definition['name']],
					['production_operation_id' => $operations[$code]->id]
				);

				foreach (['input', 'output'] as $direction) {
					DB::table('production_line_material')
						->where('production_line_id', $line->id)
						->where('direction', $direction)
						->delete();

					foreach ($definition[$direction] as $material) {
						DB::table('production_line_material')->insert([
							'production_line_id' => $line->id,
							'material_id' => $material->id,
							'direction' => $direction,
						]);
					}
				}

				$lines[$code] = $line;
			}

			return $lines;
		}

		/**
		 * Заказы с позициями; от них создаются задачи.
		 *
		 * @return array<string, Order>
		 */
		private function seedOrders(array $m): array
		{
			$milk = Order::updateOrCreate(
				['client_name' => 'ООО «Молочные реки»'],
				[
					'address' => 'г. Ташкент, ул. Пищевая, 1',
					'status' => 'new',
					'comment' => 'Картонные основы под тираж этикетки',
				]
			);

			$this->orderItem($milk, $m['laminated'], 500, 0);

			$unimilk = Order::updateOrCreate(
				['client_name' => 'АО «Юнимилк»'],
				[
					'address' => 'г. Ташкент, ул. Заводская, 7',
					'status' => 'in_production',
					'comment' => 'Резаный МК под два тиража',
				]
			);

			$this->orderItem($unimilk, $m['mk3cut'], 400, 0);
			$this->orderItem($unimilk, $m['mk4cut'], 250, 1);

			return ['milk' => $milk, 'unimilk' => $unimilk];
		}

		private function orderItem(Order $order, Material $material, float $quantity, int $sortOrder): void
		{
			DB::table('order_items')->updateOrInsert(
				['order_id' => $order->id, 'material_id' => $material->id, 'sort_order' => $sortOrder],
				['quantity' => $quantity, 'unit' => 'kg', 'created_at' => now(), 'updated_at' => now()]
			);
		}

		/**
		 * Приходные ордера с рулонами.
		 *
		 * @return array<string, MaterialRoll>
		 */
		private function seedReceipts(array $m, User $user): array
		{
			$rolls = [];

			// Ордер 1: сырьё — бумага, фольга, плёнки, пергамент, клей.
			// «Общий вес» пергамента — накапливающий рулон без учёта рулонами;
			// клей — материал без формата, грамматуры и толщины.
			$receipt = $this->createReceipt('Первичное поступление сырья', now()->subDays(10), $user);

			$rolls['paper1'] = $this->addReceiptRoll($receipt, $m['paper'], '1001', 1500);
			$rolls['paper2'] = $this->addReceiptRoll($receipt, $m['paper'], '1002', 980);
			$rolls['foil1'] = $this->addReceiptRoll($receipt, $m['foil'], '2001', 800);
			$rolls['foil2'] = $this->addReceiptRoll($receipt, $m['foil'], '2002', 350);
			$rolls['fpo1'] = $this->addReceiptRoll($receipt, $m['fpo'], '3001', 400);
			$rolls['pergament1'] = $this->addReceiptRoll($receipt, $m['pergament'], '4001', 600);
			$rolls['pergamentTotal'] = $this->addReceiptRoll($receipt, $m['pergament'], 'Общий вес', 250);
			$rolls['bopp1'] = $this->addReceiptRoll($receipt, $m['bopp'], '5001', 300);
			$rolls['glue1'] = $this->addReceiptRoll($receipt, $m['glue'], 'К-1', 200);

			// Ордер 2: МК не праймированный (без грамматуры)
			$receipt = $this->createReceipt('Поступление МК не праймированного', now()->subDays(8), $user);

			$rolls['mk3raw1'] = $this->addReceiptRoll($receipt, $m['mk3raw'], '6001', 1200);
			$rolls['mk3raw2'] = $this->addReceiptRoll($receipt, $m['mk3raw'], '6002', 900);
			$rolls['mk4raw1'] = $this->addReceiptRoll($receipt, $m['mk4raw'], '7001', 700);

			// Ордер 3: МК праймированный не резаный
			$receipt = $this->createReceipt('Поступление МК праймированного', now()->subDays(6), $user);

			$rolls['mk3primed1'] = $this->addReceiptRoll($receipt, $m['mk3primed'], '8001', 1000);
			$rolls['mk3primed2'] = $this->addReceiptRoll($receipt, $m['mk3primed'], '8002', 500);
			$rolls['mk4primed1'] = $this->addReceiptRoll($receipt, $m['mk4primed'], '9001', 450);

			return $rolls;
		}

		/**
		 * Доскладирование: каждый материал справочника, у которого ещё
		 * нет рулонов, получает свой рулон в отдельном приходном ордере —
		 * весь справочник остаётся с остатками. Образцы получают
		 * символические веса, остальные материалы — рабочие.
		 */
		private function seedRemainingReceipt(User $user): void
		{
			$receipt = $this->createReceipt('Доскладирование остальных материалов', now()->subDays(3), $user);

			$withoutRolls = Material::query()
				->where('is_active', true)
				->whereDoesntHave('rolls')
				->orderBy('id')
				->get();

			$number = 11001;

			foreach ($withoutRolls as $material) {
				$isSample = str_contains(mb_strtolower($material->name), 'образец');

				$weight = $isSample
					? 10 + ($number % 5) * 7
					: 120 + ($number % 9) * 80;

				$this->addReceiptRoll($receipt, $material, (string) $number, (float) $weight);

				$number++;
			}
		}

		private function createReceipt(string $comment, $createdAt, User $user): MaterialReceipt
		{
			$receipt = MaterialReceipt::create([
				'comment' => $comment,
				'user_id' => $user->id,
			]);

			$receipt->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

			return $receipt;
		}

		private function addReceiptRoll(MaterialReceipt $receipt, Material $material, string $number, float $weight): MaterialRoll
		{
			$roll = MaterialRoll::firstOrCreate(
				['material_id' => $material->id, 'roll_number' => $number],
				['weight' => $weight]
			);

			MaterialReceiptItem::firstOrCreate(
				['material_receipt_id' => $receipt->id, 'roll_id' => $roll->id],
				['material_id' => $material->id, 'weight' => $weight]
			);

			return $roll;
		}

		/**
		 * Расходный ордер: одна партия (batch_id) из двух рулонов.
		 * Задачи списывают сырьё своими расходами в seedTasks.
		 */
		private function seedIssue(array $rolls, User $user): void
		{
			$batchId = (string) Str::uuid();

			// Пергамент: 600 − 50 = 550
			$this->issueRoll($rolls['pergament1'], 50, 'Расход на срочный заказ', $batchId, $user, now()->subDays(4));

			// БОПП: 300 − 40 = 260
			$this->issueRoll($rolls['bopp1'], 40, 'Расход на срочный заказ', $batchId, $user, now()->subDays(4));
		}

		/**
		 * Списание с рулона: позиция расхода + уменьшение веса.
		 * batch_id ставит только ручной расходный ордер; списания
		 * задач идут без него (как в контроллере завершения).
		 */
		private function issueRoll(
			MaterialRoll $roll,
			float $weight,
			string $comment,
			?string $batchId,
			User $user,
			$createdAt
		): void {
			$issue = MaterialIssue::create([
				'material_id' => $roll->material_id,
				'roll_id' => $roll->id,
				'weight' => $weight,
				'batch_id' => $batchId,
				'comment' => $comment,
				'user_id' => $user->id,
			]);

			$issue->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

			$roll->update(['weight' => round((float) $roll->weight - $weight, 3)]);
		}

		/**
		 * Задачи всех типов и статусов.
		 */
		private function seedTasks(
			array $m,
			array $lines,
			array $orders,
			array $rolls,
			User $admin,
			User $operator,
			User $manager
		): void {
			/*
			 * 1. Кэширование — ожидает старта, по заказу.
			 */
			$this->createTask([
				'number' => 1,
				'order_id' => $orders['milk']->id,
				'material_id' => $m['laminated']->id,
				'production_line_id' => $lines['laminating']->id,
				'quantity' => 500,
				'operator_id' => $operator->id,
				'status' => 'pending',
				'created_by' => $manager->id,
				'comment' => 'Партия под заказ «Молочные реки»',
			], [$m['paper'], $m['foil']], [$m['laminated']]);

			/*
			 * 2. Кэширование — в работе: сырьё взято (резерв),
			 * строка продукции пуста — название подставит браузер.
			 */
			$task = $this->createTask([
				'number' => 2,
				'material_id' => $m['laminated']->id,
				'production_line_id' => $lines['laminating']->id,
				'quantity' => 800,
				'operator_id' => $operator->id,
				'status' => 'in_progress',
				'started_at' => now()->subDays(2),
				'created_by' => $manager->id,
			], [$m['paper'], $m['foil']], [$m['laminated']]);

			$this->takeRoll($task, $m['paper'], $rolls['paper1'], 250);
			$this->takeRoll($task, $m['foil'], $rolls['foil1'], 60);
			$this->ensureEmptyOutputs($task, $m['laminated'], 1);

			/*
			 * 3. Кэширование — выполнена с недобором: сырьё списано,
			 * продукция оприходована двумя рулонами (330 из 450).
			 */
			$task = $this->createTask([
				'number' => 3,
				'material_id' => $m['laminated']->id,
				'production_line_id' => $lines['laminating']->id,
				'quantity' => 450,
				'operator_id' => $operator->id,
				'status' => 'done',
				'started_at' => now()->subDays(7),
				'completed_at' => now()->subDays(6),
				'created_by' => $manager->id,
			], [$m['paper'], $m['foil']], [$m['laminated']]);

			// Бумага: 980 − 120 = 860
			$this->issueRoll($rolls['paper2'], 120, 'Задача №3 (Бумага БЛ 60 (40/20) ламинированная)', null, $admin, now()->subDays(6));
			$this->consumeRoll($task, $m['paper'], $rolls['paper2'], 120, now()->subDays(6));

			// Фольга: 350 − 30 = 320
			$this->issueRoll($rolls['foil2'], 30, 'Задача №3 (Бумага БЛ 60 (40/20) ламинированная)', null, $admin, now()->subDays(6));
			$this->consumeRoll($task, $m['foil'], $rolls['foil2'], 30, now()->subDays(6));

			$this->receiptTaskOutputs($task, $m['laminated'], [
				['number' => '3/1', 'weight' => 180],
				['number' => '3/2', 'weight' => 150],
			], now()->subDays(6), $admin);

			/*
			 * 4. Праймирование — ожидает старта.
			 */
			$this->createTask([
				'number' => 4,
				'material_id' => $m['mk3primed']->id,
				'production_line_id' => $lines['priming']->id,
				'quantity' => 600,
				'status' => 'pending',
				'created_by' => $manager->id,
			], [$m['mk3raw']], [$m['mk3primed']]);

			/*
			 * 5. Праймирование — в работе.
			 */
			$task = $this->createTask([
				'number' => 5,
				'material_id' => $m['mk3primed']->id,
				'production_line_id' => $lines['priming']->id,
				'quantity' => 900,
				'operator_id' => $operator->id,
				'status' => 'in_progress',
				'started_at' => now()->subDay(),
				'created_by' => $manager->id,
			], [$m['mk3raw']], [$m['mk3primed']]);

			$this->takeRoll($task, $m['mk3raw'], $rolls['mk3raw1'], 400);
			$this->ensureEmptyOutputs($task, $m['mk3primed'], 1);

			/*
			 * 6. Праймирование — выполнена: выходной рулон назван
			 * по входному с «ПР» (как подставил браузер), 370 из 350.
			 */
			$task = $this->createTask([
				'number' => 6,
				'material_id' => $m['mk3primed']->id,
				'production_line_id' => $lines['priming']->id,
				'quantity' => 350,
				'operator_id' => $operator->id,
				'status' => 'done',
				'started_at' => now()->subDays(5),
				'completed_at' => now()->subDays(5),
				'created_by' => $manager->id,
			], [$m['mk3raw']], [$m['mk3primed']]);

			// МК 3 не праймированный: 900 − 380 = 520
			$this->issueRoll($rolls['mk3raw2'], 380, 'Задача №6 (МК 3 праймированный не резаный)', null, $admin, now()->subDays(5));
			$this->consumeRoll($task, $m['mk3raw'], $rolls['mk3raw2'], 380, now()->subDays(5));

			$this->receiptTaskOutputs($task, $m['mk3primed'], [
				['number' => '6002 ПР', 'weight' => 370],
			], now()->subDays(5), $admin);

			/*
			 * 7. Резка — в работе: план указан на входе,
			 * три пустые строки продукции.
			 */
			$task = $this->createTask([
				'number' => 7,
				'order_id' => $orders['unimilk']->id,
				'material_id' => $m['mk3cut']->id,
				'production_line_id' => $lines['cutting']->id,
				'quantity' => 500,
				'operator_id' => $operator->id,
				'status' => 'in_progress',
				'started_at' => now()->subDays(3),
				'created_by' => $manager->id,
			], [$m['mk3primed']], [$m['mk3cut']]);

			$this->takeRoll($task, $m['mk3primed'], $rolls['mk3primed1'], 500);
			$this->ensureEmptyOutputs($task, $m['mk3cut'], 3);

			/*
			 * 8. Резка — выполнена: один рулон разрезан на три.
			 */
			$task = $this->createTask([
				'number' => 8,
				'order_id' => $orders['unimilk']->id,
				'material_id' => $m['mk3cut']->id,
				'production_line_id' => $lines['cutting']->id,
				'quantity' => 400,
				'operator_id' => $operator->id,
				'status' => 'done',
				'started_at' => now()->subDays(9),
				'completed_at' => now()->subDays(8),
				'created_by' => $manager->id,
			], [$m['mk3primed']], [$m['mk3cut']]);

			// МК 3 праймированный не резаный: 500 − 400 = 100
			$this->issueRoll($rolls['mk3primed2'], 400, 'Задача №8 (МК 3 праймированный резаный)', null, $admin, now()->subDays(8));
			$this->consumeRoll($task, $m['mk3primed'], $rolls['mk3primed2'], 400, now()->subDays(8));

			$this->receiptTaskOutputs($task, $m['mk3cut'], [
				['number' => '8/1', 'weight' => 150],
				['number' => '8/2', 'weight' => 140],
				['number' => '8/3', 'weight' => 95],
			], now()->subDays(8), $admin);

			/*
			 * 9. Отменённая задача.
			 */
			$this->createTask([
				'number' => 9,
				'material_id' => $m['laminated']->id,
				'production_line_id' => $lines['laminating']->id,
				'quantity' => 300,
				'status' => 'cancelled',
				'created_by' => $manager->id,
				'comment' => 'Отменена: клиент отказался от тиража',
			], [$m['paper']], [$m['laminated']]);

			/*
			 * 10. Выполнена без списания: рулоны-сырьё не брались,
			 * продукция оприходована напрямую (перенесённая задача).
			 */
			$task = $this->createTask([
				'number' => 10,
				'material_id' => $m['mk4cut']->id,
				'production_line_id' => $lines['cutting']->id,
				'quantity' => 200,
				'operator_id' => $operator->id,
				'status' => 'done',
				'started_at' => now()->subDays(12),
				'completed_at' => now()->subDays(11),
				'created_by' => $manager->id,
				'comment' => 'Продукция перенесена с предыдущего периода',
			], [$m['mk4primed']], [$m['mk4cut']]);

			$this->receiptTaskOutputs($task, $m['mk4cut'], [
				['number' => '10/1', 'weight' => 200],
			], now()->subDays(11), $admin);
		}

		/**
		 * Создаёт задачу с составом материалов (вход/выход).
		 *
		 * @param array<int, Material> $inputs
		 * @param array<int, Material> $outputs
		 */
		private function createTask(array $attributes, array $inputs, array $outputs): ProductionTask
		{
			$task = ProductionTask::updateOrCreate(
				['number' => $attributes['number']],
				$attributes
			);

			DB::table('production_task_material')
				->where('task_id', $task->id)
				->delete();

			foreach (['input' => $inputs, 'output' => $outputs] as $direction => $materials) {
				foreach ($materials as $material) {
					DB::table('production_task_material')->insert([
						'task_id' => $task->id,
						'material_id' => $material->id,
						'direction' => $direction,
					]);
				}
			}

			return $task;
		}

		/**
		 * Взятый рулон задачи: предполагаемый расход становится резервом.
		 */
		private function takeRoll(ProductionTask $task, Material $material, MaterialRoll $roll, float $weight): void
		{
			ProductionTaskInput::firstOrCreate(
				['task_id' => $task->id, 'roll_id' => $roll->id],
				[
					'material_id' => $material->id,
					'actual_weight' => $weight,
				]
			);
		}

		/**
		 * Списанный рулон выполненной задачи: расход зафиксирован,
		 * рулон больше не резервируется.
		 */
		private function consumeRoll(
			ProductionTask $task,
			Material $material,
			MaterialRoll $roll,
			float $used,
			$issuedAt
		): void {
			ProductionTaskInput::firstOrCreate(
				['task_id' => $task->id, 'roll_id' => $roll->id],
				[
					'material_id' => $material->id,
					'actual_weight' => $used,
					'issued_at' => $issuedAt,
				]
			);
		}

		/**
		 * Пустые строки продукции задачи в работе:
		 * названия подставит браузер при взятии рулонов.
		 */
		private function ensureEmptyOutputs(ProductionTask $task, Material $material, int $count): void
		{
			$existing = ProductionTaskOutput::query()
				->where('task_id', $task->id)
				->where('roll_number', '')
				->count();

			for ($i = $existing; $i < $count; $i++) {
				ProductionTaskOutput::create([
					'task_id' => $task->id,
					'material_id' => $material->id,
					'roll_number' => '',
					'actual_weight' => null,
				]);
			}
		}

		/**
		 * Оприходование продукции выполненной задачи:
		 * строки продукции → новые рулоны + приходный ордер с позициями.
		 *
		 * @param array<int, array{number: string, weight: float}> $rows
		 */
		private function receiptTaskOutputs(ProductionTask $task, Material $material, array $rows, $completedAt, User $user): void
		{
			$comment = 'Задача №' . $task->number . ' (' . ($task->material?->name ?? '') . ')';

			$receipt = $this->createReceipt($comment, $completedAt, $user);

			foreach ($rows as $row) {
				$output = ProductionTaskOutput::firstOrCreate(
					['task_id' => $task->id, 'roll_number' => $row['number']],
					[
						'material_id' => $material->id,
						'actual_weight' => $row['weight'],
					]
				);

				$roll = MaterialRoll::firstOrCreate(
					['material_id' => $material->id, 'roll_number' => $row['number']],
					['weight' => $row['weight']]
				);

				$output->update(['roll_id' => $roll->id]);

				MaterialReceiptItem::firstOrCreate(
					['material_receipt_id' => $receipt->id, 'roll_id' => $roll->id],
					['material_id' => $material->id, 'weight' => $row['weight']]
				);
			}
		}
	}
