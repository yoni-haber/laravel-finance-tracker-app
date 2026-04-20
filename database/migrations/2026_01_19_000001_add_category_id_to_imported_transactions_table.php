<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imported_transactions', function (Blueprint $table) {
            // Proper nullable category assignment; no FK constraint since categories may be
            // deleted before an import is committed, and this is a staging table.
            $table->unsignedBigInteger('category_id')->nullable()->after('external_id');
        });
    }

    public function down(): void
    {
        Schema::table('imported_transactions', function (Blueprint $table) {
            $table->dropColumn('category_id');
        });
    }
};
