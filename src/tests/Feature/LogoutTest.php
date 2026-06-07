<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_logout()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->post('/logout');

        $this->assertGuest();
    }
}
