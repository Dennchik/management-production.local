<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\HasMany;

	#[Fillable([
			'name',
			'code',
			'grammage',
			'thickness',
			'format',
			'identifier',
			'lamination_allowed',
			'priming_allowed',
			'cutting_allowed',
			'printing_allowed',
	])]
	class Material extends Model
	{
		protected function casts(): array
		{
			return [
					'grammage' => 'decimal:2',
					'thickness' => 'decimal:2',
					'lamination_allowed' => 'boolean',
					'priming_allowed' => 'boolean',
					'cutting_allowed' => 'boolean',
					'printing_allowed' => 'boolean',
			];
		}

		/**
		 * Физические рулоны этого типа материала.
		 */
		public function rolls(): HasMany
		{
			return $this->hasMany(MaterialRoll::class);
		}

		/**
		 * Операции оприходования этого материала.
		 */
		public function receipts(): HasMany
		{
			return $this->hasMany(MaterialReceipt::class);
		}

		/**
		 * Операции расхода этого материала.
		 */
		public function issues(): HasMany
		{
			return $this->hasMany(MaterialIssue::class);
		}
	}