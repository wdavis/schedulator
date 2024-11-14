<?php

namespace Tests\Feature\Actions;

use App\Actions\BuildScheduleOverrides;
use App\Enums\ScheduleOverrideType;
use App\Models\ScheduleOverride;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Spatie\Period\Boundaries;
use Spatie\Period\Period;
use Spatie\Period\PeriodCollection;
use Spatie\Period\Precision;

class BuildScheduleOverridesTest extends \Tests\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new BuildScheduleOverrides();
    }

    public function test_returns_empty_period_collections_when_no_overrides()
    {
        $overrides = new Collection();
        $result = $this->action->get($overrides);

        $this->assertArrayHasKey('opening', $result);
        $this->assertArrayHasKey('block', $result);
        $this->assertInstanceOf(PeriodCollection::class, $result['opening']);
        $this->assertInstanceOf(PeriodCollection::class, $result['block']);
        $this->assertTrue($result['opening']->isEmpty());
        $this->assertTrue($result['block']->isEmpty());
    }

    public function test_schedules_opening_override_periods_correctly()
    {
        $overrides = collect([
            ScheduleOverride::factory()->make([
                'type' => ScheduleOverrideType::opening->value,
                'starts_at' => '2024-11-12 09:00:00',
                'ends_at' => '2024-11-12 12:00:00',
            ]),
            ScheduleOverride::factory()->make([
                'type' => ScheduleOverrideType::opening->value,
                'starts_at' => '2024-11-12 13:00:00',
                'ends_at' => '2024-11-12 15:00:00',
            ]),
        ]);

        $result = $this->action->get($overrides);

        $this->assertCount(2, $result['opening']);
        $this->assertTrue($result['opening'][0]->equals(Period::make(
            CarbonImmutable::createFromFormat('Y-m-d H:i:s', '2024-11-12 09:00:00'),
            CarbonImmutable::createFromFormat('Y-m-d H:i:s', '2024-11-12 12:00:00'),
            Precision::MINUTE(),
            Boundaries::EXCLUDE_NONE()
        )));
        $this->assertTrue($result['opening'][1]->equals(Period::make(
            CarbonImmutable::createFromFormat('Y-m-d H:i:s', '2024-11-12 13:00:00'),
            CarbonImmutable::createFromFormat('Y-m-d H:i:s', '2024-11-12 15:00:00'),
            Precision::MINUTE(),
            Boundaries::EXCLUDE_NONE()
        )));
    }

    public function test_schedules_block_override_periods_correctly()
    {
        $overrides = collect([
            ScheduleOverride::factory()->make([
                'type' => ScheduleOverrideType::block->value,
                'starts_at' => '2024-11-12 09:00:00',
                'ends_at' => '2024-11-12 10:00:00',
            ]),
            ScheduleOverride::factory()->make([
                'type' => ScheduleOverrideType::block->value,
                'starts_at' => '2024-11-12 10:30:00',
                'ends_at' => '2024-11-12 11:00:00',
            ]),
        ]);

        $result = $this->action->get($overrides);

        $this->assertCount(2, $result['block']);
        $this->assertTrue($result['block'][0]->equals(Period::make(
            CarbonImmutable::createFromFormat('Y-m-d H:i:s', '2024-11-12 09:00:00'),
            CarbonImmutable::createFromFormat('Y-m-d H:i:s', '2024-11-12 10:00:00'),
            Precision::MINUTE(),
            Boundaries::EXCLUDE_ALL()
        )));
        $this->assertTrue($result['block'][1]->equals(Period::make(
            CarbonImmutable::createFromFormat('Y-m-d H:i:s', '2024-11-12 10:30:00'),
            CarbonImmutable::createFromFormat('Y-m-d H:i:s', '2024-11-12 11:00:00'),
            Precision::MINUTE(),
            Boundaries::EXCLUDE_ALL()
        )));
    }

    public function test_separates_opening_and_block_overrides_correctly()
    {
        $overrides = collect([
            ScheduleOverride::factory()->make([
                'type' => ScheduleOverrideType::opening->value,
                'starts_at' => '2024-11-12 09:00:00',
                'ends_at' => '2024-11-12 12:00:00',
            ]),
            ScheduleOverride::factory()->make([
                'type' => ScheduleOverrideType::block->value,
                'starts_at' => '2024-11-12 09:00:00',
                'ends_at' => '2024-11-12 10:00:00',
            ]),
            ScheduleOverride::factory()->make([
                'type' => ScheduleOverrideType::opening->value,
                'starts_at' => '2024-11-12 10:00:00',
                'ends_at' => '2024-11-12 11:00:00',
            ]),
        ]);

        $result = $this->action->get($overrides);

        $this->assertCount(2, $result['opening']);
        $this->assertCount(1, $result['block']);
    }
}
