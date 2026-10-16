# SDD Lite

Cada funcionalidade nova usa quatro arquivos em `features/NNN-nome/`: `feature.md`, `spec.md`, `plan.md` e `review.md`. O Plan contém o checklist que Code atualiza; a Review registra auditoria e reauditorias no mesmo arquivo. O [skeleton histórico](features/001-skeleton/spec.md) e suas [auditorias antigas](features/001-skeleton/review-001.md) permanecem como estão, sem conversão retroativa.

Fluxo: **FEATURE → SPEC → PLAN → CODE → REVIEW**, com **CODE → REVIEW** para correções. Leia o [procedimento e as fronteiras de cada passo](guidelines/workflow.md). A Spec compara alternativas técnicas reais e espera a escolha do usuário quando houver tradeoffs relevantes; uma solução única evidente pode ser justificada e seguida diretamente. Code executa um ID por sessão com testes e gate, marca a tarefa no Plan só após comprová-la e não pede validação do usuário a cada iteração. Review audita o diff e registra correções em `review.md`.

**Premissa multiagente:** as regras do SDD Lite vivem nos comandos e artefatos comuns; os adaptadores de Codex, Claude Code e Junie CLI apenas os tornam acessíveis. Nenhum controle do fluxo pode depender exclusivamente de um agente.

**Quebra do fluxo:** o agente avisa qual regra seria quebrada, explica o risco técnico e recomenda o caminho previsto. O aviso não bloqueia o usuário; se ele insistir explicitamente, o agente prossegue no escopo autorizado e registra a exceção na entrega, sem afirmar que validações ou gates pendentes foram cumpridos.

**Isolamento recomendado:** prefira um chat novo para cada comando SDD Lite ou ID de Code. Se pedir outro comando na conversa já usada, o agente avisará que o contexto acumulado pode comprometer a qualidade e recomendará um chat novo. O aviso não bloqueia: diante de insistência explícita, o agente prossegue com a etapa pedida, relê os artefatos e registra na entrega que o isolamento foi quebrado por autorização explícita. Veja a [regra de isolamento do fluxo](guidelines/workflow.md#isolamento-recomendado-um-comando-por-chat-novo).

Feature, Spec e Plan pedem validação explícita antes da etapa seguinte, registrada no próprio artefato com nome do usuário e data obtida do SO. Entre tarefas de Code e antes de Review, não há nova validação do usuário: o agente confere checklist, pré-requisitos, diff, código e testes. Veja a [regra de entrada](guidelines/workflow.md#premissa-de-entrada-validação-explícita-do-passo-anterior).

Feature cria a branch `feature/NNN-nome` antes de escrever o artefato (`fix/NNN-nome` para conserto independente). Review confere o estado local e, quando possível, o remoto; publicação não comprovada é limite registrado, sem commit ou push automático. Veja a [regra de branch e publicação](guidelines/workflow.md#branch-da-funcionalidade-e-publicação-antes-da-review).

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
