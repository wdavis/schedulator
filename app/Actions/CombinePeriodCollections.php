<?php

namespace App\Actions;

use Illuminate\Support\Collection;
use Spatie\Period\PeriodCollection;

class CombinePeriodCollections
{
    public function combine(Collection $periodCollections, ?string $key = null): PeriodCollection
    {
        // Initialize a new, empty PeriodCollection.
        $combinedCollection = new PeriodCollection;

        // Iterate over the array of PeriodCollections.
        foreach ($periodCollections as $collection) {
            // Each collection is itself an array of Period instances,
            // so iterate over those as well.
            if (! $key) {
                foreach ($collection as $period) {
                    // Add the current period to the combined collection.
                    $combinedCollection = $combinedCollection->add($period);
                }

                continue;
            }

            foreach ($collection[$key] as $period) {
                // Add the current period to the combined collection.
                $combinedCollection = $combinedCollection->add($period);
            }
        }

        // The combined collection might contain overlapping periods.
        // We can solve this by calling the overlapAll method,
        // which will combine all overlapping periods into single ones.
        return $combinedCollection->union();
    }
}
