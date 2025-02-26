<?php

namespace Tests\Feature\Actions\Bookings;

use App\Actions\Bookings\CancelBooking;
use App\Actions\Bookings\CreateBooking;
use App\Actions\Bookings\RescheduleBooking;
use App\Models\Booking;
use App\Models\Environment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RescheduleBookingTest extends TestCase
{
    use RefreshDatabase;

    private $createBookingMock;

    private $cancelBookingMock;

    private $rescheduleBooking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createBookingMock = Mockery::mock(CreateBooking::class);
        $this->cancelBookingMock = Mockery::mock(CancelBooking::class);
        $this->rescheduleBooking = new RescheduleBooking($this->createBookingMock, $this->cancelBookingMock);
    }

    /** @test */
    public function it_reschedules_a_booking_successfully(): void
    {
        $environment = Environment::factory()->create();
        $oldBooking = Booking::factory()->create([
            'starts_at' => now(),
        ]);

        $newBooking = Booking::factory()->make([
            'resource_id' => $oldBooking->resource_id,
            'service_id' => $oldBooking->service_id,
            'starts_at' => now()->addDay(),
        ]);

        $this->createBookingMock
            ->shouldReceive('create')
            ->once()
            ->andReturn($newBooking);

        $this->cancelBookingMock
            ->shouldReceive('cancel')
            ->once()
            ->with(Mockery::type(Booking::class), true);

        $rescheduledBooking = $this->rescheduleBooking->reschedule(
            $oldBooking->id,
            $newBooking->starts_at->toIso8601String(),
            $environment->id,
            null,
            null,
            [],
            true
        );

        $this->assertInstanceOf(Booking::class, $rescheduledBooking);
        $this->assertEquals($newBooking->resource_id, $rescheduledBooking->resource_id);
        $this->assertEquals($newBooking->service_id, $rescheduledBooking->service_id);
        $this->assertEquals($newBooking->starts_at, $rescheduledBooking->starts_at);
    }

    /** @test */
    public function it_rolls_back_if_rescheduling_fails(): void
    {
        $environment = Environment::factory()->create();
        $oldBooking = Booking::factory()->create([
            'starts_at' => now(),
        ]);

        $this->createBookingMock
            ->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Create booking failed'));

        $this->expectException(\Exception::class);

        try {
            $this->rescheduleBooking->reschedule(
                $oldBooking->id,
                now()->addDay()->toIso8601String(),
                $environment->id
            );
        } catch (\Exception $e) {
            $this->assertDatabaseHas('bookings', ['id' => $oldBooking->id, 'cancelled_at' => null]);
            throw $e;
        }
    }

    /** @test */
    public function it_merges_meta_data_correctly_during_rescheduling(): void
    {
        $environment = Environment::factory()->create();
        $oldBooking = Booking::factory()->create([
            'meta' => ['original' => 'value'],
            'starts_at' => now(),
        ]);

        $newMeta = ['new' => 'info'];

        $newBooking = Booking::factory()->make([
            'meta' => array_merge($oldBooking->meta, $newMeta, ['previous_starts_at' => $oldBooking->starts_at->toIso8601String()]),
            'starts_at' => now()->addDay(),
        ]);

        $this->createBookingMock
            ->shouldReceive('create')
            ->once()
            ->andReturn($newBooking);

        $this->cancelBookingMock
            ->shouldReceive('cancel')
            ->once()
            ->with(Mockery::type(Booking::class), false);

        $rescheduledBooking = $this->rescheduleBooking->reschedule(
            $oldBooking->id,
            $newBooking->starts_at->toIso8601String(),
            $environment->id,
            meta: $newMeta
        );

        $this->assertEquals($rescheduledBooking->meta['new'], 'info');
        $this->assertEquals($rescheduledBooking->meta['original'], 'value');
        $this->assertEquals($rescheduledBooking->meta['previous_starts_at'], $oldBooking->starts_at->toIso8601String());
    }

    /** @test */
    public function it_reschedules_with_new_resource_and_service_ids(): void
    {
        $environment = Environment::factory()->create();
        $oldBooking = Booking::factory()->create([
            'starts_at' => now(),
        ]);

        $newBooking = Booking::factory()->make([
            'starts_at' => now()->addDay(),
        ]);

        $this->createBookingMock
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($passedResourceId, $passedServiceId, $passedTimeSlot, $passedEnvironmentId, $passedName, $passedMeta, $passedBypassLeadTime, $passedBypassActive) use ($newBooking, $oldBooking, $environment) {
                $this->assertEquals($newBooking->resource_id, $passedResourceId);
                $this->assertEquals($newBooking->service_id, $passedServiceId);
                $this->assertEquals($newBooking->starts_at->toIso8601String(), $passedTimeSlot);
                $this->assertEquals($environment->id, $passedEnvironmentId);
                $this->assertEquals($oldBooking->name, $passedName);
                // assert array contains key previous_starts_at with oldbooking starts_at
                $this->assertArrayHasKey('previous_starts_at', $passedMeta);
                $this->assertEquals($oldBooking->starts_at->toIso8601String(), $passedMeta['previous_starts_at']);
                $this->assertFalse($passedBypassLeadTime);
                $this->assertFalse($passedBypassActive);

                return true;
            })
            ->andReturn($newBooking);

        $this->cancelBookingMock
            ->shouldReceive('cancel')
            ->once()
            ->with(Mockery::type(Booking::class), false);

        $rescheduledBooking = $this->rescheduleBooking->reschedule(
            bookingId: $oldBooking->id,
            newTimeSlot: $newBooking->starts_at->toIso8601String(),
            environmentId: $environment->id,
            newResourceId: $newBooking->resource_id,
            newServiceId: $newBooking->service_id
        );

        $this->assertEquals($rescheduledBooking->resource_id, $newBooking->resource_id);
        $this->assertEquals($rescheduledBooking->service_id, $newBooking->service_id);
    }
}
