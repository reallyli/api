<?php

namespace Tests\Feature\API;

use Artisan;
use Tests\TestCase;
use App\Models\User;
use App\Events\EmailVerified;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Notification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp()
    {
        parent::setUp();
        Artisan::call('passport:install');
    }

    /** @test */
    public function send_verification_email()
    {
        Notification::fake();
    
        Notification::assertNothingSent();
    
        $user = make(User::class, [
            'email_verified_at' => null,
            'verified'=> false,
        ]);
    
        unset($user['phone_number']);
    
        $this->json('POST', '/api/v1/register', $user->toArray());
    
        Notification::assertSentTo(
            User::first(),
            VerifyEmailNotification::class
        );
    }

    /** @test */
    public function signed_middleware_with_invalid_signature_fails()
    {
        $this->expectException(InvalidSignatureException::class);
        
        $this->get(route('verification.email', ['id' => 1, 'signature' => 'invalid']));
    }
    
    /** @test */
    public function unauthorized_email_verification_fails()
    {
        $this->withoutMiddleware(ValidateSignature::class);
        
        $this->expectException(AuthenticationException::class);
        
        $this->get(route('verification.email', ['id' => 1, 'signature' => 'invalid']));
    }
    
    /** @test */
    public function verify_method_fails_when_a_different_user_verifies()
    {
        $this->withoutMiddleware(ValidateSignature::class);
        
        $this->expectException(AuthorizationException::class);

        $this->passportActingAs();

        $this->get(route('verification.email', ['id' => 2, 'signature' => '1']));
    }

    /** @test */
    public function verify_method_returns_a_message_when_email_has_been_already_verified()
    {
        $this->withoutMiddleware(ValidateSignature::class);
        
        $this->passportActingAs(now());

        $this->getJson(route('verification.email', ['id' => 1, 'signature' => '1']))
            ->assertJson([
                'verification' => 'Email has been already verified.'
            ]);
    }

    /** @test */
    public function email_verified()
    {
        Event::fake();

        $this->withoutMiddleware(ValidateSignature::class);

        $user = $this->passportActingAs();
        $this->assertNull($user->fresh()->email_verified_at);
        
        $this->getJson(route('verification.email', ['id' => 1, 'signature' => '1']))
            ->assertJson([
                'verification' => true
            ]);

        Event::assertDispatched(EmailVerified::class, function ($e) use ($user) {
            return $e->user->id === $user->id;
        });

        $this->assertDatabaseHas('users', ['verified' => true]);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function passportActingAs($emailVerifiedAt = null)
    {
        return Passport::actingAs(factory(User::class)->create([
            'email_verified_at' => $emailVerifiedAt,
            'verified'=> false,
            ]));
    }
}
