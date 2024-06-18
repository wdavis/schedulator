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

        // limit is 30 per page currently

        $page = 1;
        $hasMorePages = true;

        // start pulling appointments from RA limiting to 5 pages
        while ($hasMorePages) {
            $appointments = $this->getPage($page);

//            if($response->status() !== 200) {
//                $this->error("Failed to get appointments from RA: {$response->status()}");
//                return;
//            }

//            if ($response->status() === 200) {
//                $appointments = $response->json()['data'];

                $this->info("Processing page: {$page}");

//                foreach ($appointments['data'] as $appointment) {
                foreach ($appointments as $appointment) {

                    // lookup the resource by the external/acuity id
                    $resource = Resource::where('meta->external_id', $appointment->provider_id)
                        ->with('locations')
                        ->first();

                    if(!$resource) {
                        $this->warn("Resource ra_id: {$appointment->provider_id} not found in ssa");
                        continue;
                    }

                    try {
                        $booking = $createBooking->createBooking(
                            name: '', // todo change
                            resourceId: $resource->id,
                            requestedDate: CarbonImmutable::parse($appointment->starts_at, 'America/Chicago')->setTimezone('UTC'),
                            service: $environment->services->first(),
                            locationId: $resource->locations->first()->id,
                            meta: [
                                'external_id' => $appointment->id,
                                'external_appointment_type_id' => $appointment->appointment_type_id,
                                'external_patient_id' => $appointment->patient_id,
                                'appointment_method' => $appointment->appointment_method,
//                            'external_timezone' => $appointment['timezone,
                            ],
                        );

                        $this->info("Created booking {$booking->id}");
                    } catch (\Exception $e) {
                        $this->error("Failed to create booking [resource: {$resource->id}]: {$e->getMessage()}");
                    }


                    // check if appointment exists
                    // if not create it
                    // if it does update it
                    // if it is cancelled delete it
                }
//            }

            // per page is 30. if the total is less than 30, then we are done
            if (count($appointments) < 1000) {
                $hasMorePages = false;
            }

            $page++;
        }

    }

    private function getPage($page) {
//        $queryString = http_build_query([
//            "include" => "provider,patient",
//            "sort" => "-starts_at",
//            "page[number]" => $page
//        ]);

        $perPage = 1000;

//        $response = \Illuminate\Support\Facades\Http::withHeaders([
//            "X-Api-Key" => env('RA_KEY')
//        ])->withoutVerifying()->get(env('RA_ENDPOINT')."/api/v1/appointments?{$queryString}");

        // get the offset based off the page and perPage to paginate records
        $response = DB::table('appointments')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        return $response;
    }
}
