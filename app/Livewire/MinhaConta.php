<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Throwable;

class MinhaConta extends Component
{
    public string $nome  = '';
    public string $email = '';

    public string $senhaAtual     = '';
    public string $novaSenha      = '';
    public string $confirmarSenha = '';

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    public function mount(): void
    {
        $user        = auth()->user();
        $this->nome  = $user->name;
        $this->email = $user->email;
    }

    public function salvarPerfil(): void
    {
        $user = auth()->user();
        $this->validate([
            'nome'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
        ]);
        try {
            $user->update(['name' => $this->nome, 'email' => $this->email]);
            $this->flashSucesso = 'Dados atualizados com sucesso!';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao salvar: ' . $e->getMessage();
        }
    }

    public function salvarSenha(): void
    {
        $this->validate([
            'senhaAtual'     => ['required', 'string'],
            'novaSenha'      => ['required', 'string', 'min:8', 'same:confirmarSenha'],
            'confirmarSenha' => ['required', 'string'],
        ]);

        $user = auth()->user();

        if (! Hash::check($this->senhaAtual, $user->password)) {
            $this->addError('senhaAtual', 'Senha atual incorreta.');
            return;
        }

        try {
            $user->update(['password' => $this->novaSenha]);
            $this->senhaAtual     = '';
            $this->novaSenha      = '';
            $this->confirmarSenha = '';
            $this->flashSucesso   = 'Senha alterada com sucesso!';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro ao alterar senha: ' . $e->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.minha-conta')
            ->layout('layouts.app', ['title' => 'Minha Conta']);
    }
}
