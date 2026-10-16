# AGENTS.md — Regras para Codex neste repositório

## Missão

Este repositório fornece um **skeleton reutilizável** PHP/Slim/Vue com cadastro, login e blog de Posts. Para funcionalidades novas, siga `.specs/guidelines/workflow.md`: Feature → Spec → Plan → Code → Review. Antes de finalizar a Spec, apresente apenas alternativas técnicas reais e aguarde a escolha do usuário quando houver tradeoffs relevantes; para uma solução única evidente, justifique-a com base no código e prossiga. Leia `.specs/README.md` e a spec da funcionalidade antes de implementar. Para o skeleton histórico, consulte `.specs/features/001-skeleton/spec.md` e suas reviews; a funcionalidade 002 registra a simplificação atual do produto. A spec define o alvo; o código existente é a base real. **Não executar uma spec inteira de uma só vez sem escopo explícito.**

**Premissa multiagente do SDD Lite:** regras, avisos e gates do fluxo devem funcionar para qualquer agente por meio dos comandos e artefatos canônicos em `.specs/`. Nunca implemente uma regra do SDD Lite apenas para Codex, Claude, Junie ou outra ferramenta; adaptadores específicos servem somente para descobrir o comando comum.

**Exceções do fluxo:** não bloqueie o usuário por uma regra processual do SDD Lite. Avise qual regra seria quebrada, justifique tecnicamente o caminho recomendado e explique o risco da exceção. Se o usuário insistir explicitamente, siga no escopo autorizado e reafirme na entrega que a quebra ocorreu por autorização expressa. Não invente validações, decisões, testes ou gates aprovados; registre como pendente o que não foi comprovado. Restrições de segurança, autorização para operações destrutivas e limites externos ao SDD Lite permanecem aplicáveis.

**Isolamento recomendado do SDD Lite:** prefira um chat novo, com contexto zerado, para cada comando Feature, Spec, Plan, Code ou Review. Antes de executar outro comando na mesma conversa, avise que hipóteses e decisões provisórias acumuladas podem comprometer a qualidade e a rastreabilidade da nova etapa; recomende abrir um chat novo, sem bloquear o usuário. Se ele insistir explicitamente, siga a etapa pedida, relendo os artefatos e conferindo o estado real do repositório; reafirme na entrega que o isolamento foi quebrado por autorização explícita. Se o pedido ainda não expressar essa decisão, aguarde a resposta. Não use o histórico da conversa como validação ou evidência. Respostas necessárias para concluir o comando atual, como a escolha técnica da Spec, pertencem à mesma execução. Consulte `.specs/guidelines/workflow.md` para a regra completa.

Feature, Spec e Plan exigem validação explícita do usuário antes da etapa seguinte, registrada **no próprio artefato validado**, com nome informado pelo usuário e data obtida do SO. Code não precisa de validação do usuário por tarefa; antes de cada ID, confira tarefas predecessoras marcadas no Plan e implementadas de fato no diff, código e testes. **Spec não altera Feature; Plan não altera Feature ou Spec; Code não altera Feature nem Spec e apenas marca a tarefa concluída no checklist do Plan; Review não altera Feature, Spec ou Plan.** Divergências aprovadas ficam no artefato da etapa atual, sem reescrever decisões anteriores. Uma funcionalidade nova usa apenas `feature.md`, `spec.md`, `plan.md` e `review.md`; não crie registros por tarefa ou arquivos de validação. Consulte `.specs/guidelines/workflow.md` para os registros e exceções.

Feature cria e registra uma branch própria antes de escrever `feature.md`. Review confere branch, diff, commit local e, quando acessível, o remoto; falta de publicação comprovada é limite da auditoria, sem tarefa automática de push. A Review não faz commit nem push; siga a política de mudanças abaixo para essas operações.

## Prioridades, em ordem

1. Não quebrar comportamento/testes já existentes.
2. Segurança e integridade de dados.
3. Simplicidade e previsibilidade para outros desenvolvedores/agentes.
4. Organização de domínio e baixo boilerplate.
5. Melhorias opcionais de automação somente quando reduzirem manutenção.

## Regras inegociáveis

