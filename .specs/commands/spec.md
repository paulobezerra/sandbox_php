# SDD Lite · Spec

Transforme uma `feature.md` em solução técnica escolhida e verificável. Leia `AGENTS.md`, [workflow](../guidelines/workflow.md), [specification](../guidelines/specification.md), [implementation](../guidelines/implementation.md) e [template](../templates/spec.md). Uma invocação executa somente Spec; não inicia Plan/Code.

Aplique antes de começar a [premissa máxima de um comando por chat novo](../guidelines/workflow.md#premissa-máxima-um-comando-por-chat-novo). Encerre este chat após concluir Spec; a escolha técnica necessária pertence à execução atual.

## 1. Verificar a base e a qualidade da entrada

1. Identifique a feature pelo ID ou contexto. Se houver candidatas indistinguíveis, peça o ID. Confira no `feature.md` a validação explícita do usuário para Spec, com nome e data do SO. Se faltar, aplique a [regra de entrada](../guidelines/workflow.md#premissa-de-entrada-validação-explícita-do-passo-anterior) antes de prosseguir. Leia a feature e inspecione os arquivos, contratos, testes, dados e consumidores diretamente afetados. Distingua comportamento atual verificado, hipótese e comportamento desejado; registre divergências entre a spec histórica e o código real.
2. Procure lacunas que impedem uma decisão: ator, permissão, estado inicial, ação, resultado, falha, dados a preservar ou limite de escopo. Resolva com o usuário apenas escolhas de produto indispensáveis. Não preencha silêncio com um contrato inventado.

## 2. Escolha técnica do usuário

1. Apresente duas ou três soluções viáveis, incluindo manter o desenho atual quando isso atender à feature. Compare segurança, integridade de dados, desempenho, compatibilidade/migração, simplicidade, manutenção e operação; marque o que não se aplica. Explique o custo e o risco relevante de cada uma, cite a evidência disponível e recomende uma opção sem somar notas mecanicamente.
2. Peça que o usuário escolha ou ajuste a solução e **aguarde a decisão**. Se a escolha já foi expressa nesta conversa, use-a sem repetir o pedido. Uma recomendação do agente não é aprovação. Antes da escolha, não escreva `spec.md` final nem avance para Plan/Code.

## 3. Especificar e conferir

1. Após a escolha, escreva `spec.md` com a decisão, a data, o motivo e os tradeoffs aceitos. Dê IDs estáveis aos requisitos. Para cada comportamento relevante, descreva estado inicial, ação e resultado observável, incluindo sucesso, entrada inválida, acesso indevido e falha parcial quando pertinentes. Defina contratos, invariantes, transições de dados, migração e consumidores afetados no nível necessário ao aceite.
2. Ligue cada requisito a uma forma de verificação proporcional ao risco. Confirme coerência com feature, stack e regras do repositório, código atual e contratos existentes. Se uma lacuna alterar o desejo de produto, atualize a feature com a decisão registrada; se for detalhe rotineiro, resolva na spec. Não marque checkbox de implementação nesta etapa.
3. Deixe a validação da Spec para Plan pendente até a declaração explícita do usuário; a escolha técnica anterior não a preenche. Entregue o link, a escolha registrada e qualquer decisão indispensável ainda aberta. Pare antes de Plan/Code.
