# Documento de Requisitos — Sistema de Gestão LC Estética e Saúde Integrativa

**Versão:** 1.2  
**Data:** Março 2026  
**Stack:** Laravel · PostgreSQL · Bootstrap  

---

## 1. Visão Geral

Sistema de gestão operacional e financeira para clínica de estética. O objetivo é centralizar o controle de entradas e saídas financeiras, contratos com fornecedores, manutenção de equipamentos e projeção de caixa — substituindo a gestão manual em planilha.

**Dores atuais identificadas:**
- Sem separação entre fase de implantação e operação recorrente
- Receitas não registradas sistematicamente
- Fornecedores sem centralização de contatos, contratos e datas de reajuste
- Sem visão de fluxo de caixa projetado
- Sem alertas automáticos de vencimentos

---

## 2. Módulos do Sistema

### Módulo 1 — Gestão Financeira (Core)

#### 2.1 Conceitos Fundamentais

**Fase da transação** (campo obrigatório):
- `implantacao` — gastos de abertura da clínica: reforma, móveis, projeto, burocracia, equipamentos
- `operacao` — despesas e receitas recorrentes do funcionamento da clínica

> Esta separação é crítica. O lucro operacional mensal deve excluir os custos de implantação, que aparecem em painel separado como "Investimento total realizado".

**Tipo de transação:**
- `entrada` — receita gerada pela clínica
- `saida` — despesa ou custo

**Status de pagamento:**
- `pago` — transação liquidada
- `pendente` — aguardando pagamento
- `cancelado` — cancelado/estornado

#### 2.2 Entradas (Receitas)

Campos necessários:
- Tipo de serviço ou produto (ex: procedimento facial, depilação, produto vendido)
- Cliente (opcional — para histórico)
- Valor bruto cobrado
- Forma de pagamento (com taxas diferenciadas por modalidade):
  - Pix / dinheiro — sem taxa
  - Débito — taxa ~1,5%
  - Crédito à vista — taxa ~2,5%
  - Crédito parcelado — taxa variável (configurável por número de parcelas)
- Número de parcelas (se parcelado)
- Data da prestação do serviço (`data_competencia`)
- Data do recebimento efetivo (`data_pagamento`)
- Status: pago, pendente, cancelado

#### 2.3 Saídas (Despesas)

Campos necessários:
- Categoria: Infraestrutura, Utilidades, Marketing, Burocracia, Reforma, Impostos, Pessoal, Insumos
- Descrição
- Fornecedor (vinculado ao Módulo 2, opcional)
- Valor bruto
- Forma de pagamento: Pix, Boleto, Cartão de Crédito, Débito, Dinheiro
- Data de competência (`data_competencia`)
- Data de pagamento (`data_pagamento`)
- Status: pago, pendente, cancelado
- Recorrência: única, mensal, trimestral, semestral, anual
- Data de início da recorrência (para geração automática das parcelas futuras)
- **Anexo de boleto** — arquivo PDF ou imagem do boleto a pagar (upload opcional)
- **Anexo de comprovante** — arquivo PDF ou imagem do comprovante de pagamento (upload ao marcar como pago)

#### 2.4 Regras de Negócio

```
imposto_estimado = valor_bruto * 0.06  (Simples Nacional, fixo)

taxa_operacional = valor_bruto * taxa_forma_pagamento

valor_liquido = valor_bruto - taxa_operacional - imposto_estimado
```

- O cálculo de `valor_liquido` é feito automaticamente no backend
- O imposto só é aplicado em entradas
- A taxa de cartão é configurável por modalidade nas configurações do sistema
- **Anexos aceitos:** PDF, JPG, PNG — tamanho máximo de 10 MB por arquivo
- **Boleto:** pode ser anexado ao criar ou editar uma saída com status `pendente`
- **Comprovante:** pode ser anexado a qualquer momento; ao anexar um comprovante, o sistema sugere marcar a transação como `pago`
- Uma transação pode ter múltiplos anexos (ex: boleto + comprovante de pagamento)
- Os arquivos são armazenados no storage do Laravel (`storage/app/private/anexos/transacoes/{id}/`)
- O acesso aos arquivos é protegido — somente usuários autenticados podem visualizar/baixar

