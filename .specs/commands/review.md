# SDD Lite · Review

Audite a funcionalidade após as tarefas de Code ou reaudite correções pedidas. Leia `AGENTS.md`, [workflow](../guidelines/workflow.md), [review](../guidelines/review.md), [quality](../guidelines/quality.md), [template](../templates/review.md), Feature, Spec e Plan. Para o skeleton histórico, use os artefatos existentes. Esta etapa registra evidência e correções em `review.md`; não implementa correções na mesma execução.

Aplique antes de começar a [regra multiagente de isolamento do chat](../guidelines/workflow.md#isolamento-recomendado-um-comando-por-chat-novo). Se esta conversa já foi usada para outro comando, avise do risco à qualidade, recomende um chat novo e não bloqueie o usuário; se ele insistir explicitamente, prossiga e reafirme na entrega que autorizou a quebra do fluxo. Encerre a etapa após Review.

## 1. Fixar a base da auditoria

1. Identifique o escopo auditado e confira o checklist do Plan, as evidências anotadas e o diff real. **Review não exige validação de cada iteração de Code.** Separe alterações preexistentes das feitas para a funcionalidade. Confirme quais decisões posteriores substituíram contratos antigos sem reescrever Feature ou Spec.
2. Confira branch local, `git status --short`, `HEAD` e o remoto quando acessível. Registre o que foi possível verificar; ausência de commit, push ou acesso à rede é limite da auditoria, sem criar tarefa automática de publicação. Não faça commit nem push.
3. Cruze requisito → tarefa → código → teste. Procure requisito sem entrega, checkbox sem prova, implementação divergente e regressão de contrato. Siga as fronteiras tocadas quando pertinentes.

## 2. Produzir evidência proporcional

1. Execute verificações específicas que possam confirmar ou refutar os riscos principais, conforme ambiente e autorização. Registre comando, resultado e ambiente/data. Separe teste executado agora, observação direta do código, evidência herdada de review anterior e inferência. Teste antigo não é teste repetido; teste não executado não é aprovado.
2. Para uma falha, anote condição de reprodução e efeito. Para uma ausência de evidência, indique o que permanece incerto e se isso impede o aceite; não a rotule automaticamente como defeito comprovado. Priorize integridade e segurança, depois contrato e previsibilidade.

## 3. Registrar a conclusão

1. Crie `review.md` na primeira auditoria usando o template; nas reauditorias, atualize o mesmo arquivo, preservando IDs e decisões ainda relevantes. Cada achado recebe ID estável, evidência, impacto, correção suficiente, aceite e teste. Reavalie achados anteriores no próprio arquivo com a evidência atual. Não execute commit nem push durante Review.
2. **Não altere Feature, Spec ou Plan.** Registre em `review.md` requisitos comprovados e pendentes; se não houver achados, registre a conclusão e seus limites. Não peça validação do usuário para cada correção ou rodada de Code. Entregue o link, o estado e as verificações. Pare antes de Code.
