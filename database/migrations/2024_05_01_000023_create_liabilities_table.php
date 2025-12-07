<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('liability_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('notes')->nullable();
            $table->decimal('interest_rate', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'liability_group_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liabilities');
    }
};
