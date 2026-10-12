<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Campanha;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Imagem da campanha (guardada fora da pasta pública). */
class CampanhaImagemController extends Controller
{
    public function __invoke(string $campanha): StreamedResponse
    {
        $c = Campanha::query()->select(['id', 'imagem_path', 'imagem_mime'])->findOrFail($campanha);
        abort_if($c->imagem_path === null || ! Storage::disk('local')->exists($c->imagem_path), 404);

        return Storage::disk('local')->response($c->imagem_path, null, ['Content-Type' => (string) $c->imagem_mime, 'Cache-Control' => 'private, max-age=3600']);
    }
}
