<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CreateUsuarioAction;
use App\Actions\UpdateUsuarioAction;
use App\Enums\RoleUsuario;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class AdminUsuarioIndex extends Component
{
    use WithPagination;

    public string $busca       = '';
    public string $filtroRole  = '';
    public string $filtroAtivo = '';

    public bool    $modalUsuario = false;
    public bool    $modalDelete  = false;
    public ?string $usuarioEditandoId = null;
    public ?string $usuarioDeleteId   = null;

    public string $nome  = '';
    public string $email = '';
    public string $senha = '';
    public string $role  = 'user';
    public bool   $ativo = true;

    public ?string $flashSucesso = null;
    public ?string $flashErro    = null;

    protected function queryString(): array
    {
        return [
            'busca'       => ['except' => '', 'as' => 'q'],
            'filtroRole'  => ['except' => '', 'as' => 'role'],
            'filtroAtivo' => ['except' => '', 'as' => 'ativo'],
        ];
    }

    public function updatingBusca(): void       { $this->resetPage(); }
    public function updatingFiltroRole(): void  { $this->resetPage(); }
    public function updatingFiltroAtivo(): void { $this->resetPage(); }

    public function abrirModalNovo(): void
    {
        $this->resetForm();
        $this->modalUsuario = true;
    }

    public function abrirModalEditar(string $id): void
    {
        $this->resetForm();
        $user = User::findOrFail($id);
        $this->usuarioEditandoId = $id;
        $this->nome  = $user->name;
        $this->email = $user->email;
        $this->role  = $user->role->value;
        $this->ativo = $user->active;
        $this->modalUsuario = true;
    }

    public function salvarUsuario(CreateUsuarioAction $criar, UpdateUsuarioAction $atualizar): void
    {
        $this->validate($this->rules());
        try {
            if ($this->usuarioEditandoId) {
                $atualizar->execute(User::findOrFail($this->usuarioEditandoId), [
                    'name'   => $this->nome,
                    'email'  => $this->email,
                    'role'   => $this->role,
                    'active' => $this->ativo,
                ]);
                $this->flashSucesso = 'Usuário atualizado!';
            } else {
                $criar->execute([
                    'name'     => $this->nome,
                    'email'    => $this->email,
                    'password' => $this->senha,
                    'role'     => $this->role,
                    'active'   => true,
                ]);
                $this->flashSucesso = 'Usuário criado!';
            }
            $this->modalUsuario = false;
            $this->resetForm();
        } catch (Throwable $e) {
            $this->flashErro = 'Erro: ' . $e->getMessage();
        }
    }

    public function confirmarDeletar(string $id): void
    {
        if ($id === (string) auth()->id()) {
            $this->flashErro = 'Você não pode excluir sua própria conta.';
            return;
        }
        $this->usuarioDeleteId = $id;
        $this->modalDelete     = true;
    }

    public function deletarUsuario(): void
    {
        try {
            $user = User::findOrFail($this->usuarioDeleteId);
            if ($user->id === auth()->id()) {
                $this->flashErro   = 'Você não pode excluir sua própria conta.';
                $this->modalDelete = false;
                return;
            }
            $user->delete();
            $this->flashSucesso = 'Usuário excluído.';
        } catch (Throwable $e) {
            $this->flashErro = 'Erro: ' . $e->getMessage();
        }
        $this->modalDelete     = false;
        $this->usuarioDeleteId = null;
    }

    public function enviarResetSenha(string $id): void
    {
        $user   = User::findOrFail($id);
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->flashSucesso = 'E-mail de redefinição enviado para ' . $user->email . '.';
        } else {
            $this->flashErro = 'Não foi possível enviar o e-mail de redefinição.';
        }
    }

    public function toggleAtivo(string $id): void
    {
        if ($id === (string) auth()->id()) {
            $this->flashErro = 'Você não pode desativar sua própria conta.';
            return;
        }
        $user       = User::findOrFail($id);
        $novoEstado = ! $user->active;
        $user->update(['active' => $novoEstado]);
        $this->flashSucesso = $novoEstado ? 'Usuário reativado.' : 'Usuário desativado.';
    }

    public function fecharModais(): void
    {
        $this->modalUsuario    = false;
        $this->modalDelete     = false;
        $this->usuarioDeleteId = null;
        $this->flashErro       = null;
        $this->resetForm();
    }

    private function rules(): array
    {
        $emailRule = $this->usuarioEditandoId
            ? Rule::unique('users', 'email')->ignore($this->usuarioEditandoId)
            : Rule::unique('users', 'email');

        $rules = [
            'nome'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', $emailRule],
            'role'  => ['required', Rule::in(['admin', 'user'])],
            'ativo' => ['boolean'],
        ];

        if (! $this->usuarioEditandoId) {
            $rules['senha'] = ['required', 'string', 'min:8'];
        }

        return $rules;
    }

    private function resetForm(): void
    {
        $this->usuarioEditandoId = null;
        $this->nome              = '';
        $this->email             = '';
        $this->senha             = '';
        $this->role              = 'user';
        $this->ativo             = true;
    }

    public function render(): View
    {
        $usuarios = User::query()
            ->when($this->busca !== '', fn ($q) => $q->where(function ($q2): void {
                $q2->where('name', 'ilike', '%' . $this->busca . '%')
                   ->orWhere('email', 'ilike', '%' . $this->busca . '%');
            }))
            ->when($this->filtroRole !== '', fn ($q) => $q->where('role', $this->filtroRole))
            ->when($this->filtroAtivo !== '', fn ($q) => $q->where('active', $this->filtroAtivo === '1'))
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.admin-usuario-index', [
            'usuarios'      => $usuarios,
            'totalUsuarios' => User::count(),
            'totalAdmins'   => User::where('role', 'admin')->count(),
            'totalInativos' => User::where('active', false)->count(),
            'roleOpcoes'    => RoleUsuario::cases(),
        ])->layout('layouts.app', ['title' => 'Usuários']);
    }
}
