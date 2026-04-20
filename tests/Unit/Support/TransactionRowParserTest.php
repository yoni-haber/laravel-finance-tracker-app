<?php

namespace Tests\Unit\Support;

use App\Models\BankProfile;
use App\Support\BankStatement\TransactionRowParser;
use Tests\TestCase;

class TransactionRowParserTest extends TestCase
{
    private function makeProfile(string $statementType, array $columns, ?string $dateFormat = 'Y-m-d'): BankProfile
    {
        $config = ['columns' => $columns];
        if ($dateFormat !== null) {
            $config['date_format'] = $dateFormat;
        }

        return new BankProfile(['statement_type' => $statementType, 'config' => $config]);
    }

    private function bankProfile(array $columns, ?string $dateFormat = 'Y-m-d'): BankProfile
    {
        return $this->makeProfile('bank', $columns, $dateFormat);
    }

    private function creditCardProfile(array $columns, ?string $dateFormat = 'Y-m-d'): BankProfile
    {
        return $this->makeProfile('credit_card', $columns, $dateFormat);
    }

    // ─── parseRow ────────────────────────────────────────────────────────────

    public function test_parse_row_returns_valid_array_for_complete_row(): void
    {
        $profile = $this->bankProfile(['date' => 0, 'description' => 1, 'amount' => 2]);
        $parser = new TransactionRowParser($profile);

        $result = $parser->parseRow(['2024-01-15', 'Coffee Shop', '12.50']);

        $this->assertNotNull($result);
        $this->assertEquals('2024-01-15', $result['date']->toDateString());
        $this->assertEquals('COFFEE SHOP', $result['description']);
        $this->assertEquals(12.50, $result['amount']);
        $this->assertNull($result['external_id']);
    }

    public function test_parse_row_returns_null_when_date_column_not_configured(): void
    {
        $profile = $this->bankProfile(['description' => 1, 'amount' => 2]);
        $parser = new TransactionRowParser($profile);

        $result = $parser->parseRow(['2024-01-15', 'Coffee Shop', '12.50']);

        $this->assertNull($result);
    }

    public function test_parse_row_returns_null_when_date_value_is_empty(): void
    {
        $profile = $this->bankProfile(['date' => 0, 'description' => 1, 'amount' => 2]);
        $parser = new TransactionRowParser($profile);

        $result = $parser->parseRow(['', 'Coffee Shop', '12.50']);

        $this->assertNull($result);
    }

    public function test_parse_row_returns_null_when_description_is_empty(): void
    {
        $profile = $this->bankProfile(['date' => 0, 'description' => 1, 'amount' => 2]);
        $parser = new TransactionRowParser($profile);

        $result = $parser->parseRow(['2024-01-15', '', '12.50']);

        $this->assertNull($result);
    }

    public function test_parse_row_returns_null_when_no_amount_columns_configured(): void
    {
        $profile = $this->bankProfile(['date' => 0, 'description' => 1]);
        $parser = new TransactionRowParser($profile);

        $result = $parser->parseRow(['2024-01-15', 'Coffee Shop', '12.50']);

        $this->assertNull($result);
    }

    public function test_parse_row_flips_sign_for_credit_card_statement(): void
    {
        $profile = $this->creditCardProfile(['date' => 0, 'description' => 1, 'amount' => 2]);
        $parser = new TransactionRowParser($profile);

        $result = $parser->parseRow(['2024-01-15', 'Coffee Shop', '12.50']);

        $this->assertNotNull($result);
        $this->assertEquals(-12.50, $result['amount']);
    }

    public function test_parse_row_does_not_flip_sign_for_bank_statement(): void
    {
        $profile = $this->bankProfile(['date' => 0, 'description' => 1, 'amount' => 2]);
        $parser = new TransactionRowParser($profile);

        $result = $parser->parseRow(['2024-01-15', 'Coffee Shop', '12.50']);

        $this->assertNotNull($result);
        $this->assertEquals(12.50, $result['amount']);
    }

    public function test_parse_row_profile_date_format_takes_priority(): void
    {
        // 'd.m.Y' is not in the global SUPPORTED_DATE_FORMATS list
        $profile = $this->bankProfile(['date' => 0, 'description' => 1, 'amount' => 2], 'd.m.Y');
        $parser = new TransactionRowParser($profile);

        $result = $parser->parseRow(['15.01.2024', 'Coffee Shop', '12.50']);

        $this->assertNotNull($result);
        $this->assertEquals('2024-01-15', $result['date']->toDateString());
    }

