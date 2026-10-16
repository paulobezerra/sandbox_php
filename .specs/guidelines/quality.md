# Quality gate

O gate verifica o escopo implementado; não substitui testes dos riscos específicos. Consulte os scripts atuais em `composer.json` e as instruções de execução em `AGENTS.md`/`README.md` antes de rodar comandos. O projeto pode exigir Docker Compose: confira `docker compose ps -a` e execute Composer dentro do serviço `app` ativo. Não presuma que MySQL, SMTP, Chrome ou o container estejam disponíveis.

Na etapa [Code](workflow.md), registre teste vermelho e verde para comportamento novo/corrigido. Antes de refatorar, use testes de caracterização para capturar o contrato existente que precisa permanecer. O gate final confirma regressões, mas não substitui esse ciclo.

## Durante a implementação

- Rode testes focados no comportamento alterado e em sua fronteira: invariantes no domínio, transação/concorrência no MySQL, contrato/autorização no HTTP e montagem/fluxo no navegador para SPA. Um percentual de cobertura não comprova sozinho que os cenários críticos passaram.
- Para PHP, execute `composer format` após editar e revise seu diff; execute `composer lint` para estilo e sintaxe sem escrita. Mantenha formatação ampla em rodada separada das alterações funcionais. Não edite migrations já aplicadas para mudar schema; use migration aditiva e confira dados existentes em ambiente isolado quando esse for o risco.
- Ao mudar API, atualize atributos, gere `docs/openapi.json` e confira rotas, métodos, corpos, respostas e autenticação. Ao mudar JS/CSS/rotas SPA, execute `./tools/test-all.sh` no host com `app` ativo: PHPUnit isolado não detecta erro de módulo ES ou falha na montagem do Vue.

## Fechamento do escopo

O `composer quality` atual executa, nesta ordem, `composer lint`, PHPUnit com Clover, cobertura de linhas total ≥80% em `src/`, PHPStan nível 6 e `composer openapi:check`. É um gate de **verificação**: `composer format` fica fora dele porque escreve arquivos. Não adicione exclusões artificiais, supressões ou testes que apenas espelham implementação para fazer o número passar; uma exclusão técnica real precisa de justificativa. Execute o gate completo depois das correções e dos testes focados. `composer validate --no-check-publish`, `git diff --check` e `migrate:status` são verificações complementares quando pertinentes; não substituem o gate.

| Mudança | Evidência adicional esperada |
|---|---|
| Contrato HTTP/Auth | Casos positivos e negativos de payload, autenticação, autorização, CSRF, rate limit e sessão pertinentes; OpenAPI sincronizada. |
| Persistência/migration | Teste de integração com dados existentes e falha/concorrência quando afetam integridade; status da migration sem reset de banco. |
| SPA | Navegador headless com app ativo, incluindo fluxo afetado e erros relevantes; `./tools/test-all.sh`. |
| Deploy/configuração | Validação da configuração e smoke representativo em ambiente isolado, por exemplo TLS atrás de proxy se o cookie `Secure` depender disso. |
| Apenas docs/guidelines | Conferir links, exemplos, coerência com scripts atuais e diff; executar gate exigido pelo repositório, registrando se o ambiente impedir. |

Registre comandos **realmente executados**, resultado, versão/ambiente relevantes e limites. Diferencie falha do produto de bloqueio de ambiente (como Chromium/Crashpad no sandbox); repita com a permissão indicada em `AGENTS.md` quando aplicável. Um gate com etapa falhando impede marcar a tarefa concluída. Teste não executado não é sucesso; explique o risco residual e se ele impede o aceite. Não repita gates já aprovados sem mudança ou dúvida concreta. Em uma auditoria somente leitura, use a evidência disponível e indique sua data; não instale ferramentas só para auditar.
