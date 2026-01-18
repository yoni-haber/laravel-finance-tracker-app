<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bank_statement_imports', function (Blueprint $table) {
            // Composite index for common query patterns
            $table->index(['user_id', 'status', 'created_at'], 'bank_imports_user_status_created_idx');
        });

        Schema::table('imported_transactions', function (Blueprint $table) {
            // Composite index for committable transactions query
            $table->index(['import_id', 'is_duplicate', 'is_committed'], 'imported_txn_commit_filter_idx');
            // Index for hash-based duplicate detection
            $table->index(['hash', 'is_duplicate'], 'imported_txn_hash_duplicate_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_statement_imports', function (Blueprint $table) {
            $table->dropIndex('bank_imports_user_status_created_idx');
        });

        Schema::table('imported_transactions', function (Blueprint $table) {
            $table->dropIndex('imported_txn_commit_filter_idx');
            $table->dropIndex('imported_txn_hash_duplicate_idx');
        });
    }
};
