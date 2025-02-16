<?php

namespace Tests\Feature\Controllers;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiAccessControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_prefix_has_v1()
    {
        $apiKey = ApiKey::factory()->create([
            'key' => 'valid-key',
            'user_id' => User::factory()->create(['api_active' => true])->id,
        ]);

        $this->withHeaders(['X-Api-Key' => $apiKey->key])
            ->get('/v1/test-api-access')
            ->assertStatus(200);
    }

    public function test_it_returns_401_when_no_api_key_provided(): void
    {
        $response = $this->get(route('test-api-access')); // replace '/test-api-access' with a route protected by this middleware

        $response->assertStatus(401);
    }

    public function test_it_returns_403_when_invalid_api_key_provided(): void
    {
        $this->withHeaders(['X-Api-Key' => 'invalid-key'])
            ->get(route('test-api-access'))
            ->assertStatus(403);
    }

    public function test_it_allows_access_with_valid_api_key_in_header(): void
    {
        $apiKey = ApiKey::factory()->create([
            'key' => 'valid-key',
            'user_id' => User::factory()->create(['api_active' => true])->id,
        ]);

        $this->withHeaders(['X-Api-Key' => $apiKey->key])
            ->get(route('test-api-access'))
            ->assertStatus(200); // Assuming 200 is a successful response for your route
    }

    public function test_it_allows_access_with_valid_bearer_token_in_authorization_header(): void
    {
        $apiKey = ApiKey::factory()->create([
            'key' => 'valid-key',
            'user_id' => User::factory()->create(['api_active' => true])->id,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer valid-key'])
            ->get(route('test-api-access'))
            ->assertStatus(200);
    }

    public function test_inactive_key_does_not_pass(): void
    {
        $apiKey = ApiKey::factory()->create([
            'key' => 'valid-key',
        ]);

        $this->withHeaders(['Authorization' => 'Bearer valid-key'])
            ->get(route('test-api-access'))
            ->assertStatus(403);
    }

    public function test_user_using_non_master_key_gets_denied_on_master_route(): void
    {
        $apiKey = ApiKey::factory()->create([
            'key' => 'valid-key',
            'user_id' => User::factory()->create(['api_active' => true])->id,
            'is_master' => false,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer valid-key'])
            ->get(route('test-master-api-access'))
            ->assertStatus(403);
    }

    public function test_master_key_works_on_a_master_route(): void
    {
        $apiKey = ApiKey::factory()->create([
            'key' => 'valid-key',
            'user_id' => User::factory()->create(['api_active' => true])->id,
            'is_master' => true,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer valid-key'])
            ->get(route('test-master-api-access'))
            ->assertStatus(200);
    }
}
