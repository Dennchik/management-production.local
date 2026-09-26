<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;

	#[Fillable([
		'material_id',
		'format',
	])]
	class MaterialFormat extends Model
	{
		public function casts(): array
		{
			return [
				'format' => 'integer',
			];
		}

		public function material(): BelongsTo
		{
			return $this->belongsTo(Material::class);
		}
	}
