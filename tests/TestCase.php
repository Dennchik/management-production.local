<?php

namespace Tests;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Создаёт роль с указанными правами (object => [actions]) и пользователя с ней.
     */
    protected function makeUserWithPermissions(array $permissions, string $roleName = 'Тестовая роль', string $login = 'tester'): User
    {
        $role = Role::create(['name' => $roleName]);

        foreach ($permissions as $object => $actions) {
            foreach ((array) $actions as $action) {
                RolePermission::create([
                    'role_id' => $role->id,
                    'object' => $object,
                    'action' => $action,
                ]);
            }
        }

        return User::create([
            'login' => $login,
            'full_name' => 'Тестовый Пользователь',
            'password' => 'password',
            'role_id' => $role->id,
        ]);
    }

    /**
     * Администратор — роль с именем «Администратор» может всё.
     */
    protected function makeAdmin(string $login = 'admin'): User
    {
        $role = Role::create(['name' => 'Администратор']);

        return User::create([
            'login' => $login,
            'full_name' => 'Админ Системный',
            'password' => 'password',
            'role_id' => $role->id,
        ]);
    }
}
