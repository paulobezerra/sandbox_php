# Convenções de implementação verificadas

Estas convenções resumem decisões comprovadas na [review-001](../features/001-skeleton/review-001.md) e [review-002](../features/001-skeleton/review-002.md). Use-as como ponto de partida para novas funcionalidades; a spec da funcionalidade define o contrato e justifica qualquer exceção. `AGENTS.md` continua sendo a regra do repositório, e [quality.md](quality.md) define os gates.

## Fronteiras e composição

- Entidades protegem invariantes também na hidratação do banco. Contratos de repository ficam em `Domain`; implementações PDO ficam em `Infrastructure`. `Application` coordena casos de uso e não carrega status HTTP; o adaptador HTTP faz parsing, chama o serviço e traduz erros. Isso foi verificado em R001-08/09/10.
- Componha módulos com seus bindings e fontes de rotas explícitas no Bootstrap. Um novo domínio entra por novos arquivos e bindings rastreáveis; não requer alterar internals de conexão, controllers ou repositories alheios. A composição padrão e o módulo legado opcional foram exercitados em R001-06.
- Exponha apenas métodos com attribute de rota. Mantenha API desconhecida em 404 JSON e fallback SPA para páginas. Se houver cache de rotas, sua chave precisa refletir os arquivos efetivamente registrados e seu conteúdo, inclusive em composições opcionais (R001-07).
- Reserve na composição os slugs de rotas estáticas antes de aceitar URLs dinâmicas de Pages; teste colisões ao acrescentar página institucional ou rota raiz (R001-17).

## Contratos HTTP e segurança

- Diferencie JSON malformado (400), corpo de tipo errado/campos inválidos (422) e ausência de campo. Não transforme input inválido em `[]`, string vazia ou slug diferente sem escolha explícita do contrato. Mapeie erros de aplicação para HTTP no adaptador, preservando respostas documentadas (R001-08/09).
- Toda mutação roteada exige CSRF por padrão. Exceções anônimas são pares exatos método/caminho declarados na composição e não dispensam CSRF para sessão autenticada. Confira também a resposta de rota `/api/*` desconhecida (R001-05).
- Autorização é verificada no backend em cada operação protegida; o router/frontend só melhora a navegação. Cadastro público não atribui ADMIN. Sessão é regenerada após login e revogada conforme a política de credenciais; testes com duas sessões mostram o efeito de troca de senha/códigos (R001-16).
- Segredos de recuperação são gerados com aleatoriedade adequada, exibidos uma vez, guardados como hash e consumidos uma vez. Consumo e troca de senha formam uma transação: erro de escrita não perde o segredo, sucesso impede reuso. Teste concorrência e falha induzida no banco (R001-01/02).
- Rate limit cobre todos os endpoints sensíveis e considera origem e conta sem revelar existência de conta. Use `REMOTE_ADDR` como base; aceite `X-Forwarded-For` apenas de proxies configurados e trate a cadeia corretamente. Para cookie `Secure`, use a URL pública HTTPS validada, não um cabeçalho encaminhado sem confiança. Verifique configuração local HTTP e proxy TLS isolado (R001-03/04).
- Renderize Markdown com escape de HTML bruto e links inseguros bloqueados. `v-html` recebe somente saída do renderizador confiável. A CSP deve refletir o runtime Vue sem build; qualquer relaxamento, como `unsafe-eval`, precisa de motivo documentado e teste XSS (R001-13).

## Dados, frontend e contratos publicados

- Faça queries parametrizadas. Mudança de schema em instalação existente usa migration nova, aditiva quando possível, com teste de preservação de dados. Não edite migration já aplicada para mudar comportamento; confira instalação vazia e base existente quando a alteração tocar onboarding/distribuição (R001-01/15/18).
- Mantenha módulos ES nativos e vendors locais, sem etapa de build. Separe chamada HTTP em `services/` e telas em `pages/`. O cliente Fetch deve informar falha de rede, renovar CSRF após 419 e repetir a mutação no máximo uma vez; valide o fluxo no navegador (R001-12).
- Atualize OpenAPI junto com rota, corpo, resposta, autenticação e cookie configurado. Gere o artefato após mudanças de contrato e rode a checagem contra rotas reais. A documentação gerada não substitui teste HTTP do fluxo (R001-11/14).

Estas práticas não exigem que toda spec implemente Auth, CMS ou todos os testes citados. Selecione os cenários que atravessam a fronteira alterada e registre no aceite por que cada um é necessário.
