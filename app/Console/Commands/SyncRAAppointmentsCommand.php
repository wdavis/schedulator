<?php

namespace App\Console\Commands;

use App\Actions\Bookings\CreateBooking;
use App\Models\Environment;
use App\Models\Resource;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncRAAppointmentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ra:sync-appointments {environmentId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(CreateBooking $createBooking)
    {
        $environmentId = $this->argument('environmentId');

        $environment = Environment::where('id', $environmentId)
            ->with('services')
            ->firstOrFail();

        $this->info("Syncing appointments for environment: {$environment->name}");

        // limit is 30 per page currently

        $page = 1;
        $hasMorePages = true;

        while ($hasMorePages) {
            $appointments = $this->getPage($page);

            $this->info("Processing page: {$page}");

            foreach ($appointments as $appointment) {

                // lookup the resource by the external/acuity id
                $resource = Resource::where('meta->external_id', $appointment->provider_id)
                    ->with('locations')
                    ->first();

                if (! $resource) {
                    $this->warn("Resource ra_id: {$appointment->provider_id} not found in ssa");

                    continue;
                }

                try {
                    $booking = $createBooking->createBooking(
                        name: '', // this is updated separately in RA app:simple-scheduling-migration
                        resourceId: $resource->id,
                        requestedDate: CarbonImmutable::parse($appointment->starts_at, 'America/Chicago')->setTimezone('UTC'),
                        service: $environment->services->first(),
                        locationId: $resource->locations->first()->id,
                        meta: [
                            'appointment_id' => (string) $appointment->id,
                            'appointment_type_id' => (string) $appointment->appointment_type_id,
                            'patient_id' => (string) $appointment->patient_id,
                            'appointment_method' => (string) $appointment->appointment_method,
                            'provider_id' => (string) $appointment->provider_id,
                        ],
                        cancelled: $appointment->cancelled
                    );

                    $this->info("Created booking {$booking->id}");
                } catch (\Exception $e) {
                    $this->error("Failed to create booking [resource: {$resource->id}]: {$e->getMessage()}");
                }

            }

            // per page is 30. if the total is less than 30, then we are done
            if (count($appointments) < 1000) {
                $hasMorePages = false;
            }

            $page++;
        }

    }

    private function getPage($page)
    {

        $perPage = 1000;

        // get the offset based off the page and perPage to paginate records
        $response = DB::table('appointments')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return $response;
    }
}
