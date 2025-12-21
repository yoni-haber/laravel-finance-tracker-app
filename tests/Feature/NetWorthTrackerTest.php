<?php

namespace Tests\Feature;

use App\Livewire\NetWorth\NetWorthTracker;
use App\Models\NetWorthEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NetWorthTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_saves_entry_and_line_items_transactionally(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NetWorthTracker::class)
            ->set('date', '2024-05-10')
            ->set('assetLines', [
                ['category' => 'Cash', 'amount' => '150.00'],
            ])
            ->set('liabilityLines', [
                ['category' => 'Credit Card', 'amount' => '40.00'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $entry = NetWorthEntry::first();

        $this->assertNotNull($entry);
        $this->assertEquals('2024-05-10', $entry->date->toDateString());
        $this->assertSame(150.0, (float) $entry->assets);
        $this->assertSame(40.0, (float) $entry->liabilities);
        $this->assertSame(110.0, (float) $entry->net_worth);

        $this->assertDatabaseHas('net_worth_line_items', [
            'net_worth_entry_id' => $entry->id,
            'type' => 'asset',
            'category' => 'Cash',
            'amount' => 150,
        ]);

        $this->assertDatabaseHas('net_worth_line_items', [
            'net_worth_entry_id' => $entry->id,
            'type' => 'liability',
            'category' => 'Credit Card',
            'amount' => 40,
        ]);
    }
}
