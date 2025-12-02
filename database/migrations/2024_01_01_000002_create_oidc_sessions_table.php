<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oidc_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->foreignId('client_id')->nullable()->constrained('oauth_clients')->onDelete('cascade');
            $table->text('id_token')->nullable();
            $table->string('nonce')->nullable();
            $table->string('state')->nullable();
            $table->string('code_challenge')->nullable();
            $table->string('code_challenge_method')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oidc_sessions');
    }
};