#### 2.5 Estrutura do Banco — Módulo Financeiro

```sql
CREATE TABLE transacoes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tipo VARCHAR(10) NOT NULL CHECK (tipo IN ('entrada', 'saida')),
    fase VARCHAR(20) NOT NULL DEFAULT 'operacao' CHECK (fase IN ('implantacao', 'operacao')),
    categoria VARCHAR(100) NOT NULL,
    subcategoria VARCHAR(100),
    centro_custo VARCHAR(100),
    descricao TEXT NOT NULL,
    cliente VARCHAR(150),
    fornecedor_id UUID REFERENCES fornecedores(id) ON DELETE SET NULL,
    valor_bruto DECIMAL(10,2) NOT NULL,
    taxa_operacional DECIMAL(10,2) DEFAULT 0,
    imposto_estimado DECIMAL(10,2) DEFAULT 0,
    valor_liquido DECIMAL(10,2) NOT NULL,
    data_competencia DATE NOT NULL,
    data_pagamento DATE,
    forma_pagamento VARCHAR(30) NOT NULL,
    num_parcelas INT DEFAULT 1,
    parcela_atual INT DEFAULT 1,
    status VARCHAR(20) NOT NULL DEFAULT 'pendente' CHECK (status IN ('pago', 'pendente', 'cancelado')),
    recorrencia VARCHAR(20) DEFAULT 'unica' CHECK (recorrencia IN ('unica', 'mensal', 'trimestral', 'semestral', 'anual')),
    data_inicio_recorrencia DATE,
    transacao_pai_id UUID REFERENCES transacoes(id) ON DELETE SET NULL, -- liga parcelas à transação original
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE taxas_cartao (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    modalidade VARCHAR(50) NOT NULL UNIQUE, -- 'debito', 'credito_1x', 'credito_2x', ..., 'credito_12x'
    percentual DECIMAL(5,2) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE
);

-- Anexos de transações (boletos e comprovantes)
CREATE TABLE transacao_anexos (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    transacao_id UUID NOT NULL REFERENCES transacoes(id) ON DELETE CASCADE,
    tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('boleto', 'comprovante')),
    nome_arquivo VARCHAR(255) NOT NULL,       -- nome original do arquivo
    caminho VARCHAR(500) NOT NULL,            -- path no storage (ex: storage/anexos/transacoes/...)
    mime_type VARCHAR(100),                   -- application/pdf, image/jpeg, image/png
    tamanho_bytes INT,
    created_at TIMESTAMP DEFAULT NOW()
);
```

---

### Módulo 2 — Contratos e Fornecedores

#### 2.6 Cadastro de Fornecedores

Campos necessários:
- Nome fantasia
- Razão social
- CNPJ
- Serviço prestado (ex: Coleta de resíduos hospitalares, Internet, Contabilidade)
- Categoria: Infraestrutura, Utilidades, Saúde/Segurança, Administrativo, Marketing
- Contato principal (nome + telefone + e-mail) — essencial para emergências
- Contato de suporte técnico/emergência (nome + telefone)
- **Arquivo do contrato** — upload do PDF do contrato assinado diretamente no cadastro do fornecedor
- Status: ativo, suspenso, encerrado

#### 2.7 Contratos

Campos necessários:
- Fornecedor (vinculado)
- Valor mensal atual
- Data de início do contrato
- Data de término (se houver)
- Periodicidade de reajuste (anual, semestral)
- Próxima data de reajuste
- Índice de reajuste: IGPM, IPCA, INPC, fixo, livre negociação
- Multa por rescisão antecipada (R$ ou % do valor mensal × meses restantes)
- Aviso prévio para rescisão (dias)
- Documento do contrato — upload de arquivo **ou** link externo (Google Drive, OneDrive): ambos são suportados; o upload tem prioridade se ambos existirem
- Classificação de risco: baixo, médio, alto (impacto no negócio se o serviço parar)
- Status: ativo, em negociação, encerrado, suspenso

