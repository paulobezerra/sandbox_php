# REVIEW — Simplificação do skeleton e do blog

**Feature:** [feature.md](feature.md) · **Spec:** [spec.md](spec.md) · **Plan:** [plan.md](plan.md) · **Data:** 2026-10-16 · **Estado:** concluída

## Base e limites

Reauditoria após commit e push da branch local `feature/002-simplificacao-skeleton`. `HEAD` é `4dd85a8` (`Simplify skeleton product to Posts and auth`) e coincide com `origin/feature/002-simplificacao-skeleton` (`4dd85a8e67c5985d29f02c863cb3fb949f02f048`). O commit contém 103 arquivos da feature e seus artefatos canônicos. `git diff --stat origin/main...HEAD` confirma o conjunto publicado; alterações globais em `.specs/` e `AGENTS.md` permanecem apenas no working tree e fora do commit. `git diff --check` passou antes do commit. O primeiro acesso SSH foi bloqueado pela configuração local em `/etc/ssh/ssh_config.d/20-systemd-ssh-proxy.conf`; a consulta repetida via `GIT_SSH_COMMAND='ssh -F /dev/null'` confirmou a publicação da branch.

Executado em 2026-10-16 no serviço `app` (PHP 8.4.26, Phinx 0.16.12): `docker compose exec -T app composer quality` passou: lint, 64 testes PHPUnit/310 assertions, cobertura executável 86,57% (464/536; mínimo 80%), PHPStan sem erros e OpenAPI (2 testes/41 assertions). `docker compose exec -T app composer migrate:status` mostrou `20261016000001 CreateInitialSchema` como `up`. `./tools/test-all.sh` inicialmente não iniciou Chromium no sandbox (`setsockopt: Operation not permitted`); repetido com permissão escalada, passou a checagem headless da SPA (Home, âncoras, rotas, login/cadastro, editor e sessão) e PHPUnit (64 testes/310 assertions). Os rastros de 404/405 esperados foram emitidos pelos testes de rotas removidas.

## Requisitos verificados

- **S002-01 / T002-01, T002-02, T002-03, T002-04:** observação do código e testes atuais confirmam composição enxuta, remoção dos endpoints antigos, CRUD de Posts por autoria e migration inicial única. O status do Phinx confirma a migration aplicada; `composer quality` e os testes HTTP/OpenAPI passaram. O código em `PostController` deriva o autor da sessão e usa operações `*Mine`; `PostOwnershipTest` cobre isolamento entre autores. Nenhuma migration foi aplicada durante esta Review.
- **S002-02 / T002-05:** `./tools/test-all.sh` passou nas rotas Home, Blog, Login, registro e Meus Posts, além das rotas institucionais removidas e links/âncoras internos. A Home observada inclui conteúdo de instalação, primeiro uso, arquitetura, operação, segurança, customização e SDD Lite.
- **S002-03 / T002-03:** testes atuais passaram para autorização e rotas. Código observado preserva CSRF no middleware e associa mutações ao ID do usuário autenticado. O gate confirma também a verificação OpenAPI.
- **S002-04 / T002-02:** testes atuais de Auth passaram no gate; a decisão de remover rate limiting integralmente está explicitamente registrada na Spec como exceção aprovada pelo usuário. A aplicação não apresenta fluxo de recuperação.
- **S002-05 / T002-01:** migration inicial está `up`; `CmsPersistenceTest` e `AuthPersistenceTest` passaram dentro do gate. A verificação de status não prova, isoladamente, que um banco vazio recém-criado conteria somente as tabelas esperadas; esse aceite consta como evidência dos testes de persistência, não como recriação executada nesta Review.
- **S002-06 / T002-04, T002-06:** buscas dirigidas em `src`, `public`, `database`, `tools`, `README.md` e `docs` não encontraram referências ativas às capacidades retiradas. As referências a health restantes são healthchecks do próprio Compose; menções na Home descrevem proteções ausentes como ressalva de segurança, e referências no teste de frontend verificam rotas removidas. A Spec registra os componentes removidos e a razão, além dos mecanismos mantidos.
- **S002-07 / T002-06:** README, guia de customização e OpenAPI refletem a superfície atual; a checagem OpenAPI passou. O histórico Git não foi reescrito. Alterações globais de SDD ficaram fora do commit publicado, conforme reavaliação de R-01.
- **S002-08 / T002-05:** verificações do Chromium passaram para conteúdo documental, instalação/primeiro uso, Posts, SDD Lite, âncoras e ausência de links internos para páginas removidas.

Os seis itens do Plan estão marcados concluídos. Nesta Review, os gates atuais passaram; as exceções históricas de cobertura registradas em T002-01 a T002-03 não representam aprovação retroativa, mas a cobertura atual superou o limite.

## Achados

- **R-01 — Alterações fora do escopo misturadas à branch.** **Estado:** resolvido para a branch publicada. **Evidência:** commit `4dd85a8` contém os 4 artefatos canônicos da feature; `git diff --name-only origin/main...HEAD -- .specs/README.md .specs/commands .specs/guidelines .specs/templates AGENTS.md .junie/plans` não retornou arquivos. As alterações globais de `.specs/` e `AGENTS.md` seguem no working tree local, sem staging e sem publicação. **Impacto:** não há mistura dessas alterações processuais no commit auditado; elas continuam pendentes de classificação em seu próprio trabalho. **Correção:** separação feita ao limitar o commit da feature aos arquivos funcionais e artefatos canônicos. **Aceite e teste:** o diff remoto da branch não contém arquivos globais ou planos auxiliares; a referência remota aponta para o mesmo commit local. Verificação concluída nesta reauditoria.

## Conclusão

O comportamento funcional auditado atende aos requisitos verificados; os gates passaram, incluindo cobertura acima do mínimo. R-01 está resolvido para o commit publicado, que não inclui as alterações globais ainda presentes no working tree local. O commit `4dd85a8` foi enviado e confirmado no remoto. Por solicitação expressa do usuário, esta reauditoria ocorreu na mesma conversa da Review anterior; os artefatos e o estado Git foram relidos. O resultado desta reauditoria está atualizado localmente em `review.md`; não foram feitos novos commit/push nem alterações de schema nesta etapa.
