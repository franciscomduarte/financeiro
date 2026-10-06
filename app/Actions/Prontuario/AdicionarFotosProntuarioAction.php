<?php

declare(strict_types=1);

namespace App\Actions\Prontuario;

use App\Enums\MomentoFoto;
use App\Models\Paciente;
use App\Models\ProntuarioFoto;
use App\Support\ClinicaAtual;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Guarda fotos no prontuário (disco privado, pasta da clínica) com uma miniatura para a galeria.
 * Se algo falhar, nenhum arquivo fica para trás.
 */
class AdicionarFotosProntuarioAction
{
    use ResolverAtendimento;

    public const DISCO = 'local';
    private const LARGURA_MINIATURA = 480;

    public function __construct(private readonly ClinicaAtual $clinicaAtual) {}

    /**
     * @param  array<int, UploadedFile>  $arquivos
     * @return array<int, ProntuarioFoto>
     */
    public function execute(
        Paciente $paciente,
        array $arquivos,
        MomentoFoto $momento,
        string $tiradaEm,
        ?string $regiao = null,
        ?string $descricao = null,
        ?string $agendamentoId = null,
        ?string $atendimentoId = null,
    ): array {
        $this->clinicaAtual->garantirEscrita();
        $atendimento = $this->atendimentoDoPaciente($paciente, $agendamentoId);
        if ($atendimentoId !== null && ! \App\Models\Atendimento::query()->whereKey($atendimentoId)->where('paciente_id', $paciente->id)->exists()) {
            throw new \InvalidArgumentException('Esse atendimento não é deste paciente.');
        }
        $pasta       = $this->clinicaAtual->pasta("prontuario/{$paciente->id}/fotos");
        $gravados    = [];

        try {
            return DB::transaction(function () use ($arquivos, $paciente, $momento, $tiradaEm, $regiao, $descricao, $atendimento, $atendimentoId, $pasta, &$gravados): array {
                $fotos = [];
                foreach ($arquivos as $arquivo) {
                    // Tudo que lê o arquivo vem antes do storeAs: o upload do Livewire é movido (não copiado)
                    $nome     = (string) Str::uuid();
                    $mime     = (string) $arquivo->getMimeType();
                    $tamanho  = (int) $arquivo->getSize();
                    $extensao = $arquivo->extension() ?: 'jpg';

                    $miniatura = $this->miniatura($arquivo, "{$pasta}/{$nome}-mini.jpg");
                    if ($miniatura !== null) {
                        $gravados[] = $miniatura;
                    }

                    $path       = $arquivo->storeAs($pasta, "{$nome}.{$extensao}", self::DISCO);
                    $gravados[] = $path;

                    $fotos[] = ProntuarioFoto::create([
                        'paciente_id'    => $paciente->id,
                        'agendamento_id' => $atendimento?->id,
                        'atendimento_id' => $atendimentoId,
                        'user_id'        => auth()->id(),
                        'momento'        => $momento,
                        'regiao'         => $regiao !== '' ? $regiao : null,
                        'descricao'      => $descricao !== '' ? $descricao : null,
                        'tirada_em'      => $tiradaEm,
                        'arquivo_path'   => $path,
                        'miniatura_path' => $miniatura,
                        'mime'           => $mime,
                        'tamanho'        => $tamanho,
                    ]);
                }

                return $fotos;
            });
        } catch (Throwable $e) {
            Storage::disk(self::DISCO)->delete($gravados);
            throw $e;
        }
    }

    /** Miniatura JPEG (GD). Sem GD ou com formato não suportado, a galeria usa a foto original. */
    private function miniatura(UploadedFile $arquivo, string $destino): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        try {
            $original = @imagecreatefromstring((string) file_get_contents($arquivo->getRealPath()));
            if ($original === false) {
                return null;
            }

            $original = $this->corrigirOrientacao($original, $arquivo);
            $mini     = imagescale($original, min(self::LARGURA_MINIATURA, imagesx($original)));

            ob_start();
            imagejpeg($mini, null, 80);
            $conteudo = (string) ob_get_clean();

            Storage::disk(self::DISCO)->put($destino, $conteudo);

            return $destino;
        } catch (Throwable $e) {
            Log::warning('Prontuário: não foi possível gerar a miniatura', ['erro' => $e->getMessage()]);

            return null;
        }
    }

    /** Fotos de celular vêm "deitadas" com a rotação no EXIF. */
    private function corrigirOrientacao(\GdImage $imagem, UploadedFile $arquivo): \GdImage
    {
        if (! function_exists('exif_read_data') || $arquivo->getMimeType() !== 'image/jpeg') {
            return $imagem;
        }

        $orientacao = (int) (@exif_read_data($arquivo->getRealPath())['Orientation'] ?? 1);

        return match ($orientacao) {
            3       => imagerotate($imagem, 180, 0) ?: $imagem,
            6       => imagerotate($imagem, -90, 0) ?: $imagem,
            8       => imagerotate($imagem, 90, 0) ?: $imagem,
            default => $imagem,
        };
    }
}
