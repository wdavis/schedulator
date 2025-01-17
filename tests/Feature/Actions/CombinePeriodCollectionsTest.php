<?php

namespace Tests\Feature\Actions;

use App\Actions\CombinePeriodCollections;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;
use Tests\TestCase;

class CombinePeriodCollectionsTest extends TestCase
{
    protected CombinePeriodCollections $combinePeriodCollections;

    protected function setUp(): void
    {
        parent::setUp();
        $this->combinePeriodCollections = new CombinePeriodCollections;
    }

    public function test_combines_period_collections_without_key()
    {
        // Arrange
        $period1 = new Period(
            CarbonImmutable::parse('2023-07-01 12:00'),
            CarbonImmutable::parse('2023-07-10 12:00'),
            Precision::MINUTE(),
            Boundaries::EXCLUDE_NONE()
        );
        $period2 = new Period(
            CarbonImmutable::parse('2023-07-05 12:00'),
            CarbonImmutable::parse('2023-07-15 12:00'),
            Precision::MINUTE(),
            Boundaries::EXCLUDE_NONE()
        );
        $period3 = new Period(
            CarbonImmutable::parse('2023-07-20 12:00'),
            CarbonImmutable::parse('2023-07-30 12:00'),
            Precision::MINUTE(),
            Boundaries::EXCLUDE_NONE()
        );

        $periodCollection1 = new PeriodCollection($period1, $period2);
        $periodCollection2 = new PeriodCollection($period3);
        $periodCollections = new Collection([$periodCollection1, $periodCollection2]);

        // Act
        $combinedCollection = $this->combinePeriodCollections->combine($periodCollections);

        // Assert
        $this->assertInstanceOf(PeriodCollection::class, $combinedCollection);
        $this->assertCount(2, $combinedCollection);

        // Verify that periods are merged and maintained as expected
        $this->assertEquals('2023-07-01', $combinedCollection[0]->start()->format('Y-m-d'));
        $this->assertEquals('2023-07-15', $combinedCollection[0]->end()->format('Y-m-d'));
        $this->assertEquals('2023-07-20', $combinedCollection[1]->start()->format('Y-m-d'));
        $this->assertEquals('2023-07-30', $combinedCollection[1]->end()->format('Y-m-d'));
    }

    public function test_combines_period_collections_with_key()
    {
        // Arrange
        $period1 = new Period(
            CarbonImmutable::parse('2023-07-01 12:00'),
            CarbonImmutable::parse('2023-07-10 12:00'),
            Precision::MINUTE(),
            Boundaries::EXCLUDE_NONE()
        );
        $period2 = new Period(
            CarbonImmutable::parse('2023-07-05 12:00'),
            CarbonImmutable::parse('2023-07-15 12:00'),
            Precision::MINUTE(),
            Boundaries::EXCLUDE_NONE()
        );

        $collection1 = ['availability' => new PeriodCollection($period1)];
        $collection2 = ['availability' => new PeriodCollection($period2)];
        $periodCollections = new Collection([$collection1, $collection2]);

        // Act
        $combinedCollection = $this->combinePeriodCollections->combine($periodCollections, 'availability');

        // Assert
        $this->assertInstanceOf(PeriodCollection::class, $combinedCollection);
        $this->assertCount(1, $combinedCollection);

        // Verify merged period
        $this->assertEquals('2023-07-01', $combinedCollection[0]->start()->format('Y-m-d'));
        $this->assertEquals('2023-07-15', $combinedCollection[0]->end()->format('Y-m-d'));
    }

    public function test_empty_period_collections_returns_empty_collection()
    {
        // Arrange
        $periodCollections = new Collection;

        // Act
        $combinedCollection = $this->combinePeriodCollections->combine($periodCollections);

        // Assert
        $this->assertInstanceOf(PeriodCollection::class, $combinedCollection);
        $this->assertCount(0, $combinedCollection);
    }

    public function test_non_overlapping_periods_with_key()
    {
        // Arrange
        $period1 = new Period(
            CarbonImmutable::parse('2023-07-01 12:00'),
            CarbonImmutable::parse('2023-07-03 12:00'),
            Precision::MINUTE(),
            Boundaries::EXCLUDE_NONE()
        );
        $period2 = new Period(
            CarbonImmutable::parse('2023-07-05 12:00'),
            CarbonImmutable::parse('2023-07-07 12:00'),
            Precision::MINUTE(),
            Boundaries::EXCLUDE_NONE()
        );

        $collection1 = ['customKey' => new PeriodCollection($period1)];
        $collection2 = ['customKey' => new PeriodCollection($period2)];
        $periodCollections = new Collection([$collection1, $collection2]);

        // Act
        $combinedCollection = $this->combinePeriodCollections->combine($periodCollections, 'customKey');

        // Assert
        $this->assertInstanceOf(PeriodCollection::class, $combinedCollection);
        $this->assertCount(2, $combinedCollection);

        // Verify non-overlapping periods remain separate
        $this->assertEquals('2023-07-01', $combinedCollection[0]->start()->format('Y-m-d'));
        $this->assertEquals('2023-07-03', $combinedCollection[0]->end()->format('Y-m-d'));
        $this->assertEquals('2023-07-05', $combinedCollection[1]->start()->format('Y-m-d'));
        $this->assertEquals('2023-07-07', $combinedCollection[1]->end()->format('Y-m-d'));
    }
}
