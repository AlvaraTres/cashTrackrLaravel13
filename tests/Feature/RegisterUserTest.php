<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RegisterUserTest extends TestCase
{

    use RefreshDatabase; // 2. Usar el trait aquí adentro


    /**
     * A basic feature test example.
     */
    public function test()
    {
        $response = $this->get(route('register'));
        
        // Verifica que la página cargó con un código 200 (OK)
        $response->assertStatus(200);
    }

    //register a new user as unverified and dispatches the registered event
    public function testRegisterUser(){
        // Esto obligara a Laravel a mostrar el error exacto en la consola
        $this->withoutExceptionHandling();

        Event::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'Diego Alvarado',
            'email' => 'diego@diego2.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);

        $response->assertRedirect(route('verification.notice'));

        // Buscamos el usuario recién creado
        $user = User::where('email' , 'diego@diego2.com')->first();

        // Verificamos que se guardó
        $this->assertNotNull($user);

        // Verificamos que no esté verificado (email_verified_at debe ser null)
        $this->assertNull($user->email_verified_at);

        Event::assertDispatched(Registered::class);
    }

    //Should validate required fields when the request body is empty
    public function testValidateFields(){
        $response = $this->post(route('register.store'), []);

        $response->assertSessionHasErrors([
            'name',
            'email',
            'password'
        ]); 

        $response->assertSessionHasErrors([
            'name' => 'El nombre es obligatorio.',
            'email' => 'El e-mail es obligatorio.',
            'password' => 'La contraseña es obligatoria.'
        ]);
    }

    //Prevents duplicate email address
    public function testPreventDuplicateEmails(){

        User::factory()->create([
            'email' => 'diego@diego2.com'
        ]);

        $response = $this->post(route('register.store'), [
            'name' => 'Diego Alvarado',
            'email' => 'diego@diego2.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);

        $response->assertRedirect();

        $response->assertSessionHasErrors([
            'email' => 'El correo ya se encuentra registrado.',
        ]);
    }

    //Sends the verification email notification after registration
    public function testVerificationEmailNotification(){
        // Esto obligara a Laravel a mostrar el error exacto en la consola
        $this->withoutExceptionHandling();

        Notification::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'Diego Alvarado',
            'email' => 'diego@diego2.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);

        // Buscamos el usuario recién creado
        $user = User::where('email' , 'diego@diego2.com')->first();

        // Se comprueba que no esté verificado (email_verified_at debe ser null)
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    //verifies the user email from a signed verification link
    public function testVerifyEmailFromSignedVerification(){
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        //actua como el usuario ingresa al link de verificación desde su correo
        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect(route('dashboard'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    //does not allow an unverified user to access the dashboard
    public function testNotAllowUserDashboard(){
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('verification.notice'));
    }

    //allow a verified user to access the dashboard
    public function testtAllowAnUserDashboard(){
        $user = User::factory()->create([
            'email_verified_at' => now()
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
    }
}
