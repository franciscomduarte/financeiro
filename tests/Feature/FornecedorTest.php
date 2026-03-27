<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Fornecedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FornecedorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ─── POST /api/v1/fornecedores ───────────────────────────────

    public function test_criar_fornecedor_com_dados_minimos(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/fornecedores', [
            'nome_fantasia'    => 'Água Premium',
            'servico_prestado' => 'Fornecimento de água mineral',
            'status'           => 'ativo',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.nome_fantasia', 'Água Premium')
            ->assertJsonPath('data.status', 'ativo');

        $this->assertDatabaseHas('fornecedores', [
            'nome_fantasia'    => 'Água Premium',
            'servico_prestado' => 'Fornecimento de água mineral',
            'status'           => 'ativo',
        ]);
    }

    public function test_criar_fornecedor_com_todos_os_campos(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/fornecedores', [
            'nome_fantasia'               => 'Tech Solutions',
            'razao_social'                => 'Tech Solutions Ltda',
            'cnpj'                        => '12.345.678/0001-90',
            'servico_prestado'            => 'Suporte de TI',
            'categoria'                   => 'Tecnologia',
            'contato_nome'                => 'João Silva',
            'contato_telefone'            => '(11) 99999-9999',
            'contato_email'               => 'joao@tech.com',
            'contato_emergencia_nome'     => 'Maria Silva',
            'contato_emergencia_telefone' => '(11) 88888-8888',
            'status'                      => 'ativo',
            'observacoes'                 => 'Parceiro desde 2020',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.nome_fantasia', 'Tech Solutions')
            ->assertJsonPath('data.cnpj', '12.345.678/0001-90')
            ->assertJsonPath('data.categoria', 'Tecnologia')
            ->assertJsonPath('data.contato_email', 'joao@tech.com');
    }

    public function test_criar_fornecedor_falha_sem_nome(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/fornecedores', [
            'servico_prestado' => 'Algum serviço',
            'status'           => 'ativo',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nome_fantasia']);
    }

    public function test_criar_fornecedor_falha_com_email_invalido(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/fornecedores', [
            'nome_fantasia'  => 'Fornecedor X',
            'contato_email'  => 'nao-eh-email',
            'status'         => 'ativo',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['contato_email']);
    }

    public function test_criar_fornecedor_falha_com_status_invalido(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/fornecedores', [
            'nome_fantasia' => 'Fornecedor Y',
            'status'        => 'invalido',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    // ─── GET /api/v1/fornecedores ────────────────────────────────

    public function test_listar_fornecedores_retorna_paginado(): void
    {
        Fornecedor::factory()->count(5)->create(['status' => 'ativo']);
        Fornecedor::factory()->count(2)->create(['status' => 'encerrado']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/fornecedores');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'nome_fantasia', 'status', 'contratos_count']],
                'meta' => ['total', 'per_page', 'current_page'],
            ]);

        $this->assertCount(7, $response->json('data'));
    }

    public function test_listar_fornecedores_filtra_por_status(): void
    {
        Fornecedor::factory()->count(3)->create(['status' => 'ativo']);
        Fornecedor::factory()->count(2)->create(['status' => 'encerrado']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/fornecedores?status=ativo');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
        foreach ($response->json('data') as $item) {
            $this->assertEquals('ativo', $item['status']);
        }
    }

    public function test_listar_fornecedores_filtra_por_categoria(): void
    {
        Fornecedor::factory()->count(2)->create(['categoria' => 'TI']);
        Fornecedor::factory()->count(3)->create(['categoria' => 'Limpeza']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/fornecedores?categoria=TI');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_listar_fornecedores_busca_por_nome(): void
    {
        Fornecedor::factory()->create(['nome_fantasia' => 'Água Premium']);
        Fornecedor::factory()->create(['nome_fantasia' => 'Tech Solutions']);

        $response = $this->actingAs($this->user)->getJson('/api/v1/fornecedores?search=gua');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Água Premium', $response->json('data.0.nome_fantasia'));
    }

    // ─── GET /api/v1/fornecedores/{id} ───────────────────────────

    public function test_mostrar_fornecedor_inclui_count_contratos(): void
    {
        $fornecedor = Fornecedor::factory()->create();

        $response = $this->actingAs($this->user)->getJson("/api/v1/fornecedores/{$fornecedor->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $fornecedor->id)
            ->assertJsonPath('data.contratos_count', 0);
    }

    public function test_mostrar_fornecedor_inexistente_retorna_404(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/fornecedores/nao-existe');

        $response->assertStatus(404);
    }

    // ─── PUT /api/v1/fornecedores/{id} ───────────────────────────

    public function test_atualizar_fornecedor(): void
    {
        $fornecedor = Fornecedor::factory()->create(['status' => 'ativo']);

        $response = $this->actingAs($this->user)->putJson("/api/v1/fornecedores/{$fornecedor->id}", [
            'nome_fantasia' => 'Novo Nome',
            'status'        => 'suspenso',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.nome_fantasia', 'Novo Nome')
            ->assertJsonPath('data.status', 'suspenso');

        $this->assertDatabaseHas('fornecedores', [
            'id'            => $fornecedor->id,
            'nome_fantasia' => 'Novo Nome',
            'status'        => 'suspenso',
        ]);
    }

    // ─── Auth ────────────────────────────────────────────────────

    public function test_rotas_requerem_autenticacao(): void
    {
        $this->getJson('/api/v1/fornecedores')->assertStatus(401);
        $this->postJson('/api/v1/fornecedores', [])->assertStatus(401);
    }
}
