<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('net_worth_snapshot_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('net_worth_snapshot_id')->constrained()->cascadeOnDelete();
            $table->string('item_type');
            $table->unsignedBigInteger('item_id');
            $table->decimal('value', 18, 2);
            $table->timestamps();
            $table->index(['item_type', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('net_worth_snapshot_items');
    }
};
