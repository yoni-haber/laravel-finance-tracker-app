<?php

namespace Tests\Unit;

use App\Models\Transaction;
use App\Models\TransactionException;
use App\Models\User;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionExceptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_exception_casts_date_and_links_transaction(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $transaction = Transaction::factory()->for($user)->for($category)->create([
            'date' => '2024-03-01',
        ]);

        $exception = $transaction->occurrenceExceptions()->create([
            'date' => '2024-03-08',
        ]);

        $this->assertInstanceOf(Carbon::class, $exception->date);
        $this->assertTrue($exception->transaction->is($transaction));
        $this->assertSame('2024-03-08', $exception->date->toDateString());
    }
}
