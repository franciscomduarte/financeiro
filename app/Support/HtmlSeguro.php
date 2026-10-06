<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Limpa o HTML do editor de texto (fichas do atendimento): mantém só a formatação permitida
 * (negrito, listas, alinhamento, cor...) e descarta scripts, eventos, links e estilos livres.
 */
final class HtmlSeguro
{
    /** Tags permitidas => atributos permitidos */
    private const TAGS = [
        'p' => ['style'], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'h2' => ['style'], 'h3' => ['style'], 'blockquote' => [],
        'mark' => [], 'span' => ['style'],
    ];

    /** Elementos removidos com todo o conteúdo */
    private const DESCARTAR = ['script', 'style', 'iframe', 'object', 'embed', 'template', 'svg', 'math', 'noscript', 'head', 'title'];

    public static function limpar(?string $html, int $limite = 20000): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="raiz">' . mb_substr($html, 0, $limite * 2) . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();

        $raiz = $doc->getElementById('raiz');
        if ($raiz === null) {
            return '';
        }
        self::limparFilhos($raiz);

        $saida = '';
        foreach (iterator_to_array($raiz->childNodes) as $filho) {
            $saida .= $doc->saveHTML($filho);
        }
        $saida = trim($saida);

        // Editor vazio costuma mandar só <p></p>
        return trim(strip_tags($saida)) === '' ? '' : mb_substr($saida, 0, $limite);
    }

    private static function limparFilhos(DOMNode $no): void
    {
        foreach (iterator_to_array($no->childNodes) as $filho) {
            if (! $filho instanceof DOMElement) {
                if ($filho->nodeType === XML_COMMENT_NODE || $filho->nodeType === XML_PI_NODE) {
                    $no->removeChild($filho);
                }
                continue;
            }

            $tag = strtolower($filho->tagName);
            if (in_array($tag, self::DESCARTAR, true)) {
                $no->removeChild($filho);
                continue;
            }

            self::limparFilhos($filho);

            if (! array_key_exists($tag, self::TAGS)) {
                // Tag não permitida (div, a, img...): fica só o conteúdo
                while ($filho->firstChild) {
                    $no->insertBefore($filho->firstChild, $filho);
                }
                $no->removeChild($filho);
                continue;
            }

            foreach (iterator_to_array($filho->attributes) as $atributo) {
                $nome = strtolower($atributo->nodeName);
                if (! in_array($nome, self::TAGS[$tag], true)) {
                    $filho->removeAttribute($atributo->nodeName);
                } elseif ($nome === 'style') {
                    $estilo = self::estilo($tag, $atributo->nodeValue ?? '');
                    $estilo === '' ? $filho->removeAttribute('style') : $filho->setAttribute('style', $estilo);
                }
            }
        }
    }

    /** Só text-align (parágrafos/títulos) e color (span), com valores conferidos. */
    private static function estilo(string $tag, string $estilo): string
    {
        $permitidos = [];
        foreach (explode(';', $estilo) as $regra) {
            [$prop, $valor] = array_pad(array_map('trim', explode(':', $regra, 2)), 2, '');
            $prop  = strtolower($prop);
            $valor = strtolower($valor);

            if ($prop === 'text-align' && $tag !== 'span' && in_array($valor, ['left', 'center', 'right', 'justify'], true)) {
                $permitidos[] = "text-align: {$valor}";
            }
            if ($prop === 'color' && $tag === 'span' && preg_match('/^(#[0-9a-f]{3,8}|rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(,\s*[\d.]+\s*)?\))$/', $valor)) {
                $permitidos[] = "color: {$valor}";
            }
        }

        return implode('; ', $permitidos);
    }
}
