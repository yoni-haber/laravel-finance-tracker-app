<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Asset;
use App\Models\AssetGroup;
use App\Models\Category;
use App\Models\Liability;
use App\Models\LiabilityGroup;
use App\Models\NetWorthSnapshot;
use App\Models\NetWorthSnapshotItem;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $categoryNames = ['Salary', 'Rent', 'Groceries', 'Utilities', 'Transport', 'Leisure'];

        $categories = collect($categoryNames)->map(function (string $name) use ($user) {
            return Category::factory()->for($user)->create([
                'name' => $name,
            ]);
        });

        $current = Carbon::now();

        foreach ($categories as $category) {
            Budget::factory()->for($user)->for($category)->create([
                'month' => $current->month,
                'year' => $current->year,
                'amount' => match ($category->name) {
                    'Rent' => 1200,
                    'Groceries' => 350,
                    'Utilities' => 200,
                    'Transport' => 150,
                    'Leisure' => 250,
                    default => 0,
                },
            ]);
        }

        Transaction::factory()
            ->for($user)
            ->for($categories->firstWhere('name', 'Salary'))
            ->create([
                'type' => 'income',
                'amount' => 3200,
                'date' => $current->copy()->startOfMonth()->addDay(),
                'is_recurring' => true,
                'frequency' => 'monthly',
                'description' => 'Monthly salary',
            ]);

        Transaction::factory()
            ->for($user)
            ->for($categories->firstWhere('name', 'Rent'))
            ->create([
                'type' => 'expense',
                'amount' => 1200,
                'date' => $current->copy()->startOfMonth()->addDays(2),
                'is_recurring' => true,
                'frequency' => 'monthly',
                'description' => 'Flat rent',
            ]);

        Transaction::factory()
            ->for($user)
            ->for($categories->firstWhere('name', 'Groceries'))
            ->count(6)
            ->sequence(
                ['amount' => 70, 'date' => $current->copy()->subDays(10)],
                ['amount' => 55, 'date' => $current->copy()->subDays(3)],
                ['amount' => 68, 'date' => $current->copy()->addDays(2)],
                ['amount' => 72, 'date' => $current->copy()->addDays(9)],
                ['amount' => 60, 'date' => $current->copy()->addDays(15)],
                ['amount' => 80, 'date' => $current->copy()->addDays(22)],
            )
            ->create([
                'type' => 'expense',
                'description' => 'Groceries',
            ]);

        Transaction::factory()
            ->for($user)
            ->for($categories->firstWhere('name', 'Transport'))
            ->recurring('weekly')
            ->create([
                'type' => 'expense',
                'amount' => 35,
                'date' => $current->copy()->startOfMonth(),
                'description' => 'Travel card',
            ]);

        $assetGroups = [
            'Cash & Bank' => ['Current Account', 'Savings Account'],
            'Investments' => ['Index Fund', 'Shares ISA'],
        ];

        collect($assetGroups)->each(function (array $assets, string $groupName) use ($user): void {
            $group = AssetGroup::create([
                'user_id' => $user->id,
                'name' => $groupName,
            ]);

            collect($assets)->each(fn (string $name) => Asset::create([
                'user_id' => $user->id,
                'asset_group_id' => $group->id,
                'name' => $name,
            ]));
        });

        $liabilityGroups = [
            'Credit Cards' => ['Visa Card'],
            'Loans' => ['Student Loan'],
        ];

        collect($liabilityGroups)->each(function (array $liabilities, string $groupName) use ($user): void {
            $group = LiabilityGroup::create([
                'user_id' => $user->id,
                'name' => $groupName,
            ]);

            collect($liabilities)->each(fn (string $name) => Liability::create([
                'user_id' => $user->id,
                'liability_group_id' => $group->id,
                'name' => $name,
            ]));
        });

        $snapshot = NetWorthSnapshot::create([
            'user_id' => $user->id,
            'snapshot_date' => now()->subDays(5),
            'notes' => 'Initial snapshot',
        ]);

        foreach ($user->assets as $asset) {
            NetWorthSnapshotItem::create([
                'net_worth_snapshot_id' => $snapshot->id,
                'item_type' => NetWorthSnapshotItem::TYPE_ASSET,
                'item_id' => $asset->id,
                'value' => match ($asset->name) {
                    'Current Account' => 950,
                    'Savings Account' => 2500,
                    'Index Fund' => 4200,
                    default => 1200,
                },
            ]);
        }

        foreach ($user->liabilities as $liability) {
            NetWorthSnapshotItem::create([
                'net_worth_snapshot_id' => $snapshot->id,
                'item_type' => NetWorthSnapshotItem::TYPE_LIABILITY,
                'item_id' => $liability->id,
                'value' => match ($liability->name) {
                    'Visa Card' => 450,
                    default => 2200,
                },
            ]);
        }
    }
}
