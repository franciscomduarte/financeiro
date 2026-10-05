<?php

namespace Tests;

use App\Models\Clinica;
use App\Support\ClinicaAtual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Clínica ativa padrão dos testes com banco (criada pela migration: "LC Estética"). */
    protected ?Clinica $clinica = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class), true)) {
            $this->clinica = Clinica::where('slug', 'lc-estetica')->firstOrFail();
            app(ClinicaAtual::class)->definir($this->clinica);
        }
    }

    /** Cria outra clínica (para testes de isolamento). */
    protected function novaClinica(string $nome = 'Outra Clínica'): Clinica
    {
        return Clinica::create(['nome' => $nome, 'slug' => \Illuminate\Support\Str::slug($nome) . '-' . \Illuminate\Support\Str::random(4), 'status' => 'ativa']);
    }
}
