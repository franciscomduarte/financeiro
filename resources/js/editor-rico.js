// O Tiptap é carregado sob demanda (só na tela do atendimento), em um arquivo separado
const carregarTiptap = () => import('./tiptap.js');

/**
 * Editor de texto das fichas do atendimento (Alpine). Salva sozinho, ~1s depois de parar de digitar,
 * chamando salvar(html). O HTML é limpo de novo no servidor (App\Support\HtmlSeguro).
 * O Editor fica fora do estado reativo do Alpine (proxy quebra o Tiptap).
 */
window.editorRico = function ({ valor = '', editavel = true, rotulo = '', salvar = () => {} }) {
    let editor = null;
    let timer = null;
    let pendente = null;

    const enviar = () => {
        if (pendente === null) return;
        const html = pendente;
        pendente = null;
        salvar(html);
    };

    return {
        versao: 0,      // muda a cada transação: atualiza o estado ativo dos botões
        salvando: false,

        async init() {
            const { Editor, StarterKit, TextAlign, Highlight, TextStyle, Color } = await carregarTiptap();
            editor = new Editor({
                element: this.$refs.area,
                editable: editavel,
                content: valor || '',
                extensions: [
                    StarterKit.configure({ heading: { levels: [2, 3] }, link: false, code: false, codeBlock: false, horizontalRule: false }),
                    TextAlign.configure({ types: ['heading', 'paragraph'] }),
                    Highlight,
                    TextStyle,
                    Color,
                ],
                editorProps: { attributes: { class: 'editor-rico-conteudo', role: 'textbox', 'aria-multiline': 'true', 'aria-label': rotulo } },
                onTransaction: () => { this.versao++; },
                onUpdate: ({ editor: e }) => {
                    pendente = e.isEmpty ? '' : e.getHTML();
                    this.salvando = true;
                    clearTimeout(timer);
                    timer = setTimeout(() => { enviar(); this.salvando = false; }, 1000);
                },
                onBlur: () => { clearTimeout(timer); enviar(); this.salvando = false; },
            });
        },

        destroy() {
            clearTimeout(timer);
            enviar();
            editor?.destroy();
        },

        ativo(nome, atributos = {}) {
            this.versao; // dependência reativa
            return editor ? editor.isActive(nome, atributos) : false;
        },

        alinhado(lado) {
            this.versao;
            return editor ? editor.isActive({ textAlign: lado }) : false;
        },

        cmd(acao, valorCmd) {
            if (!editor) return;
            const c = editor.chain().focus();
            const acoes = {
                negrito: () => c.toggleBold(),
                italico: () => c.toggleItalic(),
                sublinhado: () => c.toggleUnderline(),
                riscado: () => c.toggleStrike(),
                cor: () => c.setColor(valorCmd),
                marcador: () => c.toggleHighlight(),
                alinhar: () => c.setTextAlign(valorCmd),
                lista: () => c.toggleBulletList(),
                numerada: () => c.toggleOrderedList(),
                limpar: () => c.unsetAllMarks().clearNodes(),
                desfazer: () => c.undo(),
                refazer: () => c.redo(),
            };
            (acoes[acao] ?? (() => c))().run();
        },
    };
};
