<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetGroup;
use App\Models\Liability;
use App\Models\LiabilityGroup;
use App\Models\NetWorthSnapshot;
use App\Models\NetWorthSnapshotItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetWorthSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_net_worth_is_calculated_from_items(): void
    {
        $user = User::factory()->create();
        $assetGroup = AssetGroup::factory()->create(['user_id' => $user->id]);
        $liabilityGroup = LiabilityGroup::factory()->create(['user_id' => $user->id]);
        $asset = Asset::factory()->create(['user_id' => $user->id, 'asset_group_id' => $assetGroup->id]);
        $liability = Liability::factory()->create(['user_id' => $user->id, 'liability_group_id' => $liabilityGroup->id]);

        $snapshot = NetWorthSnapshot::create([
            'user_id' => $user->id,
            'snapshot_date' => '2024-01-01',
        ]);

        NetWorthSnapshotItem::create([
            'net_worth_snapshot_id' => $snapshot->id,
            'item_type' => NetWorthSnapshotItem::TYPE_ASSET,
            'item_id' => $asset->id,
            'value' => 1000,
        ]);

        NetWorthSnapshotItem::create([
            'net_worth_snapshot_id' => $snapshot->id,
            'item_type' => NetWorthSnapshotItem::TYPE_LIABILITY,
            'item_id' => $liability->id,
            'value' => 200,
        ]);

        $this->assertSame(1000.0, $snapshot->totalAssets());
        $this->assertSame(200.0, $snapshot->totalLiabilities());
        $this->assertSame(800.0, $snapshot->netWorth());
    }
}
