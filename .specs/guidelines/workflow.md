# Fluxo de desenvolvimento por funcionalidade

O ciclo de uma funcionalidade nova é **Feature → Spec → Plan → Code → Review**, seguido de **Code → Review** enquanto houver correções. Cada execução trata apenas o passo invocado. Os artefatos ficam em `features/NNN-nome/`; `NNN` é o próximo identificador livre, sem renumerar trabalhos históricos. Leia `AGENTS.md` e as guidelines pertinentes. O skeleton `001` é histórico e não precisa receber `feature.md` ou `plan.md` retroativamente.

Os cinco procedimentos executáveis ficam em [commands](../commands/). Os arquivos de descoberta específicos de cada agente apontam para eles; uma mudança de comportamento do fluxo deve ser feita primeiro aqui e no comando correspondente, não copiada entre adaptadores.

## Premissa máxima: um comando por chat novo

**Cada comando do SDD Lite deve começar em um chat novo, com contexto de conversa zerado, e executar somente o passo invocado.** O estado necessário deve vir dos artefatos versionados e do pedido atual, não da memória de um chat anterior. Ao concluir ou bloquear Feature, Spec, Plan, Code ou Review, encerre a atuação do comando neste chat. Não inicie o passo seguinte, outra rodada do mesmo passo nem outro ID de Code na mesma conversa, mesmo que o usuário peça explicitamente para continuar. Oriente-o a abrir outro chat e executar ali o próximo comando; explique que a sessão atual já está comprometida para uma nova execução do SDD Lite. Uma resposta do usuário necessária para concluir o comando atual, como a escolha técnica em Spec, permanece na mesma execução e não autoriza iniciar outro comando.

## Premissa de entrada: validação explícita do passo anterior

Antes de executar Spec, Plan, Code ou Review, confira a validação explícita do usuário para a entrega imediatamente anterior: Feature → Spec, Spec → Plan, Plan → primeiro Code, Code do incremento → Review e Review → Code das correções. A validação deve estar registrada no artefato anterior, em campo próprio com **nome informado pelo usuário** e **data obtida do sistema operacional no momento do registro**. Para Code, registre a validação do incremento no ID executado dentro do plan ou da review; a escolha técnica feita durante Spec não substitui a validação da Spec concluída. Não presuma validação a partir de checkbox, gate, ausência de objeções ou pedido genérico para avançar.

Se o registro faltar, avise qual entrega precisa de validação e espere o usuário dizer explicitamente que a validou. Peça o nome para o registro caso ainda não tenha sido informado; execute `date +%Y-%m-%d` no SO no momento da declaração, preencha o campo no artefato anterior e então prossiga apenas com o comando atual. Não use a data dita pelo usuário nem a de criação do arquivo como substituta. Se não houver validação explícita, pare antes de executar o passo. Em artefatos históricos sem esse campo, acrescente-o quando a validação for declarada, sem inventar aprovações retroativas. Feature inicia o ciclo e não exige validação anterior.

Entre IDs de Code, a ordem é a do plan ou da review ativa. A prontidão de uma tarefa **não depende de nova aprovação do usuário**: o agente deve confirmar no código, nos testes e nos registros que todos os IDs predecessores e pré-requisitos funcionais foram implementados e verificados. Um checkbox isolado não basta. Se a tarefa pedida ainda não estiver pronta, explique a dependência real e não avance para ela; escolha somente um ID elegível na ordem definida quando o comando não trouxer ID.

| Passo | Entrada | Saída | Fronteira |
|---|---|---|---|
| Feature | Desejo de negócio/produto | `feature.md` com problema, público, valor e limites | Não escolher arquitetura nem implementar. |
| Spec | Feature validada pelo usuário e base atual do código | `spec.md` com solução escolhida, contratos e aceite | Antes do arquivo final, apresentar opções e matriz; esperar a escolha do usuário. |
| Plan | Spec validada pelo usuário e código atual | `plan.md` com tarefas ordenadas e entregáveis | Cada tarefa é um incremento utilizável e verificável, não apenas preparação. |
| Code | Um ID aberto do plan ou review, após validação do Plan ou Review | Código, testes, docs e estado do ID | Fazer uma tarefa por sessão com TDD e gate; validar pré-requisitos reais e não iniciar a próxima. |
| Review | Code do incremento validado pelo usuário, spec, plan e diffs | Próximo `review-NNN.md` com evidência e correções | Auditar; não implementar correções na mesma execução. |

