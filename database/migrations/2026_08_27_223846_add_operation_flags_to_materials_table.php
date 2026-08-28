<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		public function up(): void
		{
			Schema::table('materials', function (Blueprint $table) {
				$table->boolean('lamination_allowed')
						->default(false)
						->comment('Разрешена ламинация');

				$table->boolean('priming_allowed')
						->default(false)
						->comment('Разрешено праймирование');

				$table->boolean('cutting_allowed')
						->default(false)
						->comment('Разрешена резка');

				$table->boolean('printing_allowed')
						->default(false)
						->comment('Разрешена печать');
			});
		}

		public function down(): void
		{
			Schema::table('materials', function (Blueprint $table) {
				$table->dropColumn([
						'lamination_allowed',
						'priming_allowed',
						'cutting_allowed',
						'printing_allowed',
				]);
			});
		}
	};