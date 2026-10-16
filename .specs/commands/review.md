# SDD Lite · Review

Audite um incremento depois de Code ou reaudite correções pedidas. Leia `AGENTS.md`, [workflow](../guidelines/workflow.md), [review](../guidelines/review.md), [quality](../guidelines/quality.md), [template](../templates/review.md), feature/spec/plan e reviews anteriores. Para o skeleton histórico, use os artefatos existentes. Esta etapa registra evidência e correções; não implementa correções na mesma execução.

Aplique antes de começar a [premissa máxima de um comando por chat novo](../guidelines/workflow.md#premissa-máxima-um-comando-por-chat-novo). Encerre este chat após Review.

## 1. Fixar a base da auditoria

1. Identifique o ID ou incremento auditado e confira no registro desse Code no plan ou na review ativa a validação explícita do usuário para Review, com nome e data do SO. Se faltar, aplique a [regra de entrada](../guidelines/workflow.md#premissa-de-entrada-validação-explícita-do-passo-anterior) antes de prosseguir. Identifique os requisitos e aceites vigentes e o diff real. Separe alterações preexistentes das feitas para o incremento. Confirme quais decisões posteriores substituíram contratos antigos sem reescrever a história.
2. Cruze requisito → tarefa → código → teste. Procure requisito sem entrega, tarefa marcada sem prova, implementação divergente e regressão de contrato. Siga as fronteiras tocadas: rota/OpenAPI, controller/service/repository, migration/dados, API/SPA, autorização/sessão e configuração/operação quando pertinentes.

## 2. Produzir evidência proporcional

1. Execute verificações específicas que possam confirmar ou refutar os riscos principais, conforme ambiente e autorização. Registre comando, resultado e ambiente/data. Separe teste executado agora, observação direta do código, evidência herdada de review anterior e inferência. Teste antigo não é teste repetido; teste não executado não é aprovado.
2. Para uma falha, anote condição de reprodução e efeito. Para uma ausência de evidência, indique o que permanece incerto e se isso impede o aceite; não a rotule automaticamente como defeito comprovado. Priorize integridade e segurança, depois contrato e previsibilidade.

## 3. Registrar a conclusão

1. Crie o próximo `review-NNN.md` usando o template e preserve reviews anteriores. Cada achado recebe `RNNN-XX`, classificação, prioridade, evidência/condição, impacto, menor correção suficiente, aceite que distingue antes/depois, testes e dependências. O escopo da correção deve caber em uma sessão Code. Reavalie IDs anteriores com referência explícita à evidência atual ou herdada.
2. Marque requisito da spec somente quando o comportamento integrado estiver comprovado. Se não houver achados, registre essa conclusão e seus limites. Deixe a validação da Review para Code das correções pendente até declaração explícita do usuário. Entregue o link, estado do incremento, verificações e ordem sugerida das correções. Pare antes de Code.
