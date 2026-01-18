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
        Schema::create('imported_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('bank_statement_imports')->onDelete('cascade');
            $table->date('date');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->string('external_id')->nullable();
            $table->string('hash');
            $table->boolean('is_duplicate')->default(false);
            $table->boolean('is_committed')->default(false);
            $table->timestamps();

            $table->index('import_id');
            $table->index('hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imported_transactions');
    }
};
