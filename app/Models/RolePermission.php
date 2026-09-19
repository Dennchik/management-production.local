<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;

	#[Fillable([
			'role_id',
			'object',
			'action',
	])]
	class RolePermission extends Model
	{
		public $timestamps = false;

		protected function casts(): array
		{
			return [
					'role_id' => 'integer',
			];
		}

		public function role(): BelongsTo
		{
			return $this->belongsTo(Role::class);
		}
	}
