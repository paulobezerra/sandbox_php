# SPEC — <nome da funcionalidade>

**ID:** NNN-nome · **Estado:** proposta · **Data:** AAAA-MM-DD

**Validação do usuário para Plan:** pendente. Após declaração explícita, registrar aqui a declaração, o nome informado pelo usuário e a data obtida do SO, sem alterar requisitos ou decisões. A decisão técnica abaixo não equivale à validação.

## Problema, base e objetivo

<quem é afetado; comportamento atual verificado e referência ao código/contrato; resultado esperado>

## Escopo e restrições

- **Inclui:** <resultados desta spec>
- **Fora do escopo:** <limites explícitos>
- **Restrições:** <compatibilidade, stack, deploy, dependências e dados relevantes; consultar AGENTS.md>

## Solução técnica

**Decisão técnica:** <escolha do usuário e data, quando houver alternativas relevantes; ou solução única evidente, data e justificativa baseada no código. Não atribuir escolha ao usuário sem declaração.>

<Resuma em texto apenas as opções reais: a solução única evidente ou a escolhida e suas alternativas relevantes, em blocos separados. Para cada uma, registre o impacto estimado no código (baixo, moderado ou alto), os componentes afetados e os pontos de aderência ou ressalva que importaram à decisão. Identifique critérios não aplicáveis. Evite comparação extensa sem tradeoff real.>

**Motivo da decisão:** <evidência verificada, benefício decisivo e custo aceito; para solução única, explicar por que não havia alternativa relevante>.

## Questões em aberto para validação

<Liste decisões de produto ou divergências com a Feature que o usuário ainda precisa avaliar. Para cada uma, cite o contrato anterior, as opções reais, o impacto e a proposta atual. Se nenhuma restar, escreva “Nenhuma” para tornar a checagem visível antes da validação. Não trate a escolha técnica já feita como validação da Spec.>

## Requisitos

- **SNNN-01** — <resultado observável; consumidores afetados: API, SPA, OpenAPI, banco, operação>
- **SNNN-02** — <outro resultado; dependência de SNNN-01 se houver>

<O plan transformará estes requisitos em tarefas entregáveis. Code e Review registram progresso fora desta Spec.>

## Contratos, dados e segurança

<Rotas/payloads/status, invariantes, autenticação/autorização/CSRF, segredos/sessões, migrations e compatibilidade existentes. Omitir partes não aplicáveis, justificando decisões que alterem contratos.>

## Critérios de aceite e verificação

- **Sucesso:** dado <estado inicial>, quando <ação>, então <resultado observável>. Teste: <nível e cenário>.
- **Falha/limite:** dado <entrada inválida, acesso indevido ou falha parcial pertinente>, quando <ação>, então <resposta/estado preservado>. Teste: <nível e cenário>.
- **Integração/compatibilidade:** <dados existentes, API/SPA/OpenAPI ou instalação limpa afetados>. Teste: <cenário e ambiente>.

**Gate aplicável:** <comandos de ../guidelines/quality.md e verificação adicional motivada pelo risco; consultar ../guidelines/implementation.md e registrar execução/limites na review>.

## Decisões e histórico

- AAAA-MM-DD — <decisão, motivo, contrato anterior substituído, efeitos e referência à review/spec anterior; não reescrever histórico>.
