<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Models\Clinica;
use App\Support\ClinicaAtual;
use App\Support\Plataforma;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/** Faixas do topo do sistema: teste grátis (dias restantes / encerrado) e e-mail a confirmar. */
class AvisosClinica extends Component
{
    public ?Clinica $clinica;
    public bool $somenteLeitura;
    public ?int $diasRestantes;
    public bool $mostrarContagem;
    public bool $emailPendente;
    public ?string $whatsapp;
    public ?string $email;

    public function __construct(ClinicaAtual $clinicaAtual)
    {
        $this->clinica = $clinicaAtual->get();
        $user          = auth()->user();
        $emTeste       = (bool) $this->clinica?->emTeste();

        $this->somenteLeitura  = $clinicaAtual->somenteLeitura();
        $this->diasRestantes   = $this->clinica?->diasRestantesTeste();
        // Contagem regressiva só para quem decide a assinatura (admins da clínica)
        $this->mostrarContagem = $emTeste && ! $this->somenteLeitura && (bool) $user?->isAdmin();
        $this->emailPendente   = $emTeste && $user !== null && ! $user->hasVerifiedEmail();
        $this->whatsapp        = Plataforma::whatsappLink(Plataforma::mensagemAssinatura($this->clinica?->nome));
        $this->email           = Plataforma::email();
    }

    public function shouldRender(): bool
    {
        return $this->somenteLeitura || $this->mostrarContagem || $this->emailPendente;
    }

    public function render(): View
    {
        return view('components.avisos-clinica');
    }
}
