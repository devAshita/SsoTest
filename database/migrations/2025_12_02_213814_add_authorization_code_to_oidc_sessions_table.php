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
        Schema::table('oidc_sessions', function (Blueprint $table) {
            $table->string('authorization_code')->nullable()->index()->after('state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oidc_sessions', function (Blueprint $table) {
            $table->dropIndex(['authorization_code']);
            $table->dropColumn('authorization_code');
        });
    }
};
