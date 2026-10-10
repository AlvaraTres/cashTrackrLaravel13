<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LoginUserTest extends TestCase
{
    use RefreshDatabase;

    //shows the login screen
    public function testShowLoginScreen(){
        $response = $this->get(route('login'));

        $response->assertOk();
    }

    //log in a verified user successfully
    public function testVerifiedUser(){
        User::factory()->create([
            'email' => 'diego1234@diego.cl',
            'password' => bcrypt('Diego_1234'),
            'email_verified_at' => now()
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'diego1234@diego.cl',
            'password' => 'Diego_1234'
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    //does not log in with invalid credentials
    public function testVerifiedUserNotAllowed(){
        User::factory()->create([
            'email' => 'diego1234@diego.cl',
            'password' => bcrypt('Diego_1234')
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'diego1234@diego.cl',
            'password' => 'incorrectDiego_1234'
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Credenciales Incorrectas');

        $this->assertGuest();

    }

    //prevent unverified user from accessing dashboard
    public function testUnverifiedAccessDashboard(){
        User::factory()->unverified()->create([
            'email' => 'diego1234@diego.cl',
            'password' => bcrypt('Diego_1234')
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'diego1234@diego.cl',
            'password' => 'Diego_1234'
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $dashboardResponse = $this->get(route('dashboard'));
        $dashboardResponse->assertRedirect(route('verification.notice'));
    }

    //does not allow access to dashboard if email is not verified
    public function testNotAllowedAccessDashboard(){
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('verification.notice'));
    }

    //allow access to dashboard if email is verified
    public function testAllowedAccessDashboard(){
        $user = User::factory()->create([
            'email_verified_at' => now()
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
    }

    //fails login if user does not exist
    public function testUserNotExist(){
        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'noexiste@email.com',
            'password' => 'emailnoexiste'
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'El correo ingresado no está asociado a una cuenta. Por favor, registrate.'
        ]);

        $this->assertGuest();
    }
}
