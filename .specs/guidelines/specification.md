# Especificações

Uma spec descreve **a solução técnica escolhida e como comprovar o resultado**. Parta da `feature.md` e crie `features/NNN-nome/spec.md` a partir do [template](../templates/spec.md) somente após o usuário decidir entre as opções apresentadas conforme o [fluxo](workflow.md). Consulte o código, os contratos e os testes existentes: a spec define o alvo, mas precisa identificar a base real da qual o projeto parte. Não transforme uma auditoria em spec nem copie todas as convenções do repositório para cada funcionalidade.

## Delimitar o trabalho

- Declare o problema, quem é afetado, o comportamento atual observado e o comportamento esperado. Separe fatos verificados de hipóteses e cite arquivos ou rotas relevantes quando isso tornar o ponto conferível.
- Liste o que entra e o que fica fora da mudança. Dê IDs estáveis aos requisitos; o [Plan](../templates/plan.md) quebrará a solução em tarefas entregáveis. Um pedido por item não autoriza executar a spec inteira.
- Registre as restrições que importam para a decisão: PHP 8.4/Slim 4/Vue 3 sem build JS, compatibilidade HTTP, dados existentes, deploy e bibliotecas atuais. Evite soluções prescritas quando o contrato observável basta; justifique escolhas estruturais e novas dependências.
- Identifique consumidores dos contratos afetados: API, SPA, OpenAPI, scripts, banco e documentação operacional. Mudança de requisito só está pronta quando esses consumidores foram considerados juntos.

## Escrever requisitos testáveis

Cada requisito deve ter um ID e um resultado observável. Descreva cenários com **estado inicial, ação e resultado esperado**. Inclua caminho de sucesso, entrada inválida, falta de autenticação/autorização e falha de dependência quando forem relevantes. Especifique status e forma da resposta HTTP apenas quando fizerem parte do contrato; não use “funciona”, “é seguro” ou “tem testes” como único aceite.

Para dados, defina invariantes e transições: unicidade, estado permitido, atomicidade, concorrência, preservação de registros e comportamento de migrations já aplicadas. Uma migration nova deve ter plano para instalações vazias **e** existentes; rollback destrutivo não é um critério obrigatório. Para segredos e sessão, defina quem pode gerar ou usar o segredo, o que é persistido, quando expira ou é invalidado, e como operações simultâneas/falhas parciais se comportam. Para frontend, descreva também o fluxo no navegador, inclusive estado de sessão, erro de rede e CSRF quando aplicável.

Exemplo de aceite útil: “dado um código de recuperação válido, se a gravação da nova senha falhar, o código permanece utilizável; se a gravação concluir, a senha muda e o código não pode ser reutilizado”. Isso determina o teste de transação sem impor uma classe ou método específico.

## Alinhar decisões e histórico

Antes de redigir o arquivo final, compare opções viáveis em segurança, integridade, performance, compatibilidade, simplicidade, manutenção e operação. Explique a recomendação e espere a escolha do usuário; não presuma que a recomendação seja aprovação. Registre na spec a escolha, data, motivo, alternativa descartada e tradeoff relevante. No skeleton, a recuperação por códigos substituiu o fluxo histórico por SMTP; os checkboxes antigos não foram apagados. Quando uma spec nova substituir um contrato existente, indique a migração e quais rotas, telas, schemas e documentos devem mudar. Preserve specs/reviews anteriores como evidência histórica e deixe a decisão vigente explícita na nova spec. Um checkbox só representa trabalho verificado no escopo e momento em que foi marcado; a [review](review.md) confronta esse registro com o código atual.

Antes de passar ao Plan, confira se cada requisito tem fronteira clara, aceite observável e verificação proporcional ao risco. Decisões indispensáveis ainda abertas devem aparecer explicitamente; detalhes de implementação rotineiros podem ser resolvidos durante o trabalho.
