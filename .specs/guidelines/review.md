# Auditorias e correções

A review compara **Feature, Spec, Plan, implementação, diffs, testes e operação**. Crie o próximo `review-NNN.md` na pasta da funcionalidade usando o [template](../templates/review.md). Não sobrescreva uma review anterior nem trate seus checkboxes como prova atual. A auditoria pode ser somente leitura; não instale dependências nem execute migrations para obter evidência sem escopo apropriado. No [fluxo](workflow.md), Review audita e descreve correções para outra sessão Code; não as implementa na mesma execução.

## Estabelecer a base

Leia a feature, a spec, as tarefas concluídas no plan, as decisões posteriores e a review anterior. Inspecione o diff da codificação e os caminhos pertinentes; identifique mudanças pré-existentes, versões, configuração e estado dos serviços necessários aos testes. Registre comando, resultado, data/ambiente quando relevante e o que não foi executado. Diferencie: observação direta do código, teste executado nesta rodada, evidência herdada de uma review anterior e inferência. A aprovação de um teste antigo não prova que ele foi repetido agora.

Cruze contratos entre camadas: rota HTTP e OpenAPI; controller, service e repository; migration e schema atual; API e SPA; configuração pública e proxy; documentação e procedimento de instalação. Para riscos de segurança, siga o dado ou segredo até seu uso e persistência. Um teste unitário da função não substitui verificação HTTP, banco ou navegador quando o defeito depende da integração.

## Registrar achados acionáveis

Use IDs estáveis `RNNN-XX`. Cada achado precisa de:

1. **Classificação e prioridade:** defeito comprovado, risco potencial, divergência de requisito ou melhoria de manutenção, com efeito concreto. Não apresente risco como exploração demonstrada sem evidência.
2. **Evidência:** comportamento reproduzido ou caminho de código verificável, com arquivo/linha ou comando e resultado. Indique a condição necessária ao defeito; se depender de produção, diga qual configuração foi assumida.
3. **Impacto e menor correção suficiente:** contrato ou dado afetado, dependências entre achados e possíveis efeitos de compatibilidade. Evite transformar a review em refatoração geral.
4. **Aceite e testes:** casos de sucesso e falha que distinguem o defeito corrigido do estado antigo. Para autenticação/dados, considere uso único, concorrência, falha transacional, autorização e preservação de registros conforme o caso.

Priorize integridade e segurança, depois contrato e previsibilidade. Um aviso de ferramenta ou teste não executado deve aparecer em “limites” ou “pendências” com seu efeito real; não vira automaticamente achado bloqueante. Marque `[x]` apenas quando o aceite específico e os gates aplicáveis tiverem evidência. Se uma correção for parcial, mantenha o item aberto e registre o que passou e o que falta.

## Reauditar sem perder a trilha

A review seguinte referencia cada ID anterior, seu estado e a evidência usada. Para itens agrupados, o grupo só pode ser “resolvido” se todos os IDs estiverem comprovados; destaque exceções individualmente. Registre achados novos com IDs da nova review. Não reescreva a conclusão histórica: explique a mudança de requisito ou a nova informação e cite a decisão vigente.

Ao consolidar registros, reconcilie afirmações aparentemente conflitantes. Exemplo da `review-001`: o smoke TLS passou em L01 e não foi repetido em L04; isso é diferente de “nunca executado”. Informe **quando** e **em qual ambiente** um teste ocorreu. Feche a reauditoria com estado claro: bloqueios reais, verificações opcionais pendentes e próximo escopo. Uma afirmação de “gate aprovado” deve apontar para os resultados e limites descritos em [quality.md](quality.md).
