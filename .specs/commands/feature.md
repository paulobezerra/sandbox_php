# SDD Lite · Feature

Transforme uma necessidade de produto em `.specs/features/NNN-nome/feature.md`. Leia `AGENTS.md`, [workflow](../guidelines/workflow.md) e [template](../templates/feature.md). Esta etapa define **por que**, **para quem** e **qual resultado**; não escolhe arquitetura nem inicia Spec/Plan/Code.

Aplique antes de começar a [regra multiagente de isolamento do chat](../guidelines/workflow.md#isolamento-recomendado-um-comando-por-chat-novo). Se esta conversa já foi usada para outro comando, avise do risco à qualidade, recomende um chat novo e não bloqueie o usuário; se ele insistir explicitamente, prossiga e reafirme na entrega que autorizou a quebra do fluxo. Encerre a etapa após Feature.

## 1. Entender o pedido

1. Identifique o usuário ou operador afetado, o problema atual, a consequência e o resultado desejado. Separe fatos observados no produto de hipóteses. Consulte apenas o contexto necessário; se a ideia disser respeito a comportamento já especificado, localize a feature/spec existente antes de abrir outro ID.
2. Delimite o que entra agora e o que fica fora. Registre restrições de produto e compatibilidade conhecidas, sem converter detalhes de implementação em requisitos de produto. Quando houver caminhos alternativos de produto, esclareça somente a escolha que impede definir o resultado; questões secundárias podem permanecer abertas e explícitas.

## 2. Preparar a branch, escrever e conferir

1. Use o ID/pasta fornecido pelo usuário ou o próximo `NNN` livre em `.specs/features/`; preserve arquivos existentes. Confira a branch e o estado do workspace. Antes de escrever `feature.md`, crie e selecione `feature/NNN-nome` a partir da base de integração, ou `fix/NNN-nome` para um conserto independente. Reutilize a branch correta se já existir; não misture alterações de outro trabalho. Registre o nome efetivo da branch no artefato. Se a Feature já foi entregue sem branch registrada, estabeleça a branch e complete esse metadado no próprio `feature.md`, sem alterar as decisões.
2. Preencha o template com necessidade, resultado, limites, sinais observáveis de sucesso e questões ainda abertas. Um sinal de sucesso deve permitir reconhecer o efeito no usuário ou operador, sem prescrever classe, biblioteca ou teste técnico.
3. Faça a checagem final: alguém que não participou da conversa consegue dizer quem ganha o quê, o que não será entregue e quais escolhas de produto faltam? Remova solução técnica antecipada, promessa vaga e duplicação de feature. Deixe o campo de validação pendente no próprio `feature.md`; não presuma aprovação pela criação da Feature. Quando o usuário validar, preencha apenas esse campo com declaração, nome e data do SO. Entregue o link, a branch, o resultado esperado e as questões pendentes. Pare nesta etapa.
