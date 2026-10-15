# SDD Lite

Cada funcionalidade tem uma pasta `features/NNN-nome/` com `spec.md` e auditorias imutáveis `review-001.md`, `review-002.md` etc. [Skeleton](features/001-skeleton/spec.md) é a spec histórica migrada sem alteração de conteúdo; sua [primeira auditoria](features/001-skeleton/review-001.md) define o próximo ciclo de correções. Referências antigas a `SPEC.md` dentro da spec são parte do registro histórico e apontam agora para esse arquivo.

Fluxo: **SPEC → IMPLEMENTAÇÃO → REVIEW → CORREÇÕES → QUALITY GATE**. Delimite o escopo pela spec ou por IDs da review. Cada achado recebe ID estável; a correção registra o ID no checklist da review, com teste e evidência. Uma nova auditoria recebe o próximo número, cita a anterior e verifica o que mudou, sem reescrever conclusões passadas. Só marque concluído após critérios de aceite e gates passarem.

Use [diretrizes de especificação](guidelines/specification.md), [auditoria](guidelines/review.md) e [qualidade](guidelines/quality.md) com os [templates](templates/). `docs/` contém apenas documentação da aplicação. `todolist.md`, quando presente, permanece histórico.