- PHP 8.4, Slim 4, Vue 3, Vue Router, PDO/MySQL, Phinx, PHPUnit; manter compatibilidade até decisão explícita em contrário.
- Sem Node.js, npm, Vite, Webpack, transpilers, SFC compilado, bundling ou etapa de build JS.
- Um deploy PHP/Apache da aplicação web; serviços externos necessários podem ser configurados separadamente.
- Sempre `declare(strict_types=1);` em PHP novo; tipar propriedades, parâmetros e retornos.
- Preferir métodos pequenos, nomes explícitos, validação determinística, DTOs apenas quando úteis.
- **Não criar framework próprio**, roteador, container DI, ORM ou abstrações de extensão se bibliotecas existentes resolverem com menos código.
- Não criar interface para cada service. Contratos de repository no Domain, implementações PDO na Infrastructure.
- Domain não importa Slim, PDO, HTTP ou Infrastructure. Application não importa Slim ou PDO. Controllers são adaptadores finos.
- Sem SQL nos controllers; sem regra de negócio nos repositories; sem `new` de dependências de infraestrutura dentro de serviços de domínio.
- Não usar `float` para dinheiro. Não converter input inválido em valores defaults silenciosamente.
- Não alterar contrato HTTP, migrations aplicadas ou dados existentes sem análise e testes.
- Não inserir credenciais, `.env` real, tokens, logs sensíveis ou dump do banco no repositório.
- Não adicionar dependências sem verificar compatibilidade, suporte e necessidade, e informar a decisão.
- Não criar subagentes ou rodadas autônomas/loops de execução sem autorização explícita do usuário.

## Estrutura alvo e direção de dependências

```text
src/Domain/{User,Post,Page}/       entidades, regras, interfaces de repository
src/Application/{Auth,User,Post,Page}/  serviços de aplicação
src/Infrastructure/Http/          controllers, middleware, erros, respostas
src/Infrastructure/Database/      conexão e repositories PDO
src/Infrastructure/{Auth,Mail,View}/ adaptações técnicas
src/Bootstrap/                    configuração, wiring, inicialização
database/{migrations,seeds}/
public/assets/{js,css,vendor}/
tests/{Unit,Integration,Architecture}/
```

Não criar pastas vazias. Controller faz parsing HTTP → chama service → transforma resultado em HTTP. Service orquestra regras/contratos. Entidade protege invariantes. Repository executa persistência. User padrão é customizável pelo projeto; preferir perfis de negócio relacionados, mas permitir adaptar User/Auth quando requerido.

## Fluxo obrigatório para cada execução de Code

1. Ler a spec em `.specs/features/<id>/spec.md`, o plan e a review ativa quando houver, e inspecionar os arquivos relevantes ao escopo solicitado.
2. Executar integralmente **uma** tarefa do plan ou review por sessão; para o skeleton histórico, respeitar o item ou fase explicitamente pedido.
3. Planejar internamente e executar sem pedir confirmações intermediárias durante Code. A escolha entre soluções na etapa Spec é uma decisão do usuário antes da escrita final.
4. Implementar código, migrations, testes e documentação necessários à conclusão.
5. Executar testes específicos durante o trabalho. Para PHP, aplicar `composer format` após editar, verificar com `composer lint` (sem alterar arquivos) e executar `composer quality` completo antes de concluir, quando esses scripts estiverem configurados. Falhas dos gates impedem marcar a tarefa como concluída; registrar bloqueios reais.
6. Não executar operações destrutivas sem autorização. Diante de bloqueios, avançar nos itens independentes.
7. Marcar apenas a tarefa concluída em `.specs/features/<id>/plan.md` após aceite e gate; relatar testes na entrega. Antes da tarefa seguinte, conferir as predecessoras no checklist e no diff, código e testes; checkbox isolado não comprova prontidão. Correções apontadas pela Review são conferidas na reauditoria de `review.md`. Preservar os estados históricos de `todolist.md`.
8. Entregar resumo curto com resultado, testes e pendências. Só encerrar quando o escopo estiver concluído ou realmente bloqueado.

## Orientações para reduzir uso de contexto/créditos

- Trabalhar com uma tarefa delimitada; não reanalisar todo repositório a cada prompt.
- Usar `rg`/`find`/scripts locais para localizar arquivos e padrões antes de abrir grandes conteúdos.
- Não ler `vendor/`, `.idea/`, `.git/`, dependências JS minificadas nem arquivos de lock por inteiro sem necessidade.
- Não fazer edições cosméticas em arquivos fora do escopo.
- Separar rodadas/diffs de formatação de mudanças funcionais; a padronização inicial de todo PHP está em `R001-18` da review ativa.
- Preferir um agente/modelo econômico para tarefas mecânicas; escalar para modelo mais forte apenas em análise complexa, bug difícil ou decisão de segurança.
- Não iniciar subagentes, paralelismos, loops Ralph ou exploração sem limite sem consentimento.
- Evitar saídas extensas, testes repetidos desnecessários e geração de documentação redundante.
- Sem suposições de sucesso: se não executou teste, dizer que não executou.

## Regras de API e segurança

