# SDD Lite

Cada funcionalidade nova tem uma pasta `features/NNN-nome/` com `feature.md`, `spec.md`, `plan.md` e auditorias imutáveis `review-001.md`, `review-002.md` etc. [Skeleton](features/001-skeleton/spec.md) é a spec histórica migrada sem alteração de conteúdo; sua [primeira auditoria](features/001-skeleton/review-001.md) registrou o ciclo de correções e a [segunda](features/001-skeleton/review-002.md) registrou a reauditoria. Ele não precisa receber os novos artefatos retroativamente. Referências antigas a `SPEC.md` dentro da spec são parte do registro histórico e apontam agora para esse arquivo.

Fluxo: **FEATURE → SPEC → PLAN → CODE → REVIEW**, com **CODE → REVIEW** para correções. Leia o [procedimento e as fronteiras de cada passo](guidelines/workflow.md). A Spec exige comparação de soluções e escolha do usuário antes da versão final. Code executa um ID por sessão com TDD, teste de caracterização quando necessário e gate. Review audita diffs e cria correções com IDs estáveis, sem sobrescrever reviews anteriores. Só marque concluído após critérios de aceite e gates passarem.

**Premissa máxima:** execute cada comando em um chat novo, com contexto de conversa zerado. Ao encerrar um comando, abra outro chat para o próximo passo ou ID de Code; insistir na mesma conversa não libera a continuação. Veja a [regra de isolamento do fluxo](guidelines/workflow.md#premissa-máxima-um-comando-por-chat-novo).

Antes de cada passo após Feature, o usuário deve ter validado explicitamente a entrega anterior. Os templates reservam um campo com nome do usuário e data obtida do SO; se faltar, o agente deve pedir a declaração de validação antes de prosseguir. Para outra tarefa de Code, a prontidão depende da sequência e dos pré-requisitos comprovados pelo agente no código, sem nova aprovação do usuário. Veja a [regra de entrada](guidelines/workflow.md#premissa-de-entrada-validação-explícita-do-passo-anterior).

Use [diretrizes de especificação](guidelines/specification.md), [implementação](guidelines/implementation.md), [auditoria](guidelines/review.md) e [qualidade](guidelines/quality.md) com os templates de [feature](templates/feature.md), [spec](templates/spec.md), [plan](templates/plan.md) e [review](templates/review.md). `docs/` contém apenas documentação da aplicação. `todolist.md`, quando presente, permanece histórico.

## Comandos e fonte única

As instruções operacionais das cinco etapas estão em [`commands/`](commands/). [`guidelines/`](guidelines/) define as regras compartilhadas, [`templates/`](templates/) define os artefatos, e [`features/`](features/) guarda o trabalho de cada funcionalidade. `AGENTS.md` mantém as regras gerais do repositório. Edite o comando ou a guideline em `.specs/` para mudar o fluxo; os arquivos dos agentes são apenas pontos de entrada e não devem repetir regras.

| Etapa | Codex | Claude Code | Junie CLI | Instrução central |
|---|---|---|---|---|
| Feature | `$sdd-feature` | `/sdd-feature` | `/sdd-feature` | [feature.md](commands/feature.md) |
| Spec | `$sdd-spec` | `/sdd-spec` | `/sdd-spec` | [spec.md](commands/spec.md) |
| Plan | `$sdd-plan` | `/sdd-plan` | `/sdd-plan` | [plan.md](commands/plan.md) |
| Code | `$sdd-code` | `/sdd-code` | `/sdd-code` | [code.md](commands/code.md) |
| Review | `$sdd-review` | `/sdd-review` | `/sdd-review` | [review.md](commands/review.md) |

Passe o ID da funcionalidade ou tarefa quando existir: `$sdd-code T002-03`, `/sdd-code T002-03` no Claude Code, ou `/sdd-code T002-03` no Junie CLI. Junie também aceita texto livre. Os adaptadores ficam em `.agents/skills/` (Codex), `.claude/commands/` (Claude Code) e `.junie/commands/` (Junie CLI). A descoberta e a sintaxe desses pontos de entrada dependem da versão da ferramenta; reinicie a sessão se arquivos recém-criados não aparecerem. Qualquer agente sem comando nativo pode receber diretamente “execute `.specs/commands/<etapa>.md` para `<ID>`”.

As melhorias adotadas foram a revisão de ambiguidades antes da solução, inspirada na etapa de [clarificação do Spec Kit](https://github.github.com/spec-kit/reference/agentic-sdd.html) e na [análise de requisitos do Kiro](https://kiro.dev/docs/specs/feature-specs/); a ligação explícita requisito → tarefa → verificação, presente nos fluxos do [Spec Kit](https://github.github.com/spec-kit/reference/agentic-sdd.html) e do [OpenSpec](https://github.com/Fission-AI/OpenSpec/blob/main/docs-lab/reference/schemas/spec-driven/index.md); e a conferência final de coerência entre artefatos. Foram incorporadas aos cinco passos existentes, mantendo o limite de uma tarefa por execução de Code e a decisão técnica do usuário antes da Spec final.
