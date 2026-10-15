# Customização e novos domínios

Este repositório é uma base de aplicação, não uma biblioteca imutável. Um projeto derivado pode alterar os campos de User e seus fluxos sem preservar compatibilidade com instalações alheias. Antes de adaptar, identifique migrations já aplicadas e planeje uma migration aditiva que preserve os dados existentes.

## Adaptar User e Auth

`Domain/User/User` contém identidade básica, e-mail normalizado e role; `Application/Auth/AuthService` implementa os fluxos padrão. Para adicionar dados próprios, prefira uma entidade de perfil relacionada (por exemplo `AuthorProfile` ou `OrganizationMembership`) quando isso mantiver a autenticação independente do CMS. Se o projeto exigir outra identidade, altere User/Auth e seus contratos como parte do projeto derivado.

Ao adaptar:

1. Mantenha `password_hash`/`password_verify`, sessão no servidor, regeneração de sessão no login, cookies seguros e CSRF nas mutações autenticadas.
2. O fluxo padrão usa códigos aleatórios gerados por usuário autenticado após confirmar a senha atual. Guarda somente hashes, mostra o lote uma vez, invalida o lote anterior na regeneração e consome códigos uma vez junto com a troca de senha. O fluxo não usa SMTP nem oferece recuperação pública por e-mail. Defina e documente um procedimento de operador com verificação de identidade para a perda de senha e de todos os códigos; sem ele, não prometa recuperação.
3. Cadastro público nunca aceita role ADMIN. Provisionar o primeiro administrador pelo comando CLI ou por processo operacional controlado.
4. Atualize o repository de domínio, implementação PDO, migration e testes juntos. Não faça SQL em controller nem introduza dependência PDO no Domain/Application.
5. Atualize os schemas OpenAPI e rode `composer openapi:generate` e `composer test`.

## Exercício realizado: domínio de avisos

O teste `tests/Architecture/DomainExtensionTest.php` implementa um domínio novo chamado `Notice` usando somente entidades, contrato de repository e serviço de aplicação. Um repository em memória no teste comprova o caso de uso sem alterar controllers, rotas, `ConnectionFactory` ou repositories existentes. O domínio segue esta divisão:

```text
src/Domain/Notice/Notice.php
src/Domain/Notice/NoticeRepository.php
src/Application/Notice/ListNoticesService.php
tests/Architecture/DomainExtensionTest.php
```

Para transformar esse exemplo em funcionalidade persistida e exposta por API, adicione novos arquivos: uma migration, `Infrastructure/Database/Repository/MysqlNoticeRepository`, binding contrato → adapter no Bootstrap, controller com atributos explícitos e testes. As alterações ficam nas novas bordas de integração; não é necessário mudar o comportamento interno dos controllers existentes, da conexão PDO ou dos repositories de outros domínios. Registre novos métodos HTTP em atributos e documente os contratos OpenAPI.

Execute o exercício com:

```sh
docker compose exec -T app composer test
```

## Convenções para qualquer domínio

- Entidades e regras ficam em `src/Domain/<Nome>/`; contratos de persistência ficam junto ao domínio.
- Operações que coordenam entidades e repositories ficam em `src/Application/<Nome>/`.
- Implementações MySQL ficam em `src/Infrastructure/Database/Repository/`; use parâmetros PDO e migrations Phinx versionadas.
- Controllers Slim são adaptadores HTTP finos em `src/Infrastructure/Http/Controller/`. Apenas métodos explicitamente anotados com attributes de rota são publicados.
- Adicione testes unitários para invariantes e casos de uso; use integração quando a mudança envolver SQL, HTTP, sessão ou configuração.
- Não crie interface para cada serviço, abstrações genéricas de plugin, nem pastas vazias. Use o caminho mais curto que preserve as fronteiras acima.
