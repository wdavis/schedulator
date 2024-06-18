<?php

namespace Tests\Feature\Actions;

use App\Actions\CombinePeriodCollections;
use Illuminate\Support\Collection;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Tests\TestCase;
use Carbon\CarbonImmutable;
use Spatie\Period\Precision;
use Spatie\Period\Boundaries;

class CombinePeriodCollectionsTest extends TestCase
{
    /**
     * @var CombinePeriodCollections
     */
    protected $combine_period_collections;

    protected function setUp(): void
    {
        parent::setUp();

        $this->combine_period_collections = new CombinePeriodCollections();
    }

    public function test_combine(): void
    {
        // Arrange
        $period1 = new Period(
            CarbonImmutable::createFromFormat('Y-m-d H:i', '2023-07-01 12:00'),
            CarbonImmutable::createFromFormat('Y-m-d H:i', '2023-07-10 12:00'),
            Precision::MINUTE(),
            Boundaries::EXCLUDE_NONE()
        );
        $period2 = new Period(
            CarbonImmutable::createFromFormat('Y-m-d H:i', '2023-07-05 12:00'),
            CarbonImmutable::createFromFormat('Y-m-d H:i', '2023-07-15 12:00'),
            Precision::MINUTE(),
            Boundaries::EXCLUDE_NONE()
        );
        $period3 = new Period(
            CarbonImmutable::createFromFormat('Y-m-d H:i', '2023-07-20 12:00'),
            CarbonImmutable::createFromFormat('Y-m-d H:i', '2023-07-30 12:00'),
            Precision::MINUTE(),
            Boundaries::EXCLUDE_NONE()
        );

        $periodCollection1 = new PeriodCollection($period1, $period2);
        $periodCollection2 = new PeriodCollection($period3);

        $periodCollections = new Collection([$periodCollection1, $periodCollection2]);

        // Act
        $combinedCollection = $this->combine_period_collections->combine($periodCollections);

        // Assert
        $this->assertInstanceOf(PeriodCollection::class, $combinedCollection);
        $this->assertCount(2, $combinedCollection);

        // The first combined period should be the union of period1 and period2
        $this->assertEquals(
            '2023-07-01',
            $combinedCollection[0]->start()->format('Y-m-d')
        );
        $this->assertEquals(
            '2023-07-15',
            $combinedCollection[0]->end()->format('Y-m-d')
        );

        // The second combined period should be the same as period3
        $this->assertEquals(
            '2023-07-20',
            $combinedCollection[1]->start()->format('Y-m-d')
        );
        $this->assertEquals(
            '2023-07-30',
            $combinedCollection[1]->end()->format('Y-m-d')
        );
    }
}
