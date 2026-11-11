<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // * Stores bcrypt hash of the numeric OTP code
            $table->string('two_factor_code_hash')->nullable()->after('remember_token');
            // * OTP expiration timestamp
            $table->timestamp('two_factor_expires_at')->nullable()->after('two_factor_code_hash');
            // * When the user last confirmed 2FA successfully
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_expires_at');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_code_hash', 'two_factor_expires_at', 'two_factor_confirmed_at']);
        });
    }
};
























