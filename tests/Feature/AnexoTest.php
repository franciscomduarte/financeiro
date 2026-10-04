<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FormaPagamento;
use App\Enums\StatusTransacao;
use App\Enums\TipoAnexo;
use App\Enums\TipoTransacao;
use App\Models\Transacao;
use App\Models\TransacaoAnexo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnexoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Transacao $transacao;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->user = User::factory()->create();

        $this->transacao = Transacao::factory()->create([
            'tipo'            => TipoTransacao::Saida,
            'forma_pagamento' => FormaPagamento::Pix,
            'status'          => StatusTransacao::Pendente,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/v1/transacoes/{id}/anexos
    // -------------------------------------------------------------------------

    public function test_upload_boleto_pdf(): void
    {
        $file = UploadedFile::fake()->create('boleto.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/transacoes/{$this->transacao->id}/anexos", [
                'arquivo' => $file,
                'tipo'    => 'boleto',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.tipo', 'boleto')
            ->assertJsonPath('data.nome_arquivo', 'boleto.pdf')
            ->assertJsonPath('suggest_mark_paid', false); // boleto não sugere pago

        $this->assertDatabaseHas('transacao_anexos', [
            'transacao_id' => $this->transacao->id,
            'tipo'         => TipoAnexo::Boleto->value,
        ]);
    }

    public function test_upload_comprovante_sugere_marcar_como_pago(): void
    {
        $file = UploadedFile::fake()->create('comprovante.jpg', 200, 'image/jpeg');

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/transacoes/{$this->transacao->id}/anexos", [
                'arquivo' => $file,
                'tipo'    => 'comprovante',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.tipo', 'comprovante')
            ->assertJsonPath('suggest_mark_paid', true); // transação pendente → sugere pago
    }

    public function test_upload_comprovante_em_transacao_paga_nao_sugere(): void
    {
        $transacaoPaga = Transacao::factory()->create([
            'tipo'            => TipoTransacao::Saida,
            'forma_pagamento' => FormaPagamento::Pix,
            'status'          => StatusTransacao::Pago,
        ]);

        $file = UploadedFile::fake()->create('comprovante.png', 100, 'image/png');

        $this->actingAs($this->user)
            ->postJson("/api/v1/transacoes/{$transacaoPaga->id}/anexos", [
                'arquivo' => $file,
                'tipo'    => 'comprovante',
            ])
            ->assertStatus(201)
            ->assertJsonPath('suggest_mark_paid', false);
    }

    public function test_upload_rejeita_arquivo_maior_que_100mb(): void
    {
        $file = UploadedFile::fake()->create('grande.pdf', 102401, 'application/pdf'); // 100 MB + 1 KB

        $this->actingAs($this->user)
            ->postJson("/api/v1/transacoes/{$this->transacao->id}/anexos", [
                'arquivo' => $file,
                'tipo'    => 'boleto',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['arquivo']);
    }

    public function test_upload_rejeita_tipo_invalido(): void
    {
        $file = UploadedFile::fake()->create('arquivo.exe', 100, 'application/octet-stream');

        $this->actingAs($this->user)
            ->postJson("/api/v1/transacoes/{$this->transacao->id}/anexos", [
                'arquivo' => $file,
                'tipo'    => 'boleto',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['arquivo']);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/transacoes/{id}/anexos
    // -------------------------------------------------------------------------

    public function test_listar_anexos_da_transacao(): void
    {
        TransacaoAnexo::factory()->count(3)->create([
            'transacao_id' => $this->transacao->id,
            'tipo'         => TipoAnexo::Boleto,
        ]);

        $this->actingAs($this->user)
            ->getJson("/api/v1/transacoes/{$this->transacao->id}/anexos")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    // -------------------------------------------------------------------------
    // DELETE /api/v1/transacoes/{id}/anexos/{anexo_id}
    // -------------------------------------------------------------------------

    public function test_remover_anexo(): void
    {
        $caminho = "anexos/transacoes/{$this->transacao->id}/boleto.pdf";
        Storage::disk('local')->put($caminho, 'fake content');

        $anexo = TransacaoAnexo::factory()->create([
            'transacao_id'  => $this->transacao->id,
            'tipo'          => TipoAnexo::Boleto,
            'caminho'       => $caminho,
            'nome_arquivo'  => 'boleto.pdf',
        ]);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/transacoes/{$this->transacao->id}/anexos/{$anexo->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Anexo removido com sucesso.');

        $this->assertDatabaseMissing('transacao_anexos', ['id' => $anexo->id]);
        Storage::disk('local')->assertMissing($caminho);
    }

    public function test_nao_pode_remover_anexo_de_outra_transacao(): void
    {
        $outraTransacao = Transacao::factory()->create([
            'tipo'            => TipoTransacao::Entrada,
            'forma_pagamento' => FormaPagamento::Pix,
            'status'          => StatusTransacao::Pago,
        ]);

        $anexoDeOutra = TransacaoAnexo::factory()->create([
            'transacao_id' => $outraTransacao->id,
            'tipo'         => TipoAnexo::Comprovante,
        ]);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/transacoes/{$this->transacao->id}/anexos/{$anexoDeOutra->id}")
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/anexos/{id}/download
    // -------------------------------------------------------------------------

    public function test_download_anexo_existente(): void
    {
        $caminho = "anexos/transacoes/{$this->transacao->id}/comprovante.pdf";
        Storage::disk('local')->put($caminho, 'fake content');

        $anexo = TransacaoAnexo::factory()->create([
            'transacao_id' => $this->transacao->id,
            'tipo'         => TipoAnexo::Comprovante,
            'caminho'      => $caminho,
            'nome_arquivo' => 'comprovante.pdf',
            'mime_type'    => 'application/pdf',
        ]);

        $this->actingAs($this->user)
            ->get("/api/v1/anexos/{$anexo->id}/download")
            ->assertOk()
            ->assertHeader('Content-Disposition');
    }

    public function test_download_requer_autenticacao(): void
    {
        $anexo = TransacaoAnexo::factory()->create([
            'transacao_id' => $this->transacao->id,
            'tipo'         => TipoAnexo::Boleto,
        ]);

        $this->getJson("/api/v1/anexos/{$anexo->id}/download")->assertStatus(401);
    }
}
