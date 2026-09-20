<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class ApiEndpointsTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $namespace = 'App';
    protected $migrateOnce = true;
    protected $seedOnce = true;
    protected $seed = \App\Database\Seeds\UserSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testSpaRootReturnsOk(): void
    {
        $result = $this->get('/');
        $result->assertStatus(200);
        $result->assertSee('CI4 Modern SPA');
    }

    public function testAuthMeUnauthenticated(): void
    {
        $result = $this->get('/api/auth/me');
        $result->assertStatus(401);
    }

    public function testDashboardProtectedWithoutAuth(): void
    {
        $result = $this->get('/api/dashboard/stats');
        $result->assertStatus(401);
    }

    public function testUsersProtectedWithoutAuth(): void
    {
        $result = $this->get('/api/users');
        $result->assertStatus(401);
    }

    public function testLoginFailsOnMissingFields(): void
    {
        $result = $this->post('/api/auth/login', []);
        $result->assertStatus(422);
    }

    public function testLoginFailsOnInvalidCredentials(): void
    {
        $result = $this->post('/api/auth/login', [
            'email'    => 'nonexistent@example.com',
            'password' => 'wrongpass',
        ]);
        $result->assertStatus(401);
    }

    public function testLoginSuccessAndSession(): void
    {
        $result = $this->post('/api/auth/login', [
            'email'    => 'admin@example.com',
            'password' => 'admin123',
        ]);
        $result->assertStatus(200);
        $result->assertSessionHas('isLoggedIn', true);
        $result->assertSessionHas('role', 'admin');
    }

    public function testRegisterSuccess(): void
    {
        $result = $this->post('/api/auth/register', [
            'username'         => 'newguy',
            'email'            => 'newguy@example.com',
            'password'         => 'password123',
            'password_confirm' => 'password123',
        ]);
        $result->assertStatus(201);
    }

    public function testDashboardStatsWhenAuthenticated(): void
    {
        $result = $this->withSession([
            'user_id'    => 1,
            'username'   => 'admin',
            'role'       => 'admin',
            'isLoggedIn' => true,
        ])->get('/api/dashboard/stats');

        $result->assertStatus(200);
        $result->assertSee('totalUsers');
    }

    public function testUsersCrudFlow(): void
    {
        $sessionData = [
            'user_id'    => 1,
            'username'   => 'admin',
            'role'       => 'admin',
            'isLoggedIn' => true,
        ];

        // 1. Get users
        $resList = $this->withSession($sessionData)->get('/api/users');
        $resList->assertStatus(200);

        // 2. Create user
        $resCreate = $this->withSession($sessionData)->post('/api/users', [
            'username' => 'testcreated',
            'email'    => 'testcreated@example.com',
            'password' => 'secret123',
            'role'     => 'user',
        ]);
        $resCreate->assertStatus(201);

        // 3. Prevent deleting self
        $resDeleteSelf = $this->withSession($sessionData)->delete('/api/users/1');
        $resDeleteSelf->assertStatus(403);
    }
}
