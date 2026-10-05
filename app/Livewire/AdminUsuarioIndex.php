<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CreateUsuarioAction;
use App\Actions\UpdateUsuarioAction;
use App\Enums\RoleUsuario;
use App\Models\Clinica;
use App\Models\User;
use App\Support\ClinicaAtual;
use RuntimeException;
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

    /** Usuários são geridos dentro da clínica ativa: só os vinculados a ela são acessíveis. */
    private function clinica(): Clinica
    {
        return app(ClinicaAtual::class)->get();
    }

    private function usuarioDaClinica(string $id): User
    {
        return $this->clinica()->usuarios()->findOrFail($id);
    }

    public function abrirModalEditar(string $id): void
    {
        $this->resetForm();
        $user = $this->usuarioDaClinica($id);
        $this->usuarioEditandoId = $id;
        $this->nome  = $user->name;
        $this->email = $user->email;
        $this->role  = $user->pivot->papel;
        $this->ativo = $user->active;
        $this->modalUsuario = true;
    }

    public function salvarUsuario(CreateUsuarioAction $criar, UpdateUsuarioAction $atualizar): void
    {
        $this->validate($this->rules());
        try {
            if ($this->usuarioEditandoId) {
                $atualizar->execute($this->usuarioDaClinica($this->usuarioEditandoId), [
                    'name'  => $this->nome,
                    'email' => $this->email,
                ], $this->clinica(), RoleUsuario::from($this->role));
                $this->flashSucesso = 'Usuário atualizado!';
            } else {
                $resultado = $criar->execute([
                    'name'     => $this->nome,
                    'email'    => $this->email,
                    'password' => $this->senha,
                    'active'   => true,
                ], $this->clinica(), RoleUsuario::from($this->role));
                $this->flashSucesso = $resultado['existente']
                    ? 'Este e-mail já tinha conta em outra clínica: o acesso a esta clínica foi liberado (a senha dele não mudou).'
                    : 'Usuário criado!';
            }
            $this->modalUsuario = false;
            $this->resetForm();
        } catch (RuntimeException $e) {
            $this->flashErro = $e->getMessage();
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

    /** Remove o acesso à clínica; a conta só é excluída se não tiver outra clínica. */
    public function deletarUsuario(): void
    {
        try {
            $user = $this->usuarioDaClinica((string) $this->usuarioDeleteId);
            if ($user->id === auth()->id()) {
                $this->flashErro   = 'Você não pode excluir sua própria conta.';
                $this->modalDelete = false;
                return;
            }
            UpdateUsuarioAction::garantirOutroAdmin($this->clinica(), $user);
            $user->clinicas()->detach($this->clinica()->id);

            if (! $user->clinicas()->exists()) {
                $user->delete();
                $this->flashSucesso = 'Usuário excluído.';
            } else {
                $this->flashSucesso = 'Acesso a esta clínica removido (o usuário continua em outras clínicas).';
            }
        } catch (RuntimeException $e) {
            $this->flashErro = $e->getMessage();
        } catch (Throwable $e) {
            $this->flashErro = 'Erro: ' . $e->getMessage();
        }
        $this->modalDelete     = false;
        $this->usuarioDeleteId = null;
    }

    public function enviarResetSenha(string $id): void
    {
        $user   = $this->usuarioDaClinica($id);
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
        $user = $this->usuarioDaClinica($id);
        if ($user->clinicas()->count() > 1) {
            $this->flashErro = 'Este usuário também acessa outras clínicas. Para tirar o acesso só desta, use "Excluir".';
            return;
        }
        if ($user->active) {
            try {
                UpdateUsuarioAction::garantirOutroAdmin($this->clinica(), $user);
            } catch (RuntimeException $e) {
                $this->flashErro = $e->getMessage();
                return;
            }
        }
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
        // Na criação, e-mail já existente significa "dar acesso a esta clínica" (ver CreateUsuarioAction)
        $emailRule = $this->usuarioEditandoId
            ? Rule::unique('users', 'email')->ignore($this->usuarioEditandoId)
            : 'max:255';

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
        $base = fn () => $this->clinica()->usuarios();

        $usuarios = $base()
            ->select(['users.id', 'users.name', 'users.email', 'users.active', 'users.created_at'])
            ->when($this->busca !== '', fn ($q) => $q->where(function ($q2): void {
                $q2->where('users.name', 'ilike', '%' . $this->busca . '%')
                   ->orWhere('users.email', 'ilike', '%' . $this->busca . '%');
            }))
            ->when($this->filtroRole !== '', fn ($q) => $q->wherePivot('papel', $this->filtroRole))
            ->when($this->filtroAtivo !== '', fn ($q) => $q->where('users.active', $this->filtroAtivo === '1'))
            ->reorder('users.name')
            ->paginate(20);

        return view('livewire.admin-usuario-index', [
            'usuarios'      => $usuarios,
            'totalUsuarios' => $base()->count(),
            'totalAdmins'   => $base()->wherePivot('papel', RoleUsuario::Admin->value)->count(),
            'totalInativos' => $base()->where('users.active', false)->count(),
            'roleOpcoes'    => RoleUsuario::cases(),
        ])->layout('layouts.app', ['title' => 'Usuários']);
    }
}
