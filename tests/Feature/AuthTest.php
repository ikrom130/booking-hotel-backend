<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'User Test',
            'email' => 'usertest@gmail.com',
            'password' => '123456'
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'email']
        ]);
    }

    /** @test */
    public function user_cannot_register_with_same_email_twice()
    {
        User::factory()->create([
            'email' => 'usertest@gmail.com'
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'User Test',
            'email' => 'usertest@gmail.com',
            'password' => '123456'
        ]);

        $response->assertStatus(422); // validation error
    }

    /** @test */
    public function user_can_login()
    {
        User::factory()->create([
            'email' => 'usertest@gmail.com',
            'password' => bcrypt('123456')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'usertest@gmail.com',
            'password' => '123456'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'token',
            'user'
        ]);
    }
}
