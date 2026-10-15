# SPEC — Skeleton PHP 8.4 + Slim 4 + Vue 3

**Versão:** 1.0 · **Estado:** especificação de evolução, não implementação · **Referência:** `sandbox_php(5).zip` de 15/10/2026.

## 1. Objetivo

Criar um **skeleton reutilizável para N projetos**, com a simplicidade de PHP tradicional e recursos modernos de PHP 8.4 e Vue 3. O produto de demonstração será um mini CMS de posts e páginas; o site inicial apresentará o próprio skeleton, recursos, arquitetura e documentação. O projeto deve permanecer adequado a desenvolvimento guiado por agentes de IA: código tipado, nomes previsíveis, convenções estáveis, testes objetivos e pouca configuração duplicada.

### Premissas inegociáveis

- Repositório único; um deploy web PHP (banco e serviço SMTP externo/local podem existir separadamente).
- **Nenhum build de JavaScript**: sem Node.js, Vite, Webpack, transpilers ou compilação de SFC. Vue 3 e Vue Router com ES Modules/scripts disponíveis localmente.
- Slim 4 para HTTP; Composer/PSR-4; MySQL/PDO; Phinx; PHPUnit.
- Domínio tipado, livre de dependências do Slim/PDO, sem impor DDD ritualístico.
- Simplicidade, segurança, previsibilidade e código reutilizável antes de abstrações.
- Bibliotecas maduras são preferíveis a infraestrutura caseira; avaliar compatibilidade e manutenção antes de instalar.
- Cada projeto derivado pode alterar seu domínio de User e suas regras; o skeleton não é uma biblioteca imutável.

### Não objetivos

Framework próprio, microserviços, engine de plugins, CMS completo, event bus, ORM obrigatório, hierarquia de classes-base, geração massiva de código, arquitetura limpa dogmática, upload de mídia, editor rich text, tags/categorias ou e-mail de confirmação do cadastro.

## 2. Situação inicial e preservação do legado

No ZIP revisado já existem: `Domain/Plan`, `Application/Plan`, `Infrastructure/{Http,Persistence,View}`, `Presentation/Http`, `Bootstrap`, CRUD de Plan, SPA Vue, `planService.js`, Phinx, compose dev/prod e testes PHPUnit. Tratar este código como **base em funcionamento**, não como algo que deve ser refeito do zero. `todolist.md` é histórico: **não desmarcar, recriar ou sobrescrever seus estados**. A migração Plan → CMS deve ser feita em etapas, com testes e migrations adequadas; não apagar tabelas com dados sem autorização.

## 3. Arquitetura proposta — MVC pragmático, domínio explícito

```text
src/
├── Domain/
│   ├── User/              # User, UserRepository (contrato), erros e regras
│   ├── Post/              # Post, PostRepository (contrato), status, regras
│   └── Page/              # Page, PageRepository (contrato), status, regras
├── Application/
│   ├── Auth/              # AuthService, PasswordRecoveryService
│   ├── User/              # ações de perfil/cadastro quando necessárias
│   ├── Post/              # PostService
│   └── Page/              # PageService
├── Infrastructure/
│   ├── Http/
│   │   ├── Controller/    # adaptadores Slim, atributos de rota
│   │   ├── Middleware/
│   │   ├── Response/
│   │   └── Error/
│   ├── Database/
│   │   ├── ConnectionFactory.php
│   │   └── Repository/    # MysqlUserRepository etc.
│   ├── Auth/              # sessão, cookies, hash de senha
│   ├── Mail/              # envio de recuperação por contrato
│   └── View/              # ViewRenderer
└── Bootstrap/             # composição de dependências, inicialização, config
database/
├── migrations/
└── seeds/                 # opcionais, apenas dados explícitos de desenvolvimento
public/
├── index.php
└── assets/{js,css,vendor}/
tests/{Unit,Integration,Architecture}/
docs/
SPEC.md
AGENTS.md
README.md
```

