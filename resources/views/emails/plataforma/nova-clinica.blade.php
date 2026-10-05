@extends('emails.plataforma.layout')

@section('conteudo')
    <h2>Nova clínica em teste 🎉</h2>
    <table class="dados">
        <tr><td>Clínica</td><td>{{ $clinica->nome }}</td></tr>
        <tr><td>Responsável</td><td>{{ $responsavel->name }}</td></tr>
        <tr><td>E-mail</td><td>{{ $responsavel->email }}</td></tr>
        <tr><td>Celular</td><td>{{ $celular }}</td></tr>
        <tr><td>Teste até</td><td>{{ $clinica->teste_ate->format('d/m/Y') }}</td></tr>
    </table>
    @php($digitos = preg_replace('/\D/', '', $celular))
    <div class="centro">
        <a class="btn" href="https://wa.me/{{ strlen($digitos) <= 11 ? '55' . $digitos : $digitos }}">Falar no WhatsApp</a>
    </div>
@endsection