#### 2.8 Histórico de Reajustes

Registrado automaticamente ao confirmar um reajuste:
- Data do reajuste
- Valor anterior
- Valor novo
- Índice aplicado
- Percentual efetivo de aumento

#### 2.9 Estrutura do Banco — Módulo Contratos

```sql
CREATE TABLE fornecedores (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nome_fantasia VARCHAR(150) NOT NULL,
    razao_social VARCHAR(200),
    cnpj VARCHAR(20) UNIQUE,
    servico_prestado VARCHAR(200) NOT NULL,
    categoria VARCHAR(100),
    contato_nome VARCHAR(150),
    contato_telefone VARCHAR(20),
    contato_email VARCHAR(150),
    contato_emergencia_nome VARCHAR(150),
    contato_emergencia_telefone VARCHAR(20),
    status VARCHAR(20) DEFAULT 'ativo' CHECK (status IN ('ativo', 'suspenso', 'encerrado')),
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE contratos (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    fornecedor_id UUID NOT NULL REFERENCES fornecedores(id),
    valor_mensal DECIMAL(10,2) NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE,
    periodicidade_reajuste VARCHAR(20) DEFAULT 'anual',
    data_proximo_reajuste DATE,
    indice_reajuste VARCHAR(30), -- 'IGPM', 'IPCA', 'INPC', 'fixo', 'livre'
    multa_rescisao_valor DECIMAL(10,2),
    multa_rescisao_percentual DECIMAL(5,2),
    aviso_previo_dias INT DEFAULT 30,
    arquivo_contrato_path VARCHAR(500),       -- path no storage se upload direto
    arquivo_contrato_nome VARCHAR(255),       -- nome original do arquivo
    link_contrato TEXT,                       -- URL externa (Google Drive, OneDrive, etc.)
    risco VARCHAR(10) DEFAULT 'medio' CHECK (risco IN ('baixo', 'medio', 'alto')),
    status VARCHAR(20) DEFAULT 'ativo' CHECK (status IN ('ativo', 'em_negociacao', 'encerrado', 'suspenso')),
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE contratos_reajustes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    contrato_id UUID NOT NULL REFERENCES contratos(id),
    data_reajuste DATE NOT NULL,
    valor_anterior DECIMAL(10,2) NOT NULL,
    valor_novo DECIMAL(10,2) NOT NULL,
    indice VARCHAR(30),
    percentual_efetivo DECIMAL(5,2),
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);
```

---

### Módulo 3 — Manutenção Preventiva

#### 2.10 Equipamentos e Plano de Manutenção

Campos necessários:
- Descrição do equipamento ou serviço (ex: Aparelho de laser, Ar-condicionado, Autoclave)
- Tipo: preventiva, corretiva, calibração, higienização
- Frequência em meses (ex: 6 = semestral, 12 = anual)
- Última data de manutenção realizada
- Próxima data calculada automaticamente:

```
proxima_data = ultima_data + (frequencia_meses * 30 dias)
```

- Custo previsto
- Responsável (fornecedor interno ou externo)
- Status: em_dia, vencendo_em_breve (≤ 15 dias), vencido

#### 2.11 Histórico de Execuções

- Data real de execução
- Custo real (vs custo previsto)
- Observações e intercorrências
- Próxima data recalculada a partir da execução

#### 2.12 Estrutura do Banco — Módulo Manutenção

