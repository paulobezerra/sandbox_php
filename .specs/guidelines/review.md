# Auditoria e correções

A Review compara Feature, Spec, checklist do Plan, código, diffs, testes e operação. Para uma funcionalidade nova, crie `review.md` na primeira auditoria e atualize o mesmo arquivo nas reauditorias. Os arquivos numerados do skeleton 001 são históricos e permanecem como estão. Review audita e descreve correções; Code as implementa em outra execução.

## Base e evidência

Confira tarefas marcadas no Plan contra diff, código e testes. Um checkbox isolado não comprova entrega. Separe comportamento observado, teste executado nesta rodada, evidência anterior e inferência; informe limites e testes não executados. Confira branch, alterações não commitadas, `HEAD` e remoto quando acessível. Ausência de publicação comprovada é um limite da auditoria, não autorização para commit ou push nem uma correção automática.

Siga os contratos entre camadas afetadas: HTTP/OpenAPI, serviço/repository, migration/schema, API/SPA, sessão/autorização, configuração e documentação. Faça verificações proporcionais ao risco; um teste unitário não prova um fluxo de integração dependente de HTTP, banco ou navegador.

## Achados e reauditoria

Cada achado recebe ID estável, prioridade, evidência verificável, impacto, menor correção suficiente, aceite e teste. Não chame risco potencial de defeito demonstrado. Registre no mesmo `review.md` o estado atual de cada achado após correções e a evidência que mudou a conclusão. Preserve IDs; evite transcrições e arquivos por rodada. A próxima sessão de Code pode corrigir um achado sem validação adicional do usuário, mas só uma nova Review o marca como resolvido.

Registre quais requisitos da Spec foram comprovados e quais ficaram pendentes. Conclua com estado claro, bloqueios reais e limites da verificação. Uma afirmação de gate aprovado deve apontar para resultados descritos em [quality.md](quality.md).
