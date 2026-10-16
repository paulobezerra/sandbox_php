# SDD Lite · Plan

Transforme uma `spec.md` escolhida em tarefas `TNNN-XX` entregáveis. Leia `AGENTS.md`, [workflow](../guidelines/workflow.md), [quality](../guidelines/quality.md), [template](../templates/plan.md), feature, spec e o código/testes pertinentes. Uma invocação executa somente Plan; não implementa tarefas.

Aplique antes de começar a [regra multiagente de isolamento do chat](../guidelines/workflow.md#isolamento-recomendado-um-comando-por-chat-novo). Se esta conversa já foi usada para outro comando, avise do risco à qualidade, recomende um chat novo e não bloqueie o usuário; se ele insistir explicitamente, prossiga e reafirme na entrega que autorizou a quebra do fluxo. Encerre a etapa após Plan.

## 1. Conferir pré-requisitos

1. Confira a validação explícita do usuário para Plan no campo de `spec.md`, com nome e data do SO; a escolha técnica não a substitui. Se faltar, aplique a [regra de entrada](../guidelines/workflow.md#premissa-de-entrada-validação-explícita-do-passo-anterior). Verifique se a escolha técnica foi registrada, se os requisitos têm IDs e se os aceites são observáveis. Confira contradições com feature, código atual e regras do repositório. Não altere Feature nem Spec, salvo o registro da validação no próprio artefato validado: registre a divergência e o impacto no Plan, peça validação explícita do usuário para mudança de contrato e mantenha dependências afetadas pendentes até a decisão.
2. Faça um inventário curto dos requisitos, consumidores e riscos que exigem trabalho: dados/migration, domínio, API/OpenAPI, SPA, segurança e operação quando aplicáveis. Não gere tarefas para camadas que a funcionalidade não toca.

## 2. Dividir em incrementos

1. Ordene tarefas por dependência e risco. Cada ID deve entregar um comportamento utilizável e verificável, com pré-condição, sucesso, falha pertinente e preservação do contrato antigo. Inclua na mesma tarefa tudo que torna aquela fatia completa: migration, backend, API, SPA, testes e documentação necessários. Preparação técnica entra na primeira tarefa que a usa; branch, scaffolding isolado e formatter não são incrementos funcionais.
2. Cite os requisitos atendidos por cada tarefa e as dependências entre IDs. Indique o teste que deve falhar antes da implementação e o gate aplicável; para mudanças não comportamentais, indique uma verificação real em vez de um teste trivial. Se uma tarefa for grande demais para concluir e validar em uma sessão Code, divida-a em fatias com resultado utilizável próprio.

## 3. Auditar o plano antes de entregar

1. Confira requisito → tarefa → aceite/verificação: nenhum requisito implementável deve ficar sem tarefa, e nenhuma tarefa deve existir sem resultado da spec. Confira ordem de dependências e riscos de integração final, sobretudo preservação de dados, contrato HTTP, autorização e navegador quando afetados.
2. Escreva `plan.md` a partir do template, com IDs estáveis. Deixe o campo de validação do Plan para Code pendente até declaração explícita do usuário; depois registre-a apenas nesse campo do `plan.md`, com nome e data do SO. Entregue o link, a sequência de execução e as lacunas que ainda impeçam Code. Pare antes de implementar.
