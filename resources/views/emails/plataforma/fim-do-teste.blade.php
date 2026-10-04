@extends('emails.plataforma.layout')

@section('conteudo')
    @if ($diasParaFim > 0)
        <h2>Seu teste termina {{ $diasParaFim === 1 ? 'amanhã' : "em {$diasParaFim} dias" }}</h2>
        <p>O teste grátis da <strong>{{ $clinica->nome }}</strong> vai até <strong>{{ $clinica->teste_ate->format('d/m/Y') }}</strong>.
            Para continuar usando sem interrupção, fale com a gente e escolha sua assinatura.</p>
    @else
        <h2>Hoje é o último dia do seu teste</h2>
        <p>O teste grátis da <strong>{{ $clinica->nome }}</strong> termina hoje. A partir de amanhã o sistema fica em
            <strong>modo somente leitura</strong>: seus dados continuam lá para consultar, mas não dá para cadastrar ou alterar nada até a assinatura.</p>
    @endif

    @php($whatsapp = \App\Support\Plataforma::whatsappLink(\App\Support\Plataforma::mensagemAssinatura($clinica->nome)))
    <div class="centro">
        @if ($whatsapp)
            <a class="btn" href="{{ $whatsapp }}">Quero assinar</a>
        @else
            <a class="btn" href="{{ route('dashboard') }}">Abrir o sistema</a>
        @endif
    </div>
    @if ($email = \App\Support\Plataforma::email())
        <p class="pequeno" style="text-align:center">Prefere e-mail? Escreva para {{ $email }}.</p>
    @endif
@endsection
