<?php

namespace App\Models;

//? Фабрика для создания тестовых и демонстрационных пользователей.
use Database\Factories\UserFactory;

//? Разрешённые для массового заполнения и скрытые поля модели.
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;

//? Работа модели с фабрикой Eloquent и системой уведомлений.
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

//? Базовая модель Laravel для пользователей и авторизации.
use Illuminate\Notifications\Notifiable;

#[Fillable(['login', 'full_name', 'display_name', 'password', 'role_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

	/**
	 * Преобразование атрибутов модели в нужные типы.
	 *
	 * @return array<string, string>
	 */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

	public function role(): BelongsTo
	{
		return $this->belongsTo(Role::class);
	}

	/**
	 * Имя для отображения в системе: display_name, иначе ФИО, иначе логин.
	 */
	public function getNameAttribute(): string
	{
		return $this->display_name ?: ($this->full_name ?: $this->login);
	}

	/**
	 * Право пользователя на действие над объектом.
	 * Администратор (роль с именем «Администратор») может всё.
	 */
	public function may(string $object, string $action = 'view'): bool
	{
		if ($this->role?->name === 'Администратор') {
			return true;
		}

		return $this->role?->hasPermission($object, $action) ?? false;
	}
}