## Decisão técnica antes da spec

Na etapa Spec, examine a Feature, os arquivos relevantes e as restrições reais. Apresente duas ou três soluções viáveis, inclusive manter o desenho atual quando isso for uma opção real. Compare em uma matriz **segurança, integridade de dados, performance, compatibilidade/migração, simplicidade de implementação, manutenção/padrões e operação**, marcando “não aplicável” quando necessário. Mostre tradeoffs e uma recomendação fundamentada; não faça uma soma mecânica de notas. Se uma opção for inviável por restrição inegociável, identifique-a fora da disputa. Peça ao usuário que escolha ou ajuste a solução. **Não escreva `spec.md` final nem avance para Plan/Code antes dessa decisão.** Após a escolha, registre a decisão, alternativas relevantes e motivo na spec; dúvidas não essenciais podem ser resolvidas pelo agente.

Antes dessa comparação, identifique ambiguidades e conflitos de produto que impedem comparar as soluções. Antes de Code, confira a cadeia requisito → tarefa → aceite/teste e resolva lacunas no artefato que as introduziu. Essas verificações pertencem aos cinco passos existentes; não criam comandos extras nem aprovações automáticas.

## Tarefas com valor entregue

O Plan transforma requisitos em fatias verticais. Uma tarefa deve ter entrada, comportamento completo, aceite demonstrável, testes e gate. Inclua banco, backend, API, SPA, documentação e migração que sejam necessários **àquele incremento**. Exemplo: “administrador envia um arquivo, recebe validação e consegue consultá-lo” pode incluir formulário e upload com persistência e testes; “criar branch”, “adicionar só uma migration” ou “rodar formatter” não são tarefas funcionais isoladas. Uma base técnica pode entrar como parte da primeira tarefa que a usa. Se uma fatia exigir dependência anterior, registre o vínculo e um resultado utilizável em cada etapa. Não marque uma feature concluída enquanto seus requisitos permanecerem abertos.

## Código e verificação

Na etapa Code, selecione um ID explícito. Se o usuário não indicar ID, escolha primeiro a correção aberta de maior prioridade e pronta na review ativa; caso não exista, a primeira tarefa pronta do plan. Declare a escolha. Para mudança de comportamento, faça TDD: escreva o teste que falha pelo motivo esperado, implemente o mínimo para passar e refatore mantendo os testes verdes. Antes de refatorar comportamento existente, registre-o com testes de caracterização nos pontos afetados. Para ajustes apenas documentais/configuração, use uma verificação que possa falhar antes da edição quando possível; não invente testes triviais. Execute o gate de [qualidade](quality.md) e as verificações adicionais do risco. Marque o ID `[x]` somente com aceite e gate aprovados; em falha real, deixe aberto e registre o estado sem iniciar outra tarefa.

## Review e continuidade

Na Review, leia o escopo pedido, os diffs reais e testes, incluindo alterações pré-existentes para não atribuí-las à tarefa errada. Confira o resultado contra a Feature, a Spec e o Plan, além de segurança, dados, contratos e experiência no navegador quando relevantes. Execute verificações proporcionais e registre o que foi observado nesta rodada ou herdado. Crie o próximo número de review sem sobrescrever as anteriores. Cada defeito ou requisito faltante vira um item `RNNN-XX` com evidência, impacto, aceite, teste e dependências, pronto para uma sessão Code. Marque requisitos da spec somente quando o resultado integrado estiver comprovado; se não houver achados, registre isso com os limites da auditoria. Não apresente falta de evidência como defeito comprovado.
