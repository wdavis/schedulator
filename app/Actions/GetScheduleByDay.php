<?php

namespace App\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Spatie\Period\Period;

class GetScheduleByDay
{
    // new format?
    // everything is a slot with an array of multiple items
    // items can be of two types openings or bookings
    // everything else is a gap

    public function execute(array $openings, Collection $bookings, CarbonImmutable $startDate, CarbonImmutable $endDate, ?string $timezone = null): array
    {
        $groupedBookings = $this->groupBookingsByDay($bookings, $timezone);
        $groupedOpenings = $this->groupOpeningsByDay($openings, $timezone);

        $combinedSlots = $this->combineSlots($groupedBookings, $groupedOpenings);

        $values = $this->addGaps($combinedSlots);

        for ($date = $startDate; $date->lte($endDate); $date = $date->addDay()) {
            $dateString = $date->toDateString();
            if (!isset($values[$dateString])) {
                $values[$dateString] = [
                    'date' => $dateString,
                    'day' => $date->format('D'),
                    'slots' => [],
                ];
            }
        }

        // sort array by keys
        ksort($values);

        return $values;
    }

    private function groupBookingsByDay(Collection $bookings, ?string $timezone): array
    {
        $grouped = [];

        foreach ($bookings as $booking) {
            $start = CarbonImmutable::parse($booking['starts_at'])->setTimezone($timezone);
            $end = CarbonImmutable::parse($booking['ends_at'])->setTimezone($timezone);
            $date = $start->toDateString();
            $day = $start->format('D');

            if (!isset($grouped[$date])) {
                $grouped[$date] = [
                    'date' => $date,
                    'day' => $day,
                    'slots' => [],
                ];
            }

            // Check if there is an existing slot with the same start and end times
            $existingSlotIndex = null;
            foreach ($grouped[$date]['slots'] as $index => $slot) {
                if ($slot['type'] === 'booking' && $slot['start']->eq($start) && $slot['end']->eq($end)) {
                    $existingSlotIndex = $index;
                    break;
                }
            }

            if ($existingSlotIndex !== null) {
                // Merge the booking into the existing slot
                $grouped[$date]['slots'][$existingSlotIndex]['bookings'][] = $booking;
            } else {
                // Create a new slot for the booking
                $grouped[$date]['slots'][] = [
                    'type' => 'booking',
                    'start' => $start,
                    'end' => $end,
                    'bookings' => [$booking],
                ];
            }
        }

        return $grouped;
    }

    private function groupOpeningsByDay(array $openings, ?string $timezone): array
    {
        $grouped = [];

        foreach ($openings as $opening) {
            $start = CarbonImmutable::parse($opening['start'])->setTimezone($timezone);
            $end = CarbonImmutable::parse($opening['end'])->setTimezone($timezone);
            $date = $start->toDateString();
            $day = $start->format('D');

            if (!isset($grouped[$date])) {
                $grouped[$date] = [
                    'date' => $date,
                    'day' => $day,
                    'slots' => [],
                ];
            }

            $grouped[$date]['slots'][] = [
                'type' => 'opening',
                'start' => $start,
                'end' => $end,
            ];
        }

        return $grouped;
    }

    private function combineSlots(array $groupedBookings, array $groupedOpenings): array
    {
        foreach ($groupedOpenings as $date => $openings) {
            if (!isset($groupedBookings[$date])) {
                $groupedBookings[$date] = $openings;
            } else {
                $groupedBookings[$date]['slots'] = array_merge($groupedBookings[$date]['slots'], $openings['slots']);
                usort($groupedBookings[$date]['slots'], function ($a, $b) {
                    return $a['start']->greaterThan($b['start']);
                });
            }
        }

        return $groupedBookings;
    }

    private function addGaps(array $groupedSlots): array
    {
        foreach ($groupedSlots as $date => $day) {
            $slots = $day['slots'];
            $newSlots = [];
            $previousEnd = null;

            foreach ($slots as $slot) {
                if ($previousEnd !== null && $slot['start']->greaterThan($previousEnd)) {
                    $newSlots[] = [
                        'type' => 'gap',
                        'start' => $previousEnd,
                        'end' => $slot['start'],
                    ];
                }

                $newSlots[] = $slot;
                $previousEnd = $slot['end'];
            }

            $groupedSlots[$date]['slots'] = $newSlots;
        }

        return $groupedSlots;
    }
}