As pastas são referência, **não criar diretórios vazios por obrigação**. O bootstrap não deve depender da presença de Posts ou Pages. `Domain` só depende de PHP e do próprio domínio; `Application` depende de `Domain`; `Infrastructure` adapta portas para MySQL, HTTP, sessão, e-mail e templates; `Bootstrap` compõe tudo. Controller não conhece PDO nem tem regra de negócio. Repository interface no domínio; implementação MySQL em infraestrutura. Services devem representar operações reais, não existir por ritual. O padrão `Controller → Service → Repository → MySQL` é aceitável se a entidade proteger invariantes.

### Usuários: padrão pronto + customização por projeto

`User` básico (id, name, email, passwordHash, role, createdAt, updatedAt) e Auth são **módulos padrão funcionais**, não camadas abstratas imutáveis. Um projeto pode: (a) usar diretamente; (b) adicionar `AuthorProfile`, `OrganizationMembership` etc. como entidades relacionadas; (c) adaptar `User`, fluxos e migrations para autenticação externa ou identidade diferente. Não forçar herança ou contratos desnecessários. Sessão/cookies/hash não podem depender de Posts/Pages nem de campos de perfil do CMS. Autorização inicial ADMIN/USER; sempre checada no backend. Cadastro público nunca pode atribuir ADMIN; provisionar admin por comando controlado.

## 4. Capacidades compartilhadas (skeleton)

### SK-01 — Estrutura e convenções
- [x] Reorganizar `src` na estrutura-alvo com mudanças pequenas e atualização de namespaces.
- [x] Preservar bootstrap/testes durante transição; remover `Presentation` somente após mover controllers.
- [x] Rever `PlanData` e mappers redundantes antes de replicar padrões no CMS.
- [x] Definir regras de dependência e verificações automatizadas proporcionais ao projeto.
**Aceite:** uma nova funcionalidade pode ser localizada por domínio e criada sem alterar código interno da infraestrutura.

### SK-02 — Rotas declarativas
- [x] Avaliar bibliotecas atuais de attributes para Slim 4 (manutenção, licença, PHP 8.4, middlewares, cache).
- [x] Usar attributes somente em endpoints explícitos; não expor automaticamente métodos públicos.
- [x] Permitir prefixo, método HTTP, parâmetros, agrupamento e middleware.
- [x] Preservar 404 JSON `/api/*` e fallback SPA; descobrir rotas sem custo desnecessário em produção.
- [x] Testar rotas duplicadas e resolução de parâmetros.
- [x] Testar autorização em endpoints autenticados quando introduzidos (F4/F5).
**Aceite:** adicionar endpoint não exige duplicar o registro manual em `Routes.php`.

### SK-03 — Composição de dependências
- [x] Avaliar container PSR-11 com autowiring **se** reduzir boilerplate real.
- [x] Fazer bindings explícitos contrato → implementação e valores de configuração.
- [x] Nunca usar service locator em entidades/services; preservar construtores e testes substituíveis.
**Aceite:** classe nova típica não exige compor manualmente toda a árvore; bindings continuam rastreáveis.

### SK-04 — OpenAPI e documentação
- [x] Avaliar `zircote/swagger-php` com PHP attributes e Swagger UI hospedado localmente.
- [x] Documentar rotas, corpos, schemas e códigos de erro disponíveis; sessão/cookie e CSRF serão documentados ao introduzir Auth (F4).
- [x] Gerar especificação via Composer/CI/deploy, sem Node e sem geração a cada request.
- [x] Se roteamento/documentação exigirem `path` duplicado, investigar integração **sem criar framework caseiro**; caso não compense, teste de consistência entre rotas reais e OpenAPI.
- [x] Definir visibilidade da documentação em produção.
**Aceite:** API navegável; OpenAPI válida e sincronizada com rotas.

### SK-05 — HTTP, segurança e configuração
- [x] Centralizar exceções de aplicação/domínio → HTTP; erros estáveis sem stack trace externo.
- [x] Distinguir payload malformado (HTTP) de invariantes (domínio); validar sem duplicação inútil.
- [x] Centralizar leitura/validação de config, sem `getenv` espalhado.
- [x] Manter prepared statements, secrets fora do repositório, headers e logs.
- [x] Revisar Vue runtime compiler e CSP `unsafe-eval`; não executar templates fornecidos por usuários.
- [x] Proteger APIs com CSRF se autenticadas por cookie; autorização em todos os endpoints necessários (ainda não há endpoints autenticados; aplicar junto com Auth em F4).
**Aceite:** respostas e falhas previsíveis; regras de segurança verificadas por testes.

