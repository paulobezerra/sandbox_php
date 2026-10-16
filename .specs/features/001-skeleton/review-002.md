# REVIEW-002 — Reauditoria do Skeleton PHP/Slim/Vue

**Auditoria anterior:** [review-001.md](review-001.md) · **Spec:** [spec.md](spec.md) · **Data:** 15/10/2026 · **Estado:** L04 aprovado

Esta revisão verifica os 18 achados R001 contra as correções e evidências já registradas em review-001, além do gate L04 executado nesta rodada. Ela não altera o histórico de R001 nem os checkboxes da spec.

## Reauditoria dos achados R001

| Achado | Estado | Evidência consultada nesta reauditoria |
|---|---|---|
| R001-01 a R001-05 | Resolvidos conforme review-001 | Fluxo por códigos de recuperação, transação, limites, cookie/configuração de proxy e CSRF seguem cobertos pela suíte; `./tools/test-all.sh` aprovou. Teste de TLS em proxy real continua fora do ambiente local. |
| R001-06 a R001-13 | Resolvidos conforme review-001 | Composição modular, cache de rotas, fronteiras e validação HTTP, invariantes, OpenAPI e SPA seguem cobertos pela suíte. O teste OpenAPI passou com 54 assertions; Chromium aprovou as rotas e fixture de CSRF/XSS. |
| R001-14 | **Resolvido em L04** | PHPStan 2.3.1 foi instalado como ferramenta de desenvolvimento e passou no nível 6 sem avisos. `composer quality` passou por lint, 102 testes/479 assertions, cobertura de 80,36% (925/1151) sem exclusões, PHPStan e OpenAPI. Hashes confirmaram que o gate não altera arquivos; o teste isolado confirmou falha sem escrita e correção via format. |
| R001-15 | Resolvido conforme review-001 | O ensaio de onboarding em cópia limpa, com banco temporário e sem tocar no banco principal, permanece documentado na revisão anterior; não foi repetido em L04. |
| R001-16 | Resolvido conforme review-001 | A invalidação de sessões por versão de credencial segue coberta pelos testes da suíte e pelo fluxo de recuperação já descrito em review-001. |
| R001-17 | Resolvido conforme review-001 | Reserva de slugs e teste arquitetural de colisões seguem presentes e a suíte passou. |
| R001-18 | Resolvido conforme review-001 | PHP-CS-Fixer 3.95.27/PHP 8.4, `composer format` e `composer lint` permanecem configurados; lint passou e hashes PHP antes/depois ficaram iguais. |

## Resultado do quality gate L04

- `composer validate --no-check-publish`: válido; aviso de `license` ausente permanece.
- `composer lint`: aprovado, sem alteração nos arquivos PHP medidos por hash.
- `composer openapi:check`: aprovado (1 teste, 54 assertions), conferindo as rotas registradas, respostas, autenticação administrativa e request bodies documentados.
- PHPStan 2.3.1 é uma ferramenta de desenvolvimento: verifica o código sem executar o site. Foi configurado no nível 6, os 34 avisos encontrados foram corrigidos sem desativar regras, e a análise passou sem erros.
- `composer quality`: passou em todas as etapas — lint, PHPUnit (102 testes/479 assertions), cobertura de 80,36% e nenhuma exclusão, PHPStan e OpenAPI (1 teste/54 assertions).
- Hashes de todos os arquivos fora de `.git` e `vendor` permaneceram idênticos antes/depois de `composer quality`. Em cópia isolada, um PHP mal formatado fez `composer lint` e `composer quality` falharem com exit 8 sem alterar o arquivo; `composer format` corrigiu-o e o lint passou.
- `./tools/test-all.sh`: aprovado após repetição escalada exigida pelo erro de Crashpad no sandbox; 14 verificações Chromium e PHPUnit (102/479) passaram.
- O teste de integração Admin/CMS cobre sucesso HTTP para criar, listar, abrir, editar, publicar, ler publicamente, despublicar e excluir Posts e Pages, além da prévia Markdown; CSRF acompanha mutações.

## Pendências e próximo passo

PHPStan é uma ferramenta de desenvolvimento que lê o código PHP sem executar o site e aponta possíveis erros, como valores de tipo inesperado. O “nível 6” é o grau de rigor da verificação. Ela não é necessária para o site funcionar; faz parte deste gate porque L04 a incluiu como checagem obrigatória antes de dar R001-14 como concluído.

L04 e R001-14 foram concluídos. PHPStan não faz parte do site em produção; fica disponível apenas no ambiente de desenvolvimento para ajudar a encontrar erros antes da publicação. O ensaio real de TLS atrás de proxy ainda não foi executado neste ambiente.
