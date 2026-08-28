<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\HasMany;

	#[Fillable([
			'order_number',
			'product_type',
			'paper_material_id',
			'foil_material_id',
			'glue_material_id',
			'paper_identifier',
			'foil_identifier',
			'glue_identifier',
			'status',
			'comment',
			'user_id',
	])]
	class LaminationOrder extends Model
	{
		/**
		 * Строки выполнения задания.
		 */
		public function items(): HasMany
		{
			return $this->hasMany(LaminationOrderItem::class);
		}

		/**
		 * Материал бумаги.
		 */
		public function paperMaterial(): BelongsTo
		{
			return $this->belongsTo(Material::class, 'paper_material_id');
		}

		/**
		 * Материал фольги.
		 */
		public function foilMaterial(): BelongsTo
		{
			return $this->belongsTo(Material::class, 'foil_material_id');
		}

		/**
		 * Материал клея.
		 */
		public function glueMaterial(): BelongsTo
		{
			return $this->belongsTo(Material::class, 'glue_material_id');
		}

		/**
		 * Пользователь, создавший задание.
		 */
		public function user(): BelongsTo
		{
			return $this->belongsTo(User::class);
		}
	}