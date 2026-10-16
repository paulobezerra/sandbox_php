# SPEC — <nome da funcionalidade>

**ID:** NNN-nome · **Estado:** proposta · **Data:** AAAA-MM-DD

**Validação do usuário para Plan:** pendente. Após declaração explícita, registrar **Nome:** <nome informado pelo usuário> · **Data:** <AAAA-MM-DD obtida do SO no momento do registro>. A escolha técnica abaixo não equivale a esta validação.

## Problema, base e objetivo

<quem é afetado; comportamento atual verificado e referência ao código/contrato; resultado esperado>

## Escopo e restrições

- **Inclui:** <resultados desta spec>
- **Fora do escopo:** <limites explícitos>
- **Restrições:** <compatibilidade, stack, deploy, dependências e dados relevantes; consultar AGENTS.md>

## Solução escolhida

**Escolha do usuário:** <opção e data da decisão; não preencher antes da escolha>.

<Resumo da matriz apresentada ao usuário, com critérios não aplicáveis identificados. Adicione coluna para terceira opção se houver. Não transformar recomendação em aprovação.>

| Critério | <opção A> | <opção B> |
|---|---|---|
| Segurança e integridade | <tradeoff> | <tradeoff> |
| Performance | <tradeoff> | <tradeoff> |
| Compatibilidade e migração | <tradeoff> | <tradeoff> |
| Simplicidade | <tradeoff> | <tradeoff> |
| Manutenção e padrões | <tradeoff> | <tradeoff> |
| Operação | <tradeoff> | <tradeoff> |

**Motivo da escolha:** <benefício decisivo e custo aceito>.

## Requisitos

- [ ] SNNN-01 — <resultado observável; consumidores afetados: API, SPA, OpenAPI, banco, operação>
- [ ] SNNN-02 — <outro resultado; dependência de SNNN-01 se houver>

<O plan transformará estes requisitos em tarefas entregáveis. Não marcar sem evidência.>

## Contratos, dados e segurança

<Rotas/payloads/status, invariantes, autenticação/autorização/CSRF, segredos/sessões, migrations e compatibilidade existentes. Omitir partes não aplicáveis, justificando decisões que alterem contratos.>

## Critérios de aceite e verificação

- [ ] **Sucesso:** dado <estado inicial>, quando <ação>, então <resultado observável>. Teste: <nível e cenário>.
- [ ] **Falha/limite:** dado <entrada inválida, acesso indevido ou falha parcial pertinente>, quando <ação>, então <resposta/estado preservado>. Teste: <nível e cenário>.
- [ ] **Integração/compatibilidade:** <dados existentes, API/SPA/OpenAPI ou instalação limpa afetados>. Teste: <cenário e ambiente>.

**Gate aplicável:** <comandos de ../guidelines/quality.md e verificação adicional motivada pelo risco; consultar ../guidelines/implementation.md e registrar execução/limites na review>.

## Decisões e histórico

- AAAA-MM-DD — <decisão, motivo, contrato anterior substituído, efeitos e referência à review/spec anterior; não reescrever histórico>.

**Questões indispensáveis abertas:** <decisões que impedem aceite; remover seção se nenhuma>.
