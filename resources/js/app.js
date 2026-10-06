import './bootstrap';
import './editor-rico';

/**
 * Alpine.js component – Preenchimento de formulário por voz.
 * Usa a Web Speech API do navegador (Chrome/Edge).
 * Fica gravando em modo contínuo até o usuário dizer "gravar",
 * então envia o texto acumulado para a API e preenche os campos.
 */
window.vozTransacao = function () {
    return {
        gravando:    false,
        processando: false,
        transcricao: '',
        parcial:     '',   // texto interim exibido enquanto grava
        erro:        '',
        recognition: null,

        iniciar() {
            if (this.gravando) {
                this.recognition?.stop();
                return;
            }

            const SpeechRecognition =
                window.SpeechRecognition || window.webkitSpeechRecognition;

            if (!SpeechRecognition) {
                this.erro = 'Seu navegador não suporta reconhecimento de voz. Use Chrome ou Edge.';
                return;
            }

            this.erro        = '';
            this.transcricao = '';
            this.parcial     = '';
            this.recognition = new SpeechRecognition();

            Object.assign(this.recognition, {
                lang:            'pt-BR',
                continuous:      true,   // não para sozinho
                interimResults:  true,   // mostra texto enquanto fala
                maxAlternatives: 1,
            });

            this.recognition.onstart = () => { this.gravando = true; };

            this.recognition.onresult = (event) => {
                let acumulado = '';
                let interino  = '';

                for (let i = 0; i < event.results.length; i++) {
                    const texto = event.results[i][0].transcript;
                    if (event.results[i].isFinal) {
                        acumulado += texto + ' ';
                    } else {
                        interino = texto;
                    }
                }

                this.parcial = acumulado + interino;

                // Detecta a palavra-chave "gravar" no trecho final confirmado
                if (/\bgravar\b/i.test(acumulado)) {
                    this.recognition.stop();
                    // Remove a palavra "gravar" e espaços extras do texto final
                    this.transcricao = acumulado.replace(/\bgravar\b/gi, '').replace(/\s+/g, ' ').trim();
                    this.parcial     = '';
                }
            };

            this.recognition.onerror = (event) => {
                this.gravando = false;
                const msgs = {
                    'not-allowed': 'Permissão de microfone negada. Libere nas configurações do navegador.',
                    'no-speech':   'Nenhuma fala detectada. Tente novamente.',
                    'network':     'Erro de rede ao processar o áudio.',
                };
                this.erro = msgs[event.error] ?? `Erro: ${event.error}`;
            };

            this.recognition.onend = async () => {
                this.gravando = false;
                if (this.transcricao) {
                    await this.enviar();
                }
            };

            this.recognition.start();
        },

        async enviar() {
            this.processando = true;
            this.erro        = '';

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

                const res = await fetch('/voz/transacao', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': csrf ?? '',
                    },
                    body: JSON.stringify({ texto: this.transcricao }),
                });

                const json = await res.json();

                if (!res.ok) {
                    this.erro = json.erro ?? 'Erro ao processar. Tente novamente.';
                    return;
                }

                window.dispatchEvent(new CustomEvent('voice-dados', { detail: json }));

            } catch {
                this.erro = 'Falha na conexão. Verifique sua internet.';
            } finally {
                this.processando = false;
            }
        },
    };
};