```sql
CREATE TABLE manutencoes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    descricao VARCHAR(200) NOT NULL,
    tipo VARCHAR(30) NOT NULL CHECK (tipo IN ('preventiva', 'corretiva', 'calibracao', 'higienizacao')),
    frequencia_meses INT NOT NULL,
    ultima_data DATE,
    proxima_data DATE,
    custo_previsto DECIMAL(10,2),
    responsavel VARCHAR(200),
    ativo BOOLEAN DEFAULT TRUE,
    observacoes TEXT
);

CREATE TABLE manutencao_execucoes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    manutencao_id UUID NOT NULL REFERENCES manutencoes(id),
    data_execucao DATE NOT NULL,
    custo_real DECIMAL(10,2),
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);
```

---

## 3. Dashboard e Indicadores

### 3.1 Painel Operacional (mensal)

Exibir apenas transações com `fase = 'operacao'`:

| Indicador | Cálculo |
|---|---|
| Receita bruta | Soma das entradas pagas no mês |
| Despesas totais | Soma das saídas pagas no mês |
| Lucro líquido | Receita líquida − Despesas totais |
| Margem (%) | Lucro líquido / Receita bruta × 100 |
| Ticket médio | Receita bruta / número de atendimentos |
| Saldo atual | Soma histórica (entradas − saídas) pagas |
| Inadimplência | Entradas pendentes com data_competencia no mês |

### 3.2 Painel de Implantação (separado)

Exibir apenas transações com `fase = 'implantacao'`:

| Indicador | Valor |
|---|---|
| Total gasto em implantação | R$ X |
| Ainda a pagar (pendentes) | R$ X |
| Por categoria | Reforma, Projeto, Burocracia, Equipamentos |

### 3.3 Filtros do Dashboard

- Por mês/ano
- Por fase (implantação / operação)
- Por categoria
- Comparativo mês atual vs mês anterior

---

## 4. Sistema de Alertas

Alertas exibidos no dashboard e (futuramente) enviados por e-mail:

| Tipo | Gatilho |
|---|---|
| Conta a vencer | Saídas com status `pendente` e `data_pagamento` nos próximos 7 dias |
| Contrato próximo do vencimento | `data_fim` do contrato ≤ 60 dias |
| Reajuste se aproximando | `data_proximo_reajuste` ≤ 30 dias |
| Manutenção próxima | `proxima_data` ≤ 15 dias |
| Manutenção vencida | `proxima_data` < hoje |

---

## 5. Projeção Financeira

Calcular automaticamente o fluxo de caixa dos próximos 6 meses com base em:
- Transações recorrentes ativas (`recorrencia != 'unica'`) — projetadas nos meses futuros
- Contratos ativos — valor mensal projetado mês a mês
- Manutenções com `proxima_data` futura — custo previsto no mês correspondente

Exibir: mês a mês, saldo projetado acumulado, destacando o mês estimado de equilíbrio (break-even).

---

## 6. APIs REST

```
POST   /api/transacoes              — Criar transação (entrada ou saída)
GET    /api/transacoes              — Listar com filtros (tipo, fase, status, período)
PUT    /api/transacoes/{id}         — Atualizar transação
DELETE /api/transacoes/{id}         — Cancelar transação
POST   /api/transacoes/{id}/anexos  — Upload de boleto ou comprovante (multipart/form-data)
GET    /api/transacoes/{id}/anexos  — Listar anexos de uma transação
DELETE /api/transacoes/{id}/anexos/{anexo_id} — Remover anexo
GET    /api/anexos/{id}/download    — Download seguro do arquivo

POST   /api/fornecedores            — Cadastrar fornecedor
GET    /api/fornecedores            — Listar fornecedores
PUT    /api/fornecedores/{id}       — Atualizar fornecedor

POST   /api/contratos               — Cadastrar contrato
GET    /api/contratos               — Listar contratos (com alertas)
POST   /api/contratos/{id}/reajuste — Registrar reajuste
POST   /api/contratos/{id}/arquivo  — Upload do arquivo do contrato (multipart/form-data)
GET    /api/contratos/{id}/arquivo/download — Download seguro do contrato

POST   /api/manutencoes             — Cadastrar plano de manutenção
GET    /api/manutencoes             — Listar com status calculado
POST   /api/manutencoes/{id}/executar — Registrar execução e recalcular próxima data

GET    /api/dashboard               — KPIs operacionais do mês
GET    /api/dashboard/implantacao   — Painel de implantação
GET    /api/alertas                 — Todos os alertas ativos
GET    /api/projecao                — Fluxo de caixa projetado (6 meses)
```