    public function test_parse_row_falls_back_to_global_date_formats(): void
    {
        // Profile format 'd.m.Y' does not match; 'Y-m-d' in global formats should match
        $profile = $this->bankProfile(['date' => 0, 'description' => 1, 'amount' => 2], 'd.m.Y');
        $parser = new TransactionRowParser($profile);

        $result = $parser->parseRow(['2024-01-15', 'Coffee Shop', '12.50']);

        $this->assertNotNull($result);
        $this->assertEquals('2024-01-15', $result['date']->toDateString());
    }

    public function test_parse_row_normalises_description_to_uppercase_and_squishes_whitespace(): void
    {
        $profile = $this->bankProfile(['date' => 0, 'description' => 1, 'amount' => 2]);
        $parser = new TransactionRowParser($profile);

        $result = $parser->parseRow(['2024-01-15', '  coffee  shop  ', '12.50']);

        $this->assertNotNull($result);
        $this->assertEquals('COFFEE SHOP', $result['description']);
    }

    public function test_parse_row_strips_currency_symbols_from_amount(): void
    {
        $profile = $this->bankProfile(['date' => 0, 'description' => 1, 'amount' => 2]);
        $parser = new TransactionRowParser($profile);

        foreach (['$12.50', '£12.50', '€12.50', '¥12.50'] as $rawAmount) {
            $result = $parser->parseRow(['2024-01-15', 'Coffee Shop', $rawAmount]);
            $this->assertNotNull($result, "Expected non-null result for amount: {$rawAmount}");
            $this->assertEquals(12.50, $result['amount'], "Expected 12.50 for amount: {$rawAmount}");
        }
    }

    public function test_parse_row_converts_parentheses_to_negative_amount(): void
    {
        $profile = $this->bankProfile(['date' => 0, 'description' => 1, 'amount' => 2]);
        $parser = new TransactionRowParser($profile);

        $result = $parser->parseRow(['2024-01-15', 'Coffee Shop', '(50.00)']);

        $this->assertNotNull($result);
        $this->assertEquals(-50.0, $result['amount']);
    }

    public function test_parse_row_returns_null_for_non_numeric_amount(): void
    {
        $profile = $this->bankProfile(['date' => 0, 'description' => 1, 'amount' => 2]);
        $parser = new TransactionRowParser($profile);

        $result = $parser->parseRow(['2024-01-15', 'Coffee Shop', 'N/A']);

        $this->assertNull($result);
    }

    public function test_parse_row_debit_only_row_produces_negative_amount(): void
    {
        $profile = $this->bankProfile(['date' => 0, 'description' => 1, 'debit' => 2, 'credit' => 3]);
        $parser = new TransactionRowParser($profile);

        $result = $parser->parseRow(['2024-01-15', 'Coffee Shop', '100.00', '']);

        $this->assertNotNull($result);
        $this->assertEquals(-100.0, $result['amount']);
    }

    public function test_parse_row_credit_only_row_produces_positive_amount(): void
    {
        $profile = $this->bankProfile(['date' => 0, 'description' => 1, 'debit' => 2, 'credit' => 3]);
        $parser = new TransactionRowParser($profile);

        $result = $parser->parseRow(['2024-01-15', 'Coffee Shop', '', '50.00']);

        $this->assertNotNull($result);
        $this->assertEquals(50.0, $result['amount']);
    }

    public function test_parse_row_only_debit_column_configured_uses_zero_for_credit(): void
    {
        $profile = $this->bankProfile(['date' => 0, 'description' => 1, 'debit' => 2]);
        $parser = new TransactionRowParser($profile);

        $result = $parser->parseRow(['2024-01-15', 'Coffee Shop', '100.00']);

        $this->assertNotNull($result);
        $this->assertEquals(-100.0, $result['amount']);
    }

    public function test_parse_row_only_credit_column_configured_uses_zero_for_debit(): void
    {
        $profile = $this->bankProfile(['date' => 0, 'description' => 1, 'credit' => 2]);
        $parser = new TransactionRowParser($profile);

        $result = $parser->parseRow(['2024-01-15', 'Coffee Shop', '50.00']);

        $this->assertNotNull($result);
        $this->assertEquals(50.0, $result['amount']);
    }
}