### SK-06 — Banco e migrations
- [x] Manter PDO e Phinx com migrations explícitas, versionadas e independentes do startup.
- [x] Separar dados demonstrativos de novas migrations estruturais; preservar o seed da migration Plan histórica já aplicada.
- [x] Planejar baseline para banco existente e testar migration em banco vazio e schema legado com dados preservados.
- [x] Remover do fluxo final a obrigação de init.sql do schema; não reescrever migrations já aplicadas.
**Aceite:** deploy pode migrar schema como etapa explícita; dados existentes preservados.

### SK-07 — Vue sem build
- [x] Padrão simples para páginas, componentes e serviços Fetch separados.
- [x] Módulos ES nativos, roteamento Vue e assets locais versionados.
- [x] Loading, erros, validação acessível e responsividade; sem biblioteca de estado se não necessária.
- [x] Evitar renderização de HTML não confiável (`v-html`) e loaders/compiladores próprios desnecessários.
**Aceite:** nova tela é navegável sem transpilar ou executar Node.

### SK-08 — Testes/observabilidade
- [x] Manter unitários, integração HTTP, integração MySQL e testes de regras arquiteturais pertinentes.
- [x] Avaliar PHPStan, PSR-12 e checagem de OpenAPI; comandos Composer documentados.
- [x] Garantir mensagens de log úteis e sem dados sensíveis.
**Aceite:** o projeto valida regressões com comandos previsíveis.

## 5. Produto de referência — mini CMS

### CMS-01 — User e Auth
- [x] User tipado e repository; e-mail único normalizado e senhas com `password_hash`/`password_verify`.
- [x] Registro, login, logout, `me`, recuperação de senha e reset por link.
- [x] Sessão PHP no servidor, session ID regenerado após login, `HttpOnly`, `Secure` em HTTPS e `SameSite` apropriado.
- [x] CSRF em operações com cookie, rate limiting em endpoints sensíveis e proteção contra enumeração de contas.
- [x] Token aleatório criptograficamente seguro, somente hash persistido, expiração, uso único e invalidação dos tokens pendentes após reset.
- [x] Envio de e-mail por adaptador SMTP, configuração por ambiente e estratégia segura em desenvolvimento (SMTP ausente falha fechado; solicitação continua genérica).
- [x] Cadastro sem confirmação de e-mail; **recuperação exige controle da caixa de e-mail**.
- [x] Roles USER e ADMIN, registro público apenas USER, admin inicial por comando seguro.
- [x] Não exigir JWT ou pacote de permissões pesado inicialmente.
**Aceite:** fluxos autenticados e recuperação funcionam; usuários comuns não editam CMS.

### CMS-02 — Posts
Campos mínimos: id, title, slug, content, excerpt, status (DRAFT/PUBLISHED), authorId, publishedAt, createdAt, updatedAt.
- [x] CRUD autenticado, publish/unpublish e listagem paginada.
- [x] Slug único, título obrigatório, data de publicação coerente.
- [x] API pública lista e exibe somente publicados.
- [x] Só ADMIN administra; conteúdo Markdown com renderização segura.
**Aceite:** publicar post no admin faz aparecer no blog público.

### CMS-03 — Pages
Campos mínimos: id, title, slug, content, status (DRAFT/PUBLISHED), publishedAt, createdAt, updatedAt.
- [x] CRUD autenticado, publish/unpublish e visualização pública.
- [x] Reservar slugs da aplicação (`admin`, `api`, `login`, `docs`, `blog`, etc.) para evitar colisões.
- [x] Não permitir que página dinâmica intercepte API, área administrativa e rotas técnicas.
- [x] Reutilizar editor/estilos de posts sem fundir antecipadamente os domínios.
**Aceite:** publicar página cria URL pública válida sem conflito de roteamento.

