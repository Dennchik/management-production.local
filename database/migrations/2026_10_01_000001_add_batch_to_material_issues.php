<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		/**
		 * Расходный ордер становится многострочным: строки одного
		 * сабмита помечаются общим batch_id и показываются как документ.
		 */
		public function up(): void
		{
			Schema::table('material_issues', function (Blueprint $table) {
				$table->string('batch_id', 36)
					->nullable()
					->index()
					->comment('Строки одного расходного ордера');
			});
		}

		public function down(): void
		{
			Schema::table('material_issues', function (Blueprint $table) {
				$table->dropIndex(['batch_id']);
				$table->dropColumn('batch_id');
			});
		}
	};
