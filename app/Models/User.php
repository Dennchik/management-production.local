<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

//? Фабрика для создания тестовых и демонстрационных пользователей.
use Database\Factories\UserFactory;

//? Разрешённые для массового заполнения и скрытые поля модели.
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;

//? Работа модели с фабрикой Eloquent и системой уведомлений.
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

//? Базовая модель Laravel для пользователей и авторизации.
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
