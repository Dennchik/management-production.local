<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthAndProfileTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | AuthController: GET /login
    |--------------------------------------------------------------------------
    */

    public function test_guest_sees_login_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertViewIs('auth.login');
    }

    public function test_authenticated_user_is_redirected_away_from_login_page(): void
    {
        $user = $this->makeAdmin();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    /*
    |--------------------------------------------------------------------------
    | AuthController: POST /login
    |--------------------------------------------------------------------------
    */

    public function test_login_with_valid_credentials_redirects_to_dashboard(): void
    {
        $user = $this->makeAdmin('ivan');

        $this->post(route('login.attempt'), [
            'login' => 'ivan',
            'password' => 'password',
        ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_wrong_password_fails_with_error(): void
    {
        $this->makeAdmin('ivan');

        $response = $this->from(route('login'))->post(route('login.attempt'), [
            'login' => 'ivan',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_login_with_unknown_login_fails(): void
    {
        $response = $this->from(route('login'))->post(route('login.attempt'), [
            'login' => 'ghost',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_login_requires_login_and_password(): void
    {
        $response = $this->from(route('login'))->post(route('login.attempt'), []);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['login', 'password']);
        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | AuthController: POST /logout
    |--------------------------------------------------------------------------
    */

    public function test_authenticated_user_can_logout(): void
    {
        $user = $this->makeAdmin();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_guest_cannot_logout(): void
    {
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | DashboardController: GET /
    |--------------------------------------------------------------------------
    */

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_guest_gets_401_for_json_request_to_dashboard(): void
    {
        $this->getJson(route('dashboard'))->assertStatus(401);
    }

    public function test_authorized_user_sees_dashboard(): void
    {
        $user = $this->makeUserWithPermissions([], 'Оператор', 'operator');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard.index')
            ->assertViewHasAll(['materialsCount', 'rollsCount', 'totalWeight', 'productionStats']);
    }

    /*
    |--------------------------------------------------------------------------
    | UserController: GET /profile
    |--------------------------------------------------------------------------
    */

    public function test_guest_is_redirected_to_login_from_profile(): void
    {
        $this->get(route('profile.show'))->assertRedirect(route('login'));
    }

    public function test_authorized_user_sees_own_profile(): void
    {
        $user = $this->makeUserWithPermissions([], 'Без прав', 'petya');

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertViewIs('users.profile')
            ->assertViewHas('user', fn (User $viewUser) => $viewUser->id === $user->id);
    }

    /*
    |--------------------------------------------------------------------------
    | UserController: POST /profile/password
    |--------------------------------------------------------------------------
    */

    public function test_user_can_change_own_password_with_correct_current_password(): void
    {
        $user = $this->makeAdmin('ivan');
        $userId = $user->id;

        $this->actingAs($user)
            ->from(route('profile.show'))
            ->post(route('profile.password'), [
                'current_password' => 'password',
                'password' => 'new-secret',
                'password_confirmation' => 'new-secret',
            ])
            ->assertRedirect(route('profile.show'))
            ->assertSessionHas('success');

        $fresh = User::findOrFail($userId);
        $this->assertTrue(Hash::check('new-secret', $fresh->password));
        $this->assertFalse(Hash::check('password', $fresh->password));
    }

    public function test_change_password_with_wrong_current_password_fails(): void
    {
        $user = $this->makeAdmin('ivan');

        $this->actingAs($user)
            ->from(route('profile.show'))
            ->post(route('profile.password'), [
                'current_password' => 'not-the-current-one',
                'password' => 'new-secret',
                'password_confirmation' => 'new-secret',
            ])
            ->assertRedirect(route('profile.show'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_change_password_requires_confirmation_to_match(): void
    {
        $user = $this->makeAdmin('ivan');

        $this->actingAs($user)
            ->from(route('profile.show'))
            ->post(route('profile.password'), [
                'current_password' => 'password',
                'password' => 'new-secret',
                'password_confirmation' => 'different',
            ])
            ->assertRedirect(route('profile.show'))
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_change_password_requires_minimum_length(): void
    {
        $user = $this->makeAdmin('ivan');

        $this->actingAs($user)
            ->from(route('profile.show'))
            ->post(route('profile.password'), [
                'current_password' => 'password',
                'password' => 'ab',
                'password_confirmation' => 'ab',
            ])
            ->assertRedirect(route('profile.show'))
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_change_password_requires_current_password_field(): void
    {
        $user = $this->makeAdmin('ivan');

        $this->actingAs($user)
            ->from(route('profile.show'))
            ->post(route('profile.password'), [
                'password' => 'new-secret',
                'password_confirmation' => 'new-secret',
            ])
            ->assertRedirect(route('profile.show'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_guest_cannot_change_password(): void
    {
        $this->post(route('profile.password'), [
            'current_password' => 'password',
            'password' => 'new-secret',
            'password_confirmation' => 'new-secret',
        ])->assertRedirect(route('login'));
    }
}
