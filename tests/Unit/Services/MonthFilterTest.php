<?php

namespace Tests\Unit\Services;

use App\Services\MonthFilter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MonthFilterTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidMonths(): array
    {
        return [
            'month 13' => ['2026-13'],
            'month 00' => ['2026-00'],
            'single digit month' => ['2026-9'],
            'full date' => ['2026-09-01'],
            'text' => ['september'],
            'sql fragment' => ["2026-09' OR 1=1"],
        ];
    }

    #[DataProvider('invalidMonths')]
    public function test_parse_rejects_values_that_are_not_year_and_month(string $month): void
    {
        $this->assertNull(MonthFilter::parse($month));
    }

    public function test_previous_and_next_cross_the_year_boundary(): void
    {
        $filter = MonthFilter::fromRequest(Request::create('/', 'GET', ['month' => '2026-12']));

        $this->assertSame('2026-11', $filter->previousValue());
        $this->assertSame('2027-01', $filter->nextValue());
        $this->assertSame(['2026-12-01', '2026-12-31'], $filter->dateRange());
    }

    public function test_date_range_covers_a_leap_february(): void
    {
        $filter = MonthFilter::fromRequest(Request::create('/', 'GET', ['month' => '2028-02']));

        $this->assertSame(['2028-02-01', '2028-02-29'], $filter->dateRange());
        $this->assertSame('2028-03', $filter->nextValue());
    }

    public function test_missing_month_uses_the_default_month_without_overflowing(): void
    {
        $filter = MonthFilter::fromRequest(Request::create('/'), CarbonImmutable::parse('2026-01-31'));

        $this->assertFalse($filter->wasInvalid);
        $this->assertSame('2026-01', $filter->value());
        $this->assertSame('2026-02', $filter->nextValue());
    }

    public function test_array_month_falls_back_to_the_default_and_is_marked_invalid(): void
    {
        $filter = MonthFilter::fromRequest(Request::create('/', 'GET', ['month' => ['2026-05']]), CarbonImmutable::parse('2026-09-10'));

        $this->assertTrue($filter->wasInvalid);
        $this->assertSame('2026-09', $filter->value());
    }
}