- Preferir attributes de rotas quando integração estável com Slim for validada; métodos `public` sem annotation/attribute não viram endpoints.
- OpenAPI gerada do código e checada contra rotas; evitar duplicação de path quando o custo de integração for razoável.
- Erros de domínio não carregam status HTTP; adapter HTTP faz mapeamento consistente.
- Sessão servidor + cookie HttpOnly/Secure/SameSite; regeneração após login; CSRF em operações mutáveis; autorização sempre no backend.
- Senhas com `password_hash`/`password_verify`. O demonstrador atual não inclui perfis, recuperação de senha ou rate limiting; projetos derivados devem definir recuperação e controles contra abuso antes de exposição pública.
- Conteúdo Markdown do CMS deve ter renderização segura e controles XSS; `v-html` não confiável é proibido.
- Queries parametrizadas com PDO; migrations controladas e reversões seguras.

## Orientações para frontend

- Vue 3 sem build; módulos ES e vendors locais. Páginas sob `public/assets/js/pages/`, chamadas HTTP em `services/`, componentes comuns reaproveitados.
- Não introduzir gerador de templates Vue caseiro nem sistema de estado complexo sem justificativa.
- Fallback SPA só para páginas; `/api/*` desconhecida sempre 404 JSON.
- Roteamento e autenticação no frontend são UX, **não autorização**.

## Comandos atuais do projeto (confirmar ambiente antes de executar)

```bash
composer install
composer format
composer lint
composer test
composer migrate
composer migrate:status
# Docker: conferir README e compose.yaml antes de subir serviços
```

Para mudanças na SPA, `composer test` sozinho não valida sintaxe/carregamento dos módulos ES nem montagem dos componentes Vue. Com a aplicação `app` ativa, execute `./tools/test-all.sh` no host: ele abre as rotas principais em Chrome/Chromium headless e depois executa PHPUnit dentro do container. Chrome/Chromium é necessário no host; não adicionar Node.js ou etapa de build para esse teste.

Não assumir que Compose, MySQL ou SMTP estão ativos. Não executar `migrate:rollback`, reset de banco, `docker compose down -v`, limpeza de volumes ou exclusões sem autorização.

### Acesso ao Docker Compose para agentes

- Este projeto não exige PHP nem Composer instalados no host. Primeiro confira o estado com `docker compose ps -a`.
- Quando o serviço `app` estiver ativo, rode comandos PHP/Composer dentro dele, por exemplo: `docker compose exec -T app composer test`, `docker compose exec -T app php -l src/arquivo.php` e `docker compose exec -T app composer validate`.
- Se o sandbox bloquear o socket do Docker, não conclua que Docker está indisponível: repita o comando usando a opção de execução com permissão escalada (`require_escalated`) e explique o motivo no pedido de aprovação.
- Se `app` não estiver ativo, confira `README.md` e `compose.yaml`; suba apenas os serviços necessários quando isso fizer parte da tarefa autorizada. Não remova ou recrie volumes nem altere dados existentes sem autorização explícita.
- `docker compose exec` só funciona para containers ativos; não use `docker compose down -v`, reset ou rollback destrutivo.

## Política de mudanças

- Não criar PR, commit, publicar, enviar e-mail, alterar ambiente remoto ou implantar sem pedido explícito.
- A instalação atual usa uma migration inicial para `users` e `posts`; alterações de schema para instalações existentes exigem análise e migration própria, preservando dados quando necessário.
- Se houver conflito entre SPEC e código: identificar, explicar e propor menor mudança; não corrigir silenciosamente tudo de uma vez.
- Evitar perguntar detalhes que já constem na spec ou review ativa; esclarecer só decisões indispensáveis.

## Operação e distribuição

- Para desenvolvimento, siga o início rápido do `README.md`; migrations são executadas explicitamente e não no startup.
- Para validar mudanças, use `docker compose exec -T app composer test`; para alterações de SPA use também `./tools/test-all.sh` com Chrome/Chromium no host.
- Se `./tools/test-all.sh` falhar ao iniciar o Chromium com `setsockopt: Operation not permitted` ou erro do Crashpad, repita o mesmo comando no host com permissão escalada (`require_escalated`), justificando que a checagem headless precisa acessar recursos do navegador bloqueados pelo sandbox. Não remova `--no-sandbox` nem desative outras proteções do Chromium para contornar o bloqueio.
- Para produção, use `compose.prod.yaml` e um `.env` externo ao controle de versão, com segredos próprios e `APP_URL` HTTPS. Termine TLS no proxy reverso e não exponha MySQL.
- Leia `docs/customization.md` antes de adaptar User/Auth ou criar domínios. Mantenha contratos no Domain e adaptadores em Infrastructure; não altere internals de HTTP/PDO para acomodar uma entidade específica.
- A sequência histórica das fases está em `.specs/features/001-skeleton/spec.md`. Não repita uma fase concluída; execute apenas o escopo explicitamente pedido. As reviews numeradas do skeleton 001 permanecem históricas; para funcionalidades novas, crie e reaudite um único `review.md`. Siga `.specs/guidelines/quality.md` para gates e mantenha `docs/` exclusivo da documentação da aplicação.