---

## 7. Plano de Desenvolvimento por Fases

### Fase 1 — Financeiro básico (prioridade máxima)
- CRUD de transações (entradas e saídas)
- Cálculo automático de imposto e taxa de cartão
- Separação implantação vs operação
- Dashboard operacional básico
- Listagem com filtros por mês, categoria e status
- Upload e download de boletos e comprovantes nas transações

### Fase 2 — Contratos e fornecedores
- CRUD de fornecedores com contatos
- CRUD de contratos com vigência e reajuste
- Upload do arquivo de contrato no cadastro do fornecedor/contrato
- Alertas de vencimento e reajuste
- Vinculação de despesas recorrentes ao contrato

### Fase 3 — Manutenção, projeção e alertas avançados
- CRUD de manutenções e execuções
- Cálculo automático de próxima data
- Projeção de fluxo de caixa 6 meses
- Dashboard completo com todos os alertas

---

## 8. Funcionalidades Não Contempladas Hoje (Roadmap Futuro)

As funcionalidades abaixo não existem na planilha atual e representam ganhos reais de gestão:

1. **Ticket médio por tipo de serviço** — identificar quais procedimentos são mais lucrativos
2. **Custo por insumo** — controle de estoque mínimo e custo por atendimento
3. **Controle de agenda/agendamentos** — integração com receitas por horário
4. **Gestão de pessoal** — comissões sobre atendimentos (se houver profissionais)
5. **Relatórios em PDF** — DRE mensal simplificado exportável
6. **Notificações por e-mail/WhatsApp** — alertas automáticos de vencimento
7. **Controle de metas** — meta mensal de faturamento com acompanhamento visual

---

## 9. Configurações do Sistema

Painel de configurações administrativas:

- Alíquota de imposto (padrão: 6%)
- Taxas de cartão por modalidade (débito, crédito 1x a 12x)
- Categorias de transações (editáveis)
- Prazo de alerta de vencimento de contrato (padrão: 60 dias)
- Prazo de alerta de manutenção (padrão: 15 dias)
- Tamanho máximo de upload por arquivo (padrão: 10 MB)
- Tipos de arquivo permitidos para upload (padrão: PDF, JPG, PNG)

---

## 10. Dados Iniciais para Migração da Planilha

Ao inicializar o sistema, importar:

**Fornecedores identificados:**
- Escritório de Contabilidade — R$ 250/mês — Boleto mensal
- Administradora do Condomínio — R$ 400/mês — Boleto mensal
- Proprietário do imóvel (Aluguel) — R$ 1.759,96/mês — início junho/2026
- Empresa de água — R$ 100/mês estimado — início maio/2026
- Distribuidora de energia — R$ 150/mês estimado — início maio/2026
- Provedor de internet — R$ 90/mês — início maio/2026
- Empresa de coleta de resíduos hospitalares — R$ 70/mês — ativa desde março/2026

**Despesas de implantação já registradas:**
- Projeto arquitetônico — R$ 3.700 — pago
- Agência de marketing (Modernize) — R$ 747 — pago
- Móveis planejados — R$ 22.000 — pagando (R$ 13.000 entrada + 9x R$ 900)
- Pedras para pia — R$ 1.900 — pagando (2x R$ 950)
- Certificados digitais — R$ 740 — pago / a pagar
- Piso vinílico — R$ 5.051,25 — pagando
- Seguro de obra — R$ 889,85 — a pagar
- Acompanhamento de obra — R$ 6.403,75 — a pagar