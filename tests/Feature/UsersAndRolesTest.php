<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersAndRolesTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Контроль доступа
    |--------------------------------------------------------------------------
    */

    public function test_guests_are_redirected_to_login_from_users_index(): void
    {
        $this->get(route('users.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_users_permission_gets_403_on_users_index(): void
    {
        $user = $this->makeUserWithPermissions(['materials' => ['view']], 'Без прав', 'no-users');

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_user_without_users_edit_permission_gets_403_on_users_create_store_and_edit(): void
    {
        $viewer = $this->makeUserWithPermissions(['users' => ['view']], 'Только просмотр', 'viewer');
        $target = $this->makeUserWithPermissions(['users' => ['view']], 'Вторая роль', 'target');

        $this->actingAs($viewer)
            ->get(route('users.create'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('users.store'), [
                'login' => 'newbie',
                'full_name' => 'Новый',
                'password' => 'secret',
            ])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('users.edit', $target))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['login' => 'newbie']);
    }

    public function test_user_without_users_permission_gets_403_on_roles_index_and_edit(): void
    {
        $stranger = $this->makeUserWithPermissions(['tasks' => ['view']], 'Посторонний', 'stranger');
        $role = Role::create(['name' => 'Редактируемая']);

        $this->actingAs($stranger)
            ->get(route('roles.index'))
            ->assertForbidden();

        $this->actingAs($stranger)
            ->get(route('roles.edit', $role))
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | UserController: index / create
    |--------------------------------------------------------------------------
    */

    public function test_users_index_lists_users_with_roles_for_admin(): void
    {
        $admin = $this->makeAdmin('boss');
        $plain = $this->makeUserWithPermissions(['users' => ['view']], 'Менеджер', 'ivanov');

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertViewIs('users.index')
            ->assertViewHas('users')
            ->assertSee('boss')
            ->assertSee('ivanov')
            ->assertSee('Менеджер');
    }

    public function test_users_create_shows_form_with_roles_for_admin(): void
    {
        $admin = $this->makeAdmin();
        $this->makeUserWithPermissions(['users' => ['view']], 'Кладовщик', 'stockman');

        $this->actingAs($admin)
            ->get(route('users.create'))
            ->assertOk()
            ->assertViewIs('users.edit')
            ->assertViewHas('roles')
            ->assertSee('name="login"', false)
            ->assertSee('name="full_name"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="role_id"', false);
    }

    /*
    |--------------------------------------------------------------------------
    | UserController: store (валидация и создание)
    |--------------------------------------------------------------------------
    */

    public function test_store_creates_user_with_profile_fields_and_role(): void
    {
        $admin = $this->makeAdmin();
        $role = Role::create(['name' => 'Оператор']);

        $response = $this->actingAs($admin)
            ->post(route('users.store'), [
                'login' => 'petrov',
                'full_name' => 'Петров Пётр Петрович',
                'display_name' => 'Петя',
                'password' => 'secret',
                'role_id' => $role->id,
            ]);

        $response->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $user = User::query()->where('login', 'petrov')->first();
        $this->assertNotNull($user);
        $this->assertSame('Петров Пётр Петрович', $user->full_name);
        $this->assertSame('Петя', $user->display_name);
        $this->assertSame($role->id, $user->role_id);
        $this->assertTrue(password_verify('secret', $user->password), 'Пароль должен быть сохранён в хешированном виде.');
    }

    public function test_store_fails_when_required_fields_missing(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('users.store'), [])
            ->assertSessionHasErrors(['login', 'full_name', 'password']);

        $this->assertDatabaseCount('users', 1); // только админ
    }

    public function test_store_fails_when_login_is_not_unique(): void
    {
        $admin = $this->makeAdmin('existing-login');

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'login' => 'existing-login',
                'full_name' => 'Дубликат',
                'password' => 'secret',
            ])
            ->assertSessionHasErrors('login');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_store_fails_when_role_id_does_not_exist(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'login' => 'ghost',
                'full_name' => 'Призрак',
                'password' => 'secret',
                'role_id' => 99999,
            ])
            ->assertSessionHasErrors('role_id');

        $this->assertDatabaseMissing('users', ['login' => 'ghost']);
    }

    public function test_store_fails_when_password_is_too_short(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'login' => 'shorty',
                'full_name' => 'Короткий Пароль',
                'password' => 'ab',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['login' => 'shorty']);
    }

    public function test_store_is_forbidden_without_users_create_permission(): void
    {
        $editorWithoutCreate = $this->makeUserWithPermissions(['users' => ['view', 'edit']], 'Без создания', 'editor');

        $this->actingAs($editorWithoutCreate)
            ->post(route('users.store'), [
                'login' => 'blocked',
                'full_name' => 'Заблокировано',
                'password' => 'secret',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['login' => 'blocked']);
    }

    /*
    |--------------------------------------------------------------------------
    | UserController: edit / update
    |--------------------------------------------------------------------------
    */

    public function test_edit_shows_user_data_for_admin(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUserWithPermissions(['users' => ['view']], 'Курьер', 'kurer');

        $this->actingAs($admin)
            ->get(route('users.edit', $user))
            ->assertOk()
            ->assertViewIs('users.edit')
            ->assertViewHas('user', fn (User $viewUser) => $viewUser->id === $user->id)
            ->assertSee('value="kurer"', false);
    }

    public function test_update_changes_profile_fields_and_role(): void
    {
        $admin = $this->makeAdmin();
        $oldRole = Role::create(['name' => 'Старая роль']);
        $newRole = Role::create(['name' => 'Новая роль']);
        $user = User::create([
            'login' => 'sidorov',
            'full_name' => 'Сидоров Сидор',
            'password' => 'old-secret',
            'role_id' => $oldRole->id,
        ]);

        $this->actingAs($admin)
            ->post(route('users.update', $user), [
                'login' => 'sidorov',
                'full_name' => 'Сидоров Сидор Сидорович',
                'display_name' => 'Сидор',
                'password' => '',
                'role_id' => $newRole->id,
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Сидоров Сидор Сидорович', $user->full_name);
        $this->assertSame('Сидор', $user->display_name);
        $this->assertSame($newRole->id, $user->role_id);
        $this->assertTrue(password_verify('old-secret', $user->password), 'Пустой пароль не должен менять существующий.');
    }

    public function test_admin_can_change_password_of_any_user(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUserWithPermissions(['users' => ['view']], 'Сменяемый', 'changeable');

        $this->actingAs($admin)
            ->post(route('users.update', $user), [
                'login' => 'changeable',
                'full_name' => 'Сменяемый Пользователь',
                'password' => 'brand-new',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertTrue(password_verify('brand-new', $user->password));
        $this->assertFalse(password_verify('password', $user->password));
    }

    public function test_update_fails_on_duplicate_login_of_another_user(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeUserWithPermissions(['users' => ['view']], 'Чужая роль', 'taken-login');
        $target = $this->makeUserWithPermissions(['users' => ['view']], 'Своя роль', 'free-login');

        $this->actingAs($admin)
            ->post(route('users.update', $target), [
                'login' => 'taken-login',
                'full_name' => $target->full_name,
            ])
            ->assertSessionHasErrors('login');

        $this->assertSame('free-login', $target->refresh()->login);
    }

    public function test_update_ignores_nonexistent_role(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUserWithPermissions(['users' => ['view']], 'Было', 'keeper');

        $this->actingAs($admin)
            ->post(route('users.update', $user), [
                'login' => 'keeper',
                'full_name' => 'Keeper',
                'role_id' => 424242,
            ])
            ->assertSessionHasErrors('role_id');
    }

    public function test_update_is_forbidden_without_users_edit_permission(): void
    {
        $viewer = $this->makeUserWithPermissions(['users' => ['view']], 'Гость карточки', 'readonly');
        $target = $this->makeUserWithPermissions(['users' => ['view']], 'Цель', 'target-user');

        $this->actingAs($viewer)
            ->post(route('users.update', $target), [
                'login' => 'target-user',
                'full_name' => 'Взломано',
                'password' => 'hacked',
            ])
            ->assertForbidden();

        $this->assertSame('Тестовый Пользователь', $target->refresh()->full_name);
    }

    /*
    |--------------------------------------------------------------------------
    | UserController: удаление
    |--------------------------------------------------------------------------
    |
    | ВНИМАНИЕ: в UserController и routes/web.php НЕТ ни страницы подтверждения
    | удаления, ни destroy-метода, ни маршрута DELETE /users/{user}, и нет
    | защиты от удаления самого себя или последнего администратора. Тест ниже
    | фиксирует фактическое поведение: DELETE-маршрут не существует (405).
    |
    */

    public function test_user_delete_route_does_not_exist(): void
    {
        $admin = $this->makeAdmin();
        $user = $this->makeUserWithPermissions(['users' => ['view']], 'Неудаляемый', 'victim');

        $this->actingAs($admin)
            ->delete(route('users.update', $user))
            ->assertStatus(405);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | RoleController: index / edit
    |--------------------------------------------------------------------------
    */

    public function test_roles_index_lists_roles_with_permissions(): void
    {
        $admin = $this->makeAdmin();
        $role = $this->makeUserWithPermissions(['users' => ['view', 'edit']], 'Контролёр', 'controller');

        $this->actingAs($admin)
            ->get(route('roles.index'))
            ->assertOk()
            ->assertViewIs('roles.index')
            ->assertViewHas('roles')
            ->assertViewHas('objects', Role::OBJECTS)
            ->assertViewHas('actions', Role::ACTIONS)
            ->assertSee('Администратор')
            ->assertSee('Контролёр');
    }

    public function test_roles_edit_shows_permission_matrix(): void
    {
        $admin = $this->makeAdmin();
        $role = Role::create(['name' => 'Матрица']);
        RolePermission::create(['role_id' => $role->id, 'object' => 'warehouse', 'action' => 'view']);

        $this->actingAs($admin)
            ->get(route('roles.edit', $role))
            ->assertOk()
            ->assertViewIs('roles.edit')
            ->assertViewHas('role', fn (Role $viewRole) => $viewRole->id === $role->id)
            ->assertSee('permissions[warehouse][]', false);
    }

    /*
    |--------------------------------------------------------------------------
    | RoleController: update (синхронизация прав)
    |--------------------------------------------------------------------------
    */

    public function test_update_syncs_role_permissions(): void
    {
        $admin = $this->makeAdmin();
        $role = Role::create(['name' => 'Синхронизируемая']);
        RolePermission::create(['role_id' => $role->id, 'object' => 'materials', 'action' => 'view']);

        $this->actingAs($admin)
            ->put(route('roles.update', $role), [
                'permissions' => [
                    'warehouse' => ['view', 'create'],
                    'rolls' => ['delete'],
                ],
            ])
            ->assertRedirect(route('roles.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('role_permissions', ['role_id' => $role->id, 'object' => 'materials']);
        $this->assertSame(
            ['rolls' => ['delete'], 'warehouse' => ['create', 'view']],
            $role->permissions()->get()
                ->groupBy('object')
                ->map(fn ($group) => $group->pluck('action')->values()->sort()->all())
                ->sortKeys()
                ->all()
        );
        $this->assertTrue($role->hasPermission('warehouse', 'view'));
        $this->assertTrue($role->hasPermission('warehouse', 'create'));
        $this->assertTrue($role->hasPermission('rolls', 'delete'));
        $this->assertFalse($role->hasPermission('materials', 'view'));
    }

    public function test_update_with_empty_permissions_clears_all(): void
    {
        $admin = $this->makeAdmin();
        $role = Role::create(['name' => 'Опустошаемая']);
        RolePermission::create(['role_id' => $role->id, 'object' => 'tasks', 'action' => 'execute']);
        RolePermission::create(['role_id' => $role->id, 'object' => 'tasks', 'action' => 'view']);

        $this->actingAs($admin)
            ->put(route('roles.update', $role), [])
            ->assertRedirect(route('roles.index'));

        $this->assertDatabaseMissing('role_permissions', ['role_id' => $role->id]);
    }

    public function test_update_ignores_unknown_objects_and_actions(): void
    {
        $admin = $this->makeAdmin();
        $role = Role::create(['name' => 'Фильтрующая']);

        $this->actingAs($admin)
            ->put(route('roles.update', $role), [
                'permissions' => [
                    'nonexistent-object' => ['view'],
                    'users' => ['nonexistent-action', 'view'],
                ],
            ])
            ->assertRedirect(route('roles.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(
            [['object' => 'users', 'action' => 'view']],
            $role->permissions()->get(['object', 'action'])->map(fn ($p) => ['object' => $p->object, 'action' => $p->action])->all()
        );
        $this->assertDatabaseMissing('role_permissions', ['role_id' => $role->id, 'object' => 'nonexistent-object']);
        $this->assertDatabaseMissing('role_permissions', ['role_id' => $role->id, 'action' => 'nonexistent-action']);
    }

    public function test_role_update_is_forbidden_without_users_edit_permission(): void
    {
        $viewer = $this->makeUserWithPermissions(['users' => ['view']], 'Права только на просмотр', 'ro-viewer');
        $role = Role::create(['name' => 'Запертая']);
        RolePermission::create(['role_id' => $role->id, 'object' => 'users', 'action' => 'view']);

        $this->actingAs($viewer)
            ->put(route('roles.update', $role), [
                'permissions' => ['users' => ['view', 'delete']],
            ])
            ->assertForbidden();

        $this->assertTrue($role->hasPermission('users', 'view'));
        $this->assertFalse($role->hasPermission('users', 'delete'));
    }
}