### CMS-04 — Editor
- [x] Armazenar Markdown e renderizar com biblioteca adequada e sanitização/escape.
- [x] Preview e formulário compartilhado quando fizer sentido; não usar WYSIWYG no MVP.
- [x] Sem imagens, mídia, comentários, categorias, tags ou versionamento no MVP.

## 6. Site público do skeleton

Home do skeleton (não uma home genérica de exemplo) com apresentação, objetivos, stack, recursos, guia de instalação, documentação de arquitetura, exemplos de código, link para Swagger/OpenAPI, blog com posts publicados e páginas CMS. Docs técnicas podem vir de arquivos Markdown versionados, **sem depender da existência de Pages no banco**. Área admin em `/admin` com dashboard simples, posts e pages; formulários de login/cadastro/recuperação. SPA Vue em um único deploy, backend autorizando tudo.

### API proposta (validar durante implementação)

```text
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me
POST   /api/auth/forgot-password
POST   /api/auth/reset-password
GET    /api/posts
GET    /api/posts/{slug}
GET    /api/pages/{slug}
GET    /api/admin/posts
POST   /api/admin/posts
GET    /api/admin/posts/{id}
PUT    /api/admin/posts/{id}
DELETE /api/admin/posts/{id}
POST   /api/admin/posts/{id}/publish
POST   /api/admin/posts/{id}/unpublish
GET    /api/admin/pages
POST   /api/admin/pages
GET    /api/admin/pages/{id}
PUT    /api/admin/pages/{id}
DELETE /api/admin/pages/{id}
POST   /api/admin/pages/{id}/publish
POST   /api/admin/pages/{id}/unpublish
```

APIs públicas e administrativas devem usar contratos previsíveis, schemas OpenAPI tipados e testes. Rotas do Vue de posts/pages não devem esconder erros `/api/*`.

## 7. Fases de execução (uma por vez)

### F1 — Consolidar estrutura existente
- [x] SK-01 e revisão das responsabilidades de controller/service/repository.
- [x] Não quebrar CRUD de Plan na transição; preservar testes atuais.
- [x] Separar configuração, erros, imports e namespaces sem refatorar funcionalidades não relacionadas.
**Gate:** testes existentes aprovados; diagrama de dependências simples e README atualizado.

### F2 — Convenções produtivas
- [x] SK-02/03/04: attributes, DI proporcional, OpenAPI e Swagger UI.
- [x] SK-05/06/07/08: reforçar segurança, migrations, Vue e testes.
**Gate:** CRUD de Plan ainda passa; documentação e rotas sincronizadas; zero build JS.

**Registro F2 (15/10/2026):** fase concluída. SK-05/07/08 foi verificado para as capacidades atuais; rotas de autenticação e documentação dos contratos CMS serão feitas em F4/F5. SK-06 foi testado em base vazia e schema legado com dado preservado. Decisão de escopo: manter os dois registros demonstrativos dentro da migration Plan histórica, já aplicada, e separar dados de exemplo de todas as migrations novas; não reescrever o histórico aplicado.

### F3 — Transição do exemplo Plan
- [x] Criar migrations User/Post/Page e retirar o exemplo Plan do frontend sem alterar o schema existente.
- [x] Tirar tela/serviço Plan do frontend; manter API e testes de regressão isolados como módulo legado opt-in até substituição funcional em F4/F5.
- [x] Não remover ou renomear schema com dados sem migração/backup e confirmação.
**Gate:** skeleton inicializa sem depender do domínio Plan; nenhum dado apagado inadvertidamente. **Concluído:** entrada padrão não registra o controller nem compõe o repository Plan; ativação explícita permanece disponível para compatibilidade e testes.

**Registro F3 (15/10/2026):** migration aditiva `20261015000002` criou `users`, `posts` e `pages` no banco dev cujo histórico continha a migration Plan. A tabela `plans` e seus dados não foram alterados. O bootstrap padrão não importa o domínio Plan nem registra seu controller; o módulo de compatibilidade pode ser ativado explicitamente e continua coberto pela suíte. A tela e o serviço Plan foram retirados do frontend. A reversão da migration CMS recusa execução se alguma das três tabelas contiver dados.

### F4 — Identidade e autenticação
- [x] CMS-01 completo, testes e documentação da API.
**Gate:** registro, login, logout, recuperação por e-mail e ADMIN/USER testados.

