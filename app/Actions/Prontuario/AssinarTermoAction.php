<?php

declare(strict_types=1);

namespace App\Actions\Prontuario;

use App\Models\Paciente;
use App\Models\ProntuarioModelo;
use App\Models\ProntuarioTermo;
use App\Support\ClinicaAtual;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

/**
 * Guarda o termo assinado na tela: texto final, imagem da assinatura e um hash (sha256) do texto
 * com a assinatura, para provar depois que nada foi alterado.
 */
class AssinarTermoAction
{
    use ResolverAtendimento;

    private const TAMANHO_MAXIMO = 512 * 1024; // assinatura em PNG raramente passa de 50 KB

    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    public function execute(
        Paciente $paciente,
        string $titulo,
        string $conteudo,
        string $assinanteNome,
        string $assinaturaDataUrl,
        ?string $modeloId = null,
        ?string $agendamentoId = null,
    ): ProntuarioTermo {
        $this->clinicaAtual->garantirEscrita();

        $png         = $this->decodificarAssinatura($assinaturaDataUrl);
        $atendimento = $this->atendimentoDoPaciente($paciente, $agendamentoId);
        $modelo      = $modeloId ? ProntuarioModelo::query()->select(['id'])->findOrFail($modeloId) : null;
        $conteudo    = trim($conteudo);
        $path        = $this->clinicaAtual->pasta("prontuario/{$paciente->id}/termos/" . Str::uuid() . '.png');

        Storage::disk(AdicionarFotosProntuarioAction::DISCO)->put($path, $png);

        try {
            return DB::transaction(fn () => ProntuarioTermo::create([
                'paciente_id'     => $paciente->id,
                'modelo_id'       => $modelo?->id,
                'agendamento_id'  => $atendimento?->id,
                'user_id'         => auth()->id(),
                'titulo'          => trim($titulo),
                'conteudo'        => $conteudo,
                'assinante_nome'  => trim($assinanteNome),
                'assinatura_path' => $path,
                'hash'            => hash('sha256', $conteudo . "\n" . trim($assinanteNome) . "\n" . hash('sha256', $png)),
                'assinado_em'     => now(),
                'ip'              => request()?->ip(),
            ]));
        } catch (Throwable $e) {
            Storage::disk(AdicionarFotosProntuarioAction::DISCO)->delete($path);
            throw $e;
        }
    }

    private function decodificarAssinatura(string $dataUrl): string
    {
        if (! str_starts_with($dataUrl, 'data:image/png;base64,')) {
            throw new InvalidArgumentException('Peça para o paciente assinar no quadro antes de salvar.');
        }

        $png = base64_decode(substr($dataUrl, 22), true);

        if ($png === false || strlen($png) > self::TAMANHO_MAXIMO || ! str_starts_with($png, "\x89PNG")) {
            throw new InvalidArgumentException('Não conseguimos ler a assinatura. Limpe o quadro e assine de novo.');
        }

        return $png;
    }
}
