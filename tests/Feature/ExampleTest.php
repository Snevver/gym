<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/')->assertRedirect('/inchecken');
        $this->get('/calendar')->assertRedirect('/inchecken');
        $this->getJson('/api/exercises')->assertUnauthorized();
    }

    public function test_login_page_loads(): void
    {
        $this->get('/inchecken')->assertOk()->assertSee('data-page="Login"', false);
    }

    public function test_gym_pages_load_for_logged_in_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertOk()->assertSee('data-page="GymLog"', false);
        $this->actingAs($user)->get('/calendar')->assertOk();
        $this->actingAs($user)->get('/scheme/1')->assertOk();
        $this->actingAs($user)->getJson('/api/exercises')->assertOk();
    }

    public function test_logged_in_user_skips_login_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/inchecken')->assertRedirect('/');
    }

    public function test_session_cookie_is_app_specific_and_host_only(): void
    {
        $this->assertSame('gym_session', config('session.cookie'));
        $this->assertNull(config('session.domain'));
    }
}
