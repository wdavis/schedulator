<?php

namespace App\Traits;

trait GradesColors
{
    protected function gradeColors(array $input, string $startColor, string $endColor): array
    {
        $maxCount = 0;

        foreach ($input as $date => $dayData) {
            $dailyCount = array_sum(array_map(function ($hourData) {
                return $hourData['count'];
            }, $dayData['slotsByHour']));

            if ($dailyCount > $maxCount) {
                $maxCount = $dailyCount;
            }
        }

        $startColor = '#FF0000'; // red
        $endColor = '#00FF00'; // green

        foreach ($input as $date => $dayData) {
            $dailyCount = array_sum(array_map(function ($hourData) {
                return $hourData['count'];
            }, $dayData['slotsByHour']));

            // Normalize the count relative to the maximum
            $normalizedCount = $maxCount ? ($dailyCount / $maxCount) : 0;

            // Interpolate the color based on the normalized count
            $color = $this->interpolateColor($startColor, $endColor, $normalizedCount);

            // Update the color using the full key path
            $input[$date]['color'] = $color;
        }

        // handle the slots by hour
        foreach ($input as $date => $dayData) {
            // Calculate the maxCount for slotsByHour specific to this day
            $hourlyMaxCount = 0;
            foreach ($dayData['slotsByHour'] as $hour => $hourData) {
                if ($hourData['count'] > $hourlyMaxCount) {
                    $hourlyMaxCount = $hourData['count'];
                }
            }

            foreach ($dayData['slotsByHour'] as $hour => $hourData) {
                // Normalize the count relative to the hourly maxCount for the hourly color
                $normalizedHourlyCount = $hourlyMaxCount ? ($hourData['count'] / $hourlyMaxCount) : 0;

                // Interpolate the color based on the normalized count for the hourly color
                $input[$date]['slotsByHour'][$hour]['color'] = $this->interpolateColor($startColor, $endColor, $normalizedHourlyCount);
            }
        }


//        foreach ($input as $date => &$dayData) {
//            $dailyCount = array_sum(array_map(function ($hourData) {
//                return $hourData['count'];
//            }, $dayData['slotsByHour']));
//
//            // Normalize the count relative to the maximum
//            $normalizedCount = $maxCount ? ($dailyCount / $maxCount) : 0;
//
//            // Interpolate the color based on the normalized count
//            $dayData['color'] = $this->interpolateColor($startColor, $endColor, $normalizedCount);
//
//            // You can apply similar logic to the hourly slots if needed
//        }

        return $input;
    }



    protected function interpolateColor($color1, $color2, $factor): string
    {
        $r1 = hexdec(substr($color1, 1, 2));
        $g1 = hexdec(substr($color1, 3, 2));
        $b1 = hexdec(substr($color1, 5, 2));

        $r2 = hexdec(substr($color2, 1, 2));
        $g2 = hexdec(substr($color2, 3, 2));
        $b2 = hexdec(substr($color2, 5, 2));

        $r = $r1 + ($r2 - $r1) * $factor;
        $g = $g1 + ($g2 - $g1) * $factor;
        $b = $b1 + ($b2 - $b1) * $factor;

        return sprintf("#%02x%02x%02x", round($r), round($g), round($b));
    }
}
