<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\HasMany;

	#[Fillable([
			'name',
	])]
	class Role extends Model
	{
		public const OBJECTS = [
				'materials' => 'Материалы',
				'tasks' => 'Производственные задачи',
				'warehouse' => 'Склад',
				'rolls' => 'Рулоны',
				'operations' => 'Технологические линии',
				'reports' => 'Отчёты',
				'users' => 'Пользователи и роли',
		];

		public const ACTIONS = [
				'view' => 'Смотреть',
				'create' => 'Создавать',
				'edit' => 'Редактировать',
				'delete' => 'Удалять',
		];

		protected function casts(): array
		{
			return [
					'name' => 'string',
			];
		}

		public function permissions(): HasMany
		{
			return $this->hasMany(RolePermission::class);
		}

		/**
		 * Есть ли у роли право на действие над объектом.
		 */
		public function hasPermission(string $object, string $action): bool
		{
			return $this->permissions()
					->where('object', $object)
					->where('action', $action)
					->exists();
		}

		/**
		 * Заменяет все права роли на переданные.
		 */
		public function syncPermissions(array $permissions): void
		{
			$rows = [];

			foreach ($permissions as $object => $actions) {
				foreach ((array) $actions as $action) {
					if (isset(static::OBJECTS[$object]) && isset(static::ACTIONS[$action])) {
						$rows[] = ['object' => $object, 'action' => $action];
					}
				}
			}

			$this->permissions()->delete();

			if ($rows !== []) {
				$this->permissions()->createMany($rows);
			}
		}
	}
