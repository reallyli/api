<?php

namespace Tests\Feature\API\Authentication;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\VerifyEmailNotification;

class ResendTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function resend_method_returns_a_message_if_email_has_been_already_verified()
    {
        $this->passportActingAs(now());

        $result = $this->getJson(route('verification.resend'))->json();
        
        $this->assertEquals('User already have verified email.', $result);
    }
    
    /** @test */
    public function email_resended()
    {
        Notification::fake();
    
        Notification::assertNothingSent();
    
        $this->passportActingAs();
        
        $result = $this->getJson(route('verification.resend'))->json();
        
        $this->assertEquals('Email has been reseneded.', $result);

        Notification::assertSentTo(
            User::first(),
            VerifyEmailNotification::class
        );
    }
    
        
    public function passportActingAs($emailVerifiedAt = null)
    {
        return Passport::actingAs(factory(User::class)->create([
            'email_verified_at' => $emailVerifiedAt,
            'verified'=> false,
            ]));
    }
}
