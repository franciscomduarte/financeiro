<?php

declare(strict_types=1);

namespace App\Actions\Atendimento;

use App\Actions\Prontuario\AdicionarFotosProntuarioAction;
use App\Enums\RoleUsuario;
use App\Models\ProntuarioAnexo;
use App\Support\ClinicaAtual;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** PDFs (exames, laudos, receitas) anexados no atendimento; ficam no prontuário do paciente. */
class AnexosAtendimentoAction
{
    use AtendimentoEditavel;

    public const MAX_KB = 20480; // 20 MB

    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    /** @param  array<int, UploadedFile>  $arquivos */
    public function adicionar(string $atendimentoId, array $arquivos, ?string $descricao = null): int
    {
        $atendimento = $this->atendimentoEditavel($atendimentoId);
        $pasta       = $this->clinicaAtual->pasta("prontuario/{$atendimento->paciente_id}/anexos");
        $gravados    = [];

        try {
            DB::transaction(function () use ($arquivos, $atendimento, $descricao, $pasta, &$gravados): void {
                foreach ($arquivos as $arquivo) {
                    // Confere o conteúdo, não só a extensão (o upload do Livewire é movido no storeAs)
                    $mime    = (string) $arquivo->getMimeType();
                    $tamanho = (int) $arquivo->getSize();
                    if ($mime !== 'application/pdf') {
                        throw new RuntimeException("\"{$arquivo->getClientOriginalName()}\" não é um PDF.");
                    }
                    $original = Str::of(pathinfo($arquivo->getClientOriginalName(), PATHINFO_FILENAME))->squish()->limit(140, '')->toString();

                    $path       = $arquivo->storeAs($pasta, Str::uuid() . '.pdf', AdicionarFotosProntuarioAction::DISCO);
                    $gravados[] = $path;

                    ProntuarioAnexo::create([
                        'paciente_id'    => $atendimento->paciente_id,
                        'atendimento_id' => $atendimento->id,
                        'user_id'        => auth()->id(),
                        'nome'           => mb_substr(filled($descricao) && count($arquivos) === 1 ? trim($descricao) : ($original ?: 'Anexo'), 0, 150),
                        'arquivo_path'   => $path,
                        'mime'           => $mime,
                        'tamanho'        => $tamanho,
                    ]);
                }
            });
        } catch (Throwable $e) {
            Storage::disk(AdicionarFotosProntuarioAction::DISCO)->delete($gravados);
            throw $e;
        }

        Log::info('[Atendimento] anexos adicionados', ['atendimento_id' => $atendimento->id, 'quantidade' => count($arquivos), 'user_id' => auth()->id()]);

        return count($arquivos);
    }

    /** Quem abriu o atendimento remove enquanto está em andamento; depois disso, só administradores. */
    public function remover(string $anexoId): void
    {
        $anexo = ProntuarioAnexo::query()->findOrFail($anexoId);
        $admin = auth()->user()?->role === RoleUsuario::Admin;

        if (! $admin) {
            if ($anexo->atendimento_id === null) {
                throw new RuntimeException('Só administradores removem anexos do prontuário.');
            }
            $this->atendimentoEditavel($anexo->atendimento_id); // em andamento e do autor
        }
        $this->clinicaAtual->garantirEscrita();

        $anexo->delete();
        Storage::disk(AdicionarFotosProntuarioAction::DISCO)->delete($anexo->arquivo_path);

        Log::warning('[Prontuário] anexo removido', ['anexo_id' => $anexo->id, 'paciente_id' => $anexo->paciente_id, 'user_id' => auth()->id()]);
    }
}
