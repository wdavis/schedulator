<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Environment;
use App\Models\Location;
use App\Models\LocationResource;
use App\Models\Resource;
use App\Models\Schedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BasicSetupSeeder extends Seeder
{
    public function run(): void
    {
        $users = $this->createUsers(2);
        $environments = $this->createEnvironments($users);
        $resources = $this->createResources($environments);
        ray($resources);
        $this->createSchedules($resources, $environments);
    }

    private function createUsers(int $count)
    {
        $users = [];
        for ($i = 1; $i <= $count; $i++) {
            $user = User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => bcrypt('password'),
            ]);

            $randomKey = Str::random(32);

            $apiKey = ApiKey::create([
                'key' => $i === 1 ? 'test-key' : $randomKey, // make sure we have a known key for testing
                'user_id' => $user->id,
            ]);

            $users[] = $user;
        }

        return $users;
    }

    private function createEnvironments($users)
    {
        $environments = [];
        foreach ($users as $user) {
            $prodEnv = Environment::create([
                'name' => 'production',
                'user_id' => $user->id,
            ]);

            $environments[] = $prodEnv;

            $stagingEnv = Environment::create([
                'name' => 'staging',
                'user_id' => $user->id,
            ]);

            $environments[] = $stagingEnv;

            $devEnv = Environment::create([
                'name' => 'dev',
                'user_id' => $user->id,
            ]);

            $environments[] = $devEnv;
        }

        // create default service for each environment
        foreach ($environments as $environment) {
            $service = $environment->services()->create([
                'name' => "Default Service for {$environment->name}",
            ]);

            //            $environment->update([
            //                'default_service_id' => $service->id,
            //            ]);
        }

        return $environments;
    }

    private function createResources($environments)
    {
        $resources = [];
        foreach ($environments as $environment) {
            for ($i = 1; $i <= 2; $i++) {
                $resource = Resource::create([
                    'name' => "Resource {$i} for {$environment->name}",
                    'environment_id' => $environment->id,
                ]);

                $location = Location::create([
                    'name' => "Location {$i} for {$environment->name}",
                    'primary' => $i === 1,
                ]);

                LocationResource::create([
                    'location_id' => $location->id,
                    'resource_id' => $resource->id,
                ]);

                $resources[$environment->id][$i]['resource'] = $resource;
                $resources[$environment->id][$i]['location'] = $location;
            }
        }

        return $resources;
    }

    private function createSchedules($resources, $environments)
    {
        $scheduleData = [
            [
                'start_time' => '09:00:00',
                'end_time' => '12:00:00',
                'days' => [1, 2, 3, 4, 5], // Monday to Friday
            ],
            [
                'start_time' => '14:00:00',
                'end_time' => '18:00:00',
                'days' => [1, 2, 3, 4, 5], // Monday to Friday
            ],
        ];

        foreach ($environments as $environment) {
            foreach ($scheduleData as $data) {
                foreach ($data['days'] as $day) {
                    ray($environment);
                    foreach ($resources[$environment->id] as $resourceLocation) {
                        ray($resourceLocation);
                        Schedule::create([
                            'resource_id' => $resourceLocation['resource']->id,
                            'location_id' => $resourceLocation['location']->id,
                            'start_time' => CarbonImmutable::parse($data['start_time'])->format('H:i:s'),
                            'end_time' => CarbonImmutable::parse($data['end_time'])->format('H:i:s'),
                            'day_of_week' => $day,
                        ]);
                    }
                }
            }
        }
    }
}
