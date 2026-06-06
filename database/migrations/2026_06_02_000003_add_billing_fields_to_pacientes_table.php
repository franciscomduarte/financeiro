<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table): void {
            $table->decimal('valor_mensalidade', 10, 2)->default(0)->after('email');
            $table->string('forma_pagamento', 30)->default('pix')->after('valor_mensalidade');
        });
    }

    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table): void {
            $table->dropColumn(['valor_mensalidade', 'forma_pagamento']);
        });
    }
};
