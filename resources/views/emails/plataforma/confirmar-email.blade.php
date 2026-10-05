@extends('emails.plataforma.layout')

@section('conteudo')
    <h2>Olá, {{ \Illuminate\Support\Str::of($nome)->before(' ') }}!</h2>
    <p>Falta só confirmar seu e-mail. Assim garantimos que os avisos e a recuperação de senha chegam até você.</p>
    <div class="centro"><a class="btn" href="{{ $link }}">Confirmar meu e-mail</a></div>
    <p class="pequeno">O link vale por {{ \App\Services\VerificacaoEmailService::VALIDADE_DIAS }} dias. Se não foi você quem criou a conta, ignore esta mensagem.</p>
    <p class="pequeno">Se o botão não abrir, copie e cole no navegador:<br>{{ $link }}</p>
@endsection
