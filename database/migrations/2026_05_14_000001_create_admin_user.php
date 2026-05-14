<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->insertOrIgnore([
            'name'              => 'Admin',
            'email'             => 'admin@lcestetica.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('Orleans@09'),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'admin@lcestetica.com')->delete();
    }
};
