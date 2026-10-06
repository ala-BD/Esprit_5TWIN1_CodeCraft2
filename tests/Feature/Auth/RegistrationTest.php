<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Ben Dupont',
            'prenom' => 'Jean',
            'email' => 'jean.dupont@example.com',
            'role' => 'CLIENT',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'email' => 'jean.dupont@example.com',
            'role' => 'CLIENT',
        ]);
    }
}
