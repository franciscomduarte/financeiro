# 🚀 Laravel SaaS API - High Performance Guidelines

## 🧱 Tech Stack
- **Backend:** Laravel 11+ (PHP 8.3+)
- **Database:** PostgreSQL (JSONB + índices avançados)
- **Frontend:** Tailwind CSS (Mobile-First) + Livewire/Blade
- **Infra:** Redis (Cache, Queue, Rate Limiting)
- **Build:** Vite

## ⚠️ Core Principles (MANDATORY)
- **Performance First:** Toda decisão deve considerar impacto em escala e latência.
- **Multi-Tenancy Ready:** Isolamento total de dados via `tenant_id`.
- **Deterministic APIs:** Respostas previsíveis, tipadas e consistentes.
- **Fail Loudly:** Erros devem ser capturados e logados, nunca silenciados.
- **Resiliência:** Uso obrigatório de Transactions e retries em serviços externos.

## 🏢 Multi-Tenancy (CRITICAL)
- **Isolamento:** Todas as tabelas (exceto globais) devem conter `tenant_id`.
- **Scopes:** Usar `Global Scopes` nos Models para garantir que nenhuma query escape ao `tenant_id`.
- **Resolução:** Resolver o tenant via Middleware (subdomínio ou header) e injetar no Service Container.
- **Indexes:** - `INDEX (tenant_id)` em todas as tabelas vinculadas.
    - `INDEX (tenant_id, created_at)` para listagens paginadas.

## 🧠 API Standards
- **Responses:** Use `ApiResource` ou DTOs. Nunca retorne Models diretamente.
- **Pagination:** - Proibido usar `all()` ou `get()` sem limite.
    - Use `paginate(20)` ou `cursorPaginate()` para volumes massivos.
- **Filtering:** Padronizar via query strings: `?filter[name]=&sort=-created_at`.
- **Versioning:** Prefixo obrigatório `/api/v1/`.
- **Enums:** Usar PHP Native Enums para todos os status e tipos fixos.

## ⚡ Performance & PostgreSQL
- **N+1 Prevention:** Ativar `Model::preventLazyLoading()` em desenvolvimento.
- **Query Optimization:** - Sempre use `select(['id', 'name', ...])` para evitar overhead.
    - Eager Loading (`with(['...'])`) obrigatório para relações.
- **Postgres Advanced:**
    - Use `JSONB` com índices `GIN` para dados semi-estruturados.
    - Use `Full-Text Search` (tsvector) em vez de `LIKE %...%`.
- **Caching:** - Estratégia de Cache Tags: `Cache::tags(['tenant:' . $id])`.
    - TTL obrigatório em todas as chaves.

## 🧩 Arquitetura & Patterns
- **Controllers:** Devem ser "Thin" (máximo 10 linhas por método). Apenas orquestração.
- **Actions/Services:** Lógica de negócio isolada em classes de responsabilidade única (ex: `CreateInvoiceAction`).
- **Transactions:** `DB::transaction()` obrigatório em operações que alteram múltiplos estados.
- **Queues:** Jobs para e-mails, relatórios, webhooks e processamento pesado.
- **Type Safety:** `declare(strict_types=1);` em todos os arquivos novos.

## 🎨 Frontend & Mobile-First (Tailwind)
- **Mobile-First:** Estilizar primeiro para telas pequenas. Use prefixos `md:`, `lg:` para desktop.
- **UX:** - Touch targets ≥ 44x44px.
    - Inputs semânticos (`type="email"`, `tel`) para teclados mobile.
- **Performance:**
    - Componentizar elementos repetitivos via Blade Components.
    - `loading="lazy"` em imagens fora da dobra superior.
    - Purge CSS ativo via Vite.

## 🧪 Qualidade & Segurança
- **Testing:** - Feature Tests para todos os endpoints da API.
    - Mocking de APIs externas (`Http::fake()`) e Queues.
- **Validation:** Uso obrigatório de `FormRequest`.
- **Security:** - Sanitização de inputs e Escape de outputs.
    - Auth via Laravel Sanctum (Token-based) com escopos de tenant.
- **Idempotência:** Implementar em endpoints de escrita crítica (ex: pagamentos).

## 🚨 Anti-Patterns (PROIBIDO)
- ❌ Queries dentro de loops (N+1).
- ❌ Lógica de negócio ou queries SQL dentro do Blade.
- ❌ Regras de negócio dentro de Controllers.
- ❌ Falta de `tenant_id` em queries de tabelas vinculadas.
- ❌ Uso de `SELECT *`.

## 📊 Observabilidade
- **Logs:** Estruturados em JSON contendo `tenant_id` e `user_id`.
- **Monitoring:** Monitorar tempo de resposta de queries e falhas de Jobs via Laravel Horizon.