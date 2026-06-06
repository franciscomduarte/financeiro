<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobrancas', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->string('asaas_id')->unique()->comment('ID do pagamento no Asaas');
            $table->decimal('valor', 10, 2);
            $table->date('vencimento');
            $table->string('mes_referencia', 7)->comment('Formato: 2024-01');
            $table->enum('status', ['PENDING', 'RECEIVED', 'OVERDUE', 'DELETED', 'REFUNDED'])->default('PENDING');
            $table->text('qr_code_texto')->nullable()->comment('Pix copia e cola');
            $table->string('link_fatura')->nullable();
            $table->timestamp('whatsapp_enviado_em')->nullable();
            $table->timestamp('email_enviado_em')->nullable();
            $table->timestamp('pago_em')->nullable();
            $table->timestamps();

            $table->unique(['paciente_id', 'mes_referencia'], 'unique_paciente_mes');
            $table->index(['status', 'vencimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cobrancas');
    }
};
