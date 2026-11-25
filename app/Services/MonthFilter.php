<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

/**
 * Reads a `?month=YYYY-MM` query value and turns it into a date range with
 * its neighbouring months. A missing or invalid value falls back to the
 * default month, and `wasInvalid` records that so the page can say so.
 */
final readonly class MonthFilter
{
    private const string FORMAT_PATTERN = '/^(\d{4})-(0[1-9]|1[0-2])$/';

    private function __construct(
        public CarbonImmutable $start,
        public string $key,
        public bool $wasInvalid,
    ) {}

    public static function fromRequest(Request $request, ?CarbonInterface $defaultMonth = null, string $key = 'month'): self
    {
        $rawMonth = $request->query($key);
        $defaultStart = CarbonImmutable::instance($defaultMonth ?? CarbonImmutable::today())->startOfMonth()->startOfDay();

        if ($rawMonth === null || $rawMonth === '') {
            return new self($defaultStart, $key, false);
        }

        $parsedStart = is_string($rawMonth) ? self::parse($rawMonth) : null;

        return $parsedStart === null
            ? new self($defaultStart, $key, true)
            : new self($parsedStart, $key, false);
    }

    /**
     * Parses "YYYY-MM" into the first day of that month, or null when invalid.
     */
    public static function parse(string $month): ?CarbonImmutable
    {
        if (preg_match(self::FORMAT_PATTERN, $month, $matches) !== 1) {
            return null;
        }

        return CarbonImmutable::create((int) $matches[1], (int) $matches[2], 1)->startOfDay();
    }

    public function end(): CarbonImmutable
    {
        return $this->start->endOfMonth();
    }

    public function previous(): CarbonImmutable
    {
        return $this->start->subMonthNoOverflow();
    }

    public function next(): CarbonImmutable
    {
        return $this->start->addMonthNoOverflow();
    }

    /**
     * The "YYYY-MM" value of this month, as used in the query string.
     */
    public function value(): string
    {
        return $this->start->format('Y-m');
    }

    public function previousValue(): string
    {
        return $this->previous()->format('Y-m');
    }

    public function nextValue(): string
    {
        return $this->next()->format('Y-m');
    }

    public function label(): string
    {
        return $this->start->format('F Y');
    }

    public function contains(CarbonInterface $date): bool
    {
        return $date->format('Y-m') === $this->value();
    }

    /**
     * Inclusive date range for `whereBetween` on a date column.
     *
     * @return array{0: string, 1: string}
     */
    public function dateRange(): array
    {
        return [$this->start->toDateString(), $this->end()->toDateString()];
    }
}
