<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ImapConnectionTester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_login_with_valid_imap_credentials(): void
    {
        $mockTester = Mockery::mock(ImapConnectionTester::class);
        $mockTester->shouldReceive('test')
            ->once()
            ->andReturn([
                'success' => true,
                'folders' => ['INBOX', 'Sent'],
            ]);
        $this->app->instance(ImapConnectionTester::class, $mockTester);

        Livewire::test(\App\Livewire\LoginForm::class)
            ->set('email', 'test@tonmoyinfrastructure.org')
            ->set('password', 'SecretPassword123')
            ->call('login')
            ->assertRedirect(route('mailbox'));

        $this->assertTrue(Auth::guard('web')->check());
        $this->assertEquals('test@tonmoyinfrastructure.org', Auth::user()->email);
        $this->assertDatabaseHas('users', ['email' => 'test@tonmoyinfrastructure.org']);
    }

    /** @test */
    public function user_can_login_with_local_credentials_when_imap_fails(): void
    {
        $user = User::factory()->create([
            'email' => 'local@example.com',
            'password' => 'LocalPassword123',
        ]);

        $mockTester = Mockery::mock(ImapConnectionTester::class);
        $mockTester->shouldReceive('test')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'IMAP connection failed',
            ]);
        $this->app->instance(ImapConnectionTester::class, $mockTester);

        Livewire::test(\App\Livewire\LoginForm::class)
            ->set('email', 'local@example.com')
            ->set('password', 'LocalPassword123')
            ->call('login')
            ->assertRedirect(route('mailbox'));

        $this->assertTrue(Auth::guard('web')->check());
        $this->assertEquals($user->id, Auth::id());
    }

    /** @test */
    public function login_fails_with_invalid_credentials(): void
    {
        $mockTester = Mockery::mock(ImapConnectionTester::class);
        $mockTester->shouldReceive('test')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'IMAP authentication failed',
            ]);
        $this->app->instance(ImapConnectionTester::class, $mockTester);

        Livewire::test(\App\Livewire\LoginForm::class)
            ->set('email', 'nonexistent@example.com')
            ->set('password', 'WrongPassword')
            ->call('login')
            ->assertSee('Invalid email or password');

        $this->assertFalse(Auth::guard('web')->check());
    }
}