### F5 — Mini CMS
- [x] CMS-02/03/04, testes e OpenAPI.
**Gate:** ADMIN publica posts e pages, visitante vê somente conteúdo publicado.

**Registro F5 (15/10/2026):** CRUD de Posts e Pages implementado com repositories MySQL, ações explícitas de publicação, paginação e filtros públicos por status/data. Todas as operações administrativas conferem role ADMIN no backend; mutações de Auth/Admin exigem CSRF. Pages rejeitam slugs reservados. Conteúdo Markdown é renderizado por `league/commonmark` com HTML bruto escapado e links inseguros desativados; `/admin/content` fornece editor, preview e gestão básica. OpenAPI foi regenerada com schemas de conteúdo e paginação. A migration CMS existente foi preservada.

### F6 — Site institucional do skeleton
- [x] Home, features, arquitetura, docs, blog, pages e administração.
- [x] Verificar montagem da SPA nas rotas principais em Chrome/Chromium headless junto dos testes PHPUnit.
**Gate:** o próprio site demonstra a arquitetura e funciona em instalação limpa; `./tools/test-all.sh` verifica browser e backend.

### F7 — Distribuição
- [x] `README.md`, `AGENTS.md`, `.env.example`, testes completos, deploy prod, migrações em banco vazio e instruções de customização User/Auth.
- [x] Exercício: criar novo domínio seguindo convenções sem modificar internals de HTTP ou banco.
**Gate:** desenvolvedor ou Codex consegue clonar e iniciar novo projeto sem Node/build. **Concluído:** instruções foram validadas contra o Compose, imagem de produção construída e migrations executadas em schema vazio.

**Registro F7 (15/10/2026):** README refeito como guia de instalação e operação; AGENTS atualizado para apontar aos procedimentos vigentes; `.env.example`, Compose e instruções de produção alinhados a configuração explícita, HTTPS e SMTP. `docs/customization.md` descreve adaptação de User/Auth e extensão; o domínio Notice e seu teste demonstram entidade, contrato e serviço de aplicação sem mudanças nos adapters HTTP ou PDO existentes. `composer test` passou (64 testes, 265 assertions); `tools/test-all.sh` passou no Chromium (10 rotas) e na mesma suíte PHPUnit. A imagem `production` foi construída; `compose.prod.yaml config --quiet` passou; as três migrations foram aplicadas e listadas como `up` num schema MySQL temporário vazio, removido após a verificação.

## 8. Critérios globais de pronto

- [x] Um único deploy da aplicação entrega backend e Vue, sem build JS.
- [x] Rotas declarativas e OpenAPI automática.
- [x] Auth funcional, customizável por projeto e independente do CMS.
- [x] Posts e Pages funcionais, com admin protegido e público seguro.
- [x] Site do skeleton com documentação.
- [x] Migrations, PHPUnit, convenções e documentação operacional.
- [x] Infraestrutura não depende de módulos CMS; código tipado e previsível para agentes.

**Registro F6 (15/10/2026):** a SPA apresenta home institucional, recursos, arquitetura e guia de instalação; conecta Swagger/OpenAPI, blog, pages publicadas e administração. Foram adicionados formulários de login, cadastro, recuperação e redefinição de senha integrados à API existente. Slugs dessas rotas institucionais e de autenticação são protegidos contra colisão com Pages CMS. `tools/test-frontend.sh` usa Chrome/Chromium headless para verificar Vue montado e conteúdo esperado nas rotas centrais; `tools/test-all.sh` executa essa verificação e PHPUnit. PHPUnit sozinho não detecta falhas de parsing ou montagem do frontend.

## 9. Regras de mudança e histórico

- Esta SPEC passa a ser a especificação **de evolução**; `todolist.md` é histórico da implantação anterior, a manter intacto.
- Marcar somente itens verificados em código/testes. Atualizar critérios de aceite e registrar alterações deliberadas de escopo.
- Toda etapa deve ser pequena, reversível e revisável; sem executar todas as fases em um prompt.
- Mudanças grandes, troca de bibliotecas estruturais ou migrações destrutivas exigem avaliação antes de implementar.
