<?php

declare(strict_types=1);

namespace Tests\Unit\Livewire\Concerns;

use App\Livewire\Concerns\InteractsWithSelectedPeriod;
use App\Models\User;
use App\Support\SelectedPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Iterator;
use Livewire\Attributes\On;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionAttribute;
use ReflectionMethod;
use Tests\TestCase;

final class InteractsWithSelectedPeriodTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_selected_period_is_available_to_consuming_components(): void
    {
        $reflectionMethod = new ReflectionMethod(InteractsWithSelectedPeriod::class, 'selectedPeriod');

        $this->assertTrue($reflectionMethod->isProtected());
    }

    public function test_mount_initialises_the_period_from_the_current_month_for_guests(): void
    {
        Carbon::setTestNow('2024-05-15');

        $selectedPeriodConcernHarness = new SelectedPeriodConcernHarness();

        $selectedPeriodConcernHarness->mountInteractsWithSelectedPeriod();

        $this->assertSame(5, $selectedPeriodConcernHarness->periodMonth);
        $this->assertSame(2024, $selectedPeriodConcernHarness->periodYear);
    }

    public function test_mount_initialises_the_period_from_the_authenticated_user(): void
    {
        $user = User::factory()->create(['selected_month' => 3, 'selected_year' => 2023]);

        $this->actingAs($user);
        $selectedPeriodConcernHarness = new SelectedPeriodConcernHarness();

        $selectedPeriodConcernHarness->mountInteractsWithSelectedPeriod();

        $this->assertSame(3, $selectedPeriodConcernHarness->periodMonth);
        $this->assertSame(2023, $selectedPeriodConcernHarness->periodYear);
    }

    public function test_mount_falls_back_to_the_current_month_when_the_user_has_no_complete_period(): void
    {
        Carbon::setTestNow('2024-09-15');
        $user = User::factory()->create(['selected_month' => 3, 'selected_year' => null]);

        $this->actingAs($user);
        $selectedPeriodConcernHarness = new SelectedPeriodConcernHarness();

        $selectedPeriodConcernHarness->mountInteractsWithSelectedPeriod();

        $this->assertSame(9, $selectedPeriodConcernHarness->periodMonth);
        $this->assertSame(2024, $selectedPeriodConcernHarness->periodYear);
    }

    public function test_update_selected_period_listens_for_the_period_changed_event(): void
    {
        $reflectionMethod = new ReflectionMethod(InteractsWithSelectedPeriod::class, 'updateSelectedPeriod');

        $attributes = $reflectionMethod->getAttributes(On::class);

        $this->assertCount(1, $attributes);
        $this->assertSame(['period-changed'], $this->attributeArguments($attributes[0]));
    }

    /**
     * @return Iterator<string, array{int, int, int, int}>
     */
    public static function selectedPeriodUpdateProvider(): Iterator
    {
        yield 'in range values pass through' => [8, 2026, 8, 2026];

        yield 'month below range is clamped' => [0, 2026, 1, 2026];

        yield 'month above range is clamped' => [13, 2026, 12, 2026];

        yield 'year below range is clamped' => [4, 1999, 4, SelectedPeriod::MIN_YEAR];

        yield 'year above range is clamped' => [4, 2101, 4, SelectedPeriod::MAX_YEAR];
    }

    #[DataProvider('selectedPeriodUpdateProvider')]
    public function test_update_selected_period_stores_a_clamped_period(
        int $month,
        int $year,
        int $expectedMonth,
        int $expectedYear,
    ): void {
        $selectedPeriodConcernHarness = new SelectedPeriodConcernHarness();

        $selectedPeriodConcernHarness->updateSelectedPeriod($month, $year);

        $this->assertSame($expectedMonth, $selectedPeriodConcernHarness->periodMonth);
        $this->assertSame($expectedYear, $selectedPeriodConcernHarness->periodYear);
    }

    public function test_selected_period_returns_the_assigned_period_as_a_value_object(): void
    {
        $selectedPeriodConcernHarness = new SelectedPeriodConcernHarness();
        $selectedPeriodConcernHarness->periodMonth = 11;
        $selectedPeriodConcernHarness->periodYear = 2025;

        $selectedPeriod = $selectedPeriodConcernHarness->exposedSelectedPeriod();

        $this->assertSame(11, $selectedPeriod->month);
        $this->assertSame(2025, $selectedPeriod->year);
    }

    public function test_selected_period_clamps_assigned_values(): void
    {
        $selectedPeriodConcernHarness = new SelectedPeriodConcernHarness();
        $selectedPeriodConcernHarness->periodMonth = 99;
        $selectedPeriodConcernHarness->periodYear = 9999;

        $selectedPeriod = $selectedPeriodConcernHarness->exposedSelectedPeriod();

        $this->assertSame(12, $selectedPeriod->month);
        $this->assertSame(SelectedPeriod::MAX_YEAR, $selectedPeriod->year);
    }

    public function test_selected_period_falls_back_to_the_current_user_period_before_mount(): void
    {
        $user = User::factory()->create(['selected_month' => 6, 'selected_year' => 2022]);

        $this->actingAs($user);
        $selectedPeriodConcernHarness = new SelectedPeriodConcernHarness();

        $selectedPeriod = $selectedPeriodConcernHarness->exposedSelectedPeriod();

        $this->assertSame(6, $selectedPeriod->month);
        $this->assertSame(2022, $selectedPeriod->year);
    }

    public function test_selected_period_falls_back_to_the_current_month_before_mount_for_guests(): void
    {
        Carbon::setTestNow('2024-12-15');
        $selectedPeriodConcernHarness = new SelectedPeriodConcernHarness();

        $selectedPeriod = $selectedPeriodConcernHarness->exposedSelectedPeriod();

        $this->assertSame(12, $selectedPeriod->month);
        $this->assertSame(2024, $selectedPeriod->year);
    }

    /**
     * @param ReflectionAttribute<On> $reflectionAttribute
     * @return array<int, mixed>
     */
    private function attributeArguments(ReflectionAttribute $reflectionAttribute): array
    {
        return array_values($reflectionAttribute->getArguments());
    }
}

final class SelectedPeriodConcernHarness
{
    use InteractsWithSelectedPeriod;

    public function exposedSelectedPeriod(): SelectedPeriod
    {
        return $this->selectedPeriod();
    }
}
