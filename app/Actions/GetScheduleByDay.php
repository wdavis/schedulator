<?php

namespace App\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class GetScheduleByDay
{
    public function execute(array $openings, Collection $bookings, CarbonImmutable $startDate, CarbonImmutable $endDate, ?string $timezone = 'UTC'): array
    {
        // Ensure startDate and endDate are in UTC
        $startDate = $startDate->setTimezone('UTC');
        $endDate = $endDate->setTimezone('UTC');

        $groupedBookings = $this->groupBookingsByDay($bookings, $timezone);
        $groupedOpenings = $this->groupOpeningsByDay($openings, $timezone);

        $combinedSlots = $this->combineSlots($groupedBookings, $groupedOpenings);

        $values = $this->addGaps($combinedSlots, $timezone);

        $finalValues = [];
        for ($date = $startDate; $date->lte($endDate); $date = $date->addDay()) {
            $dateString = $date->setTimezone($timezone)->toDateString();
            if (!isset($values[$dateString])) {
                $values[$dateString] = [
                    'date' => $dateString,
                    'day' => $date->setTimezone($timezone)->format('D'),
                    'slots' => [],
                ];
            }
            $finalValues[] = $values[$dateString];
        }

        // Sort array by date
        usort($finalValues, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        // Format for output
        return $this->formatForOutput($finalValues, $timezone);
    }

    private function groupBookingsByDay(Collection $bookings, string $timezone): array
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

            $slotIndex = $this->findSlotIndex($grouped[$date]['slots'], $start, $end);
            if ($slotIndex === -1) {
                $grouped[$date]['slots'][] = [
                    'type' => 'booking_slot',
                    'start' => $start,
                    'end' => $end,
                    'bookings' => [$booking],
                    'openings' => [],
                ];
            } else {
                $grouped[$date]['slots'][$slotIndex]['bookings'][] = $booking;
            }
        }

        return $grouped;
    }

    private function groupOpeningsByDay(array $openings, string $timezone): array
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

            $slotIndex = $this->findSlotIndex($grouped[$date]['slots'], $start, $end);
            if ($slotIndex === -1) {
                $grouped[$date]['slots'][] = [
                    'type' => 'opening_slot',
                    'start' => $start,
                    'end' => $end,
                    'openings' => [
                        [
                            'type' => 'opening',
                            'start' => $start->toIso8601String(),
                            'end' => $end->toIso8601String(),
                        ]
                    ],
                    'bookings' => [],
                ];
            } else {
                $grouped[$date]['slots'][$slotIndex]['openings'][] = [
                    'type' => 'opening',
                    'start' => $start->toIso8601String(),
                    'end' => $end->toIso8601String(),
                ];
            }
        }

        return $grouped;
    }

    private function combineSlots(array $groupedBookings, array $groupedOpenings): array
    {
        foreach ($groupedOpenings as $date => $openings) {
            if (!isset($groupedBookings[$date])) {
                $groupedBookings[$date] = $openings;
            } else {
                foreach ($openings['slots'] as $openingSlot) {
                    $slotIndex = $this->findSlotIndex($groupedBookings[$date]['slots'], $openingSlot['start'], $openingSlot['end']);
                    if ($slotIndex === -1) {
                        $groupedBookings[$date]['slots'][] = $openingSlot;
                    } else {
                        $groupedBookings[$date]['slots'][$slotIndex]['openings'] = array_merge(
                            $groupedBookings[$date]['slots'][$slotIndex]['openings'],
                            $openingSlot['openings']
                        );
                    }
                }
                usort($groupedBookings[$date]['slots'], function ($a, $b) {
                    return $a['start']->greaterThan($b['start']);
                });
            }
        }

        return $groupedBookings;
    }

    private function addGaps(array $groupedSlots, string $timezone): array
    {
        foreach ($groupedSlots as $date => $day) {
            $slots = $day['slots'];
            $newSlots = [];
            $previousEnd = null;

            foreach ($slots as $slot) {
                $slotStart = $slot['start'];
                if ($previousEnd !== null && $slotStart->greaterThan($previousEnd)) {
                    $newSlots[] = [
                        'type' => 'gap',
                        'start' => $previousEnd->setTimezone($timezone),
                        'end' => $slotStart->setTimezone($timezone),
                        'openings' => [],
                        'bookings' => [],
                    ];
                }

                $newSlots[] = $slot;
                $previousEnd = $slot['end'];
            }

            $groupedSlots[$date]['slots'] = $newSlots;
        }

        return $groupedSlots;
    }

    private function formatForOutput(array $values, string $timezone): array
    {
        foreach ($values as &$dayData) {
            for ($i = 0; $i < count($dayData['slots']); $i++) {
                $slot = &$dayData['slots'][$i];
                $slot['start'] = $slot['start']->setTimezone($timezone)->toIso8601String();
                $slot['end'] = $slot['end']->setTimezone($timezone)->toIso8601String();

                if (!isset($slot['openings'])) {
                    $slot['openings'] = [];
                }
                if (!isset($slot['bookings'])) {
                    $slot['bookings'] = [];
                }
            }
        }

        return $values;
    }

    private function findSlotIndex(array $slots, CarbonImmutable $start, CarbonImmutable $end): int
    {
        foreach ($slots as $index => $slot) {
            if ($slot['start']->eq($start) && $slot['end']->eq($end)) {
                return $index;
            }
        }
        return -1;
    }
}
