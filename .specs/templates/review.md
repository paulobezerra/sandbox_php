# REVIEW-NNN — <nome da funcionalidade>

**Feature:** [feature.md](feature.md) · **Spec:** [spec.md](spec.md) · **Plan:** [plan.md](plan.md) · **Auditoria anterior:** <link ou nenhuma> · **Data:** AAAA-MM-DD · **Estado:** <aberta | correções em andamento | concluída com limites>

**Validação do usuário para Code das correções:** pendente. Após declaração explícita, registrar **Nome:** <nome informado pelo usuário> · **Data:** <AAAA-MM-DD obtida do SO no momento do registro>.

## Base, evidência e limites

<ID de tarefa auditado, diff inspecionado, resultado da feature/spec/plan e contrato vigente. Separar observação de código, teste executado nesta rodada, evidência herdada e inferência.>

<Para cada requisito do incremento: indicar tarefa, comportamento observado e teste/evidência; destacar lacunas e contradições entre artefatos.>

| Verificação | Ambiente/data | Resultado | Limite |
|---|---|---|---|
| `<comando real>` | <onde/quando> | <aprovado/falhou e medida relevante> | <o que não comprova> |

**Não verificado:** <itens e motivo; dizer se bloqueiam o aceite>.

## Achados e correções

- [ ] **RNNN-01 · <prioridade> · <defeito comprovado | risco potencial | divergência de requisito | melhoria>.** **Evidência:** `<arquivo:linha>` ou <comando/resultado e condição>. **Impacto:** <efeito observável>. **Correção mínima:** <mudança e compatibilidade>. **Aceite:** <cenários que distinguem antes/depois>. **Testes:** <níveis e casos, inclusive falha quando relevante>. **Dependências:** <IDs ou nenhuma>.

<Para cada correção, acrescentar abaixo do ID: data, arquivos, teste executado e resultado. Manter aberto se faltar aceite ou gate. Repetir o campo de validação abaixo para cada ID de correção concluído; ele não controla a execução da próxima correção de Code.>

**Validação do usuário do Code <RNNN-XX> para Review:** pendente. Após declaração explícita, registrar **Nome:** <nome informado pelo usuário> · **Data:** <AAAA-MM-DD obtida do SO no momento do registro>.

## Reauditoria anterior e alinhamento com a spec

| ID anterior | Estado atual | Evidência nesta rodada ou referência anterior | Limite |
|---|---|---|---|
| <RNNN-XX> | <resolvido/aberto/parcial> | <código/teste ou review e data> | <não repetido, ambiente etc.> |

<Omitir tabela se for a primeira review. Registrar mudanças de requisito e decisões vigentes sem alterar a spec/review histórica.>

## Quality gate e conclusão

- [ ] Testes específicos aprovados: <comandos, resultados e ambiente>.
- [ ] `composer quality` aprovado: <lint, testes/assertions, cobertura, PHPStan, OpenAPI; ou bloqueio real>.
- [ ] SPA/deploy/migration verificados quando aplicável: <comandos e resultados ou motivo de não aplicação>.

**Estado:** <o que está comprovado, bloqueios reais, verificações opcionais e próximo escopo>. Não chamar “não repetido nesta rodada” de “nunca executado”; conferir registros anteriores.
