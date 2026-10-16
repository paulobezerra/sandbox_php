# Customização e novos domínios

Este repositório é uma base de aplicação que pode ser adaptada ao produto derivado. A instalação padrão inclui cadastro, login, logout e Posts: cada conta autenticada gerencia somente os próprios Posts. Não há perfis de usuário, recuperação de senha nem limite de tentativas de autenticação. Defina controles contra abuso e um procedimento de recuperação antes de expor uma aplicação derivada publicamente.

## Adaptar User e Auth

`src/Domain/User/User.php` contém a identidade básica e o e-mail normalizado; `src/Application/Auth/AuthService.php` implementa cadastro, login e logout. Para acrescentar dados próprios, prefira uma entidade de perfil relacionada quando isso mantiver a autenticação independente do CMS. Se o projeto exigir outra identidade, adapte User/Auth e seus contratos em conjunto.

Ao adaptar:

1. Mantenha `password_hash`/`password_verify`, sessão no servidor, regeneração de sessão no login, cookies seguros e CSRF nas mutações autenticadas.
2. Defina um procedimento de recuperação de conta com verificação de identidade e controles contra abuso adequados ao ambiente. O demonstrador não oferece recuperação nem limitação de tentativas.
3. Atualize o contrato de User, sua implementação PDO, migration e testes juntos. Não faça SQL em controller nem introduza dependência PDO no Domain/Application.
4. Atualize os schemas OpenAPI quando mudar a API e rode `composer openapi:generate` e `composer quality`.

## Adicionar um domínio

Entidades e contratos de persistência ficam em `src/Domain/<Nome>/`; operações de aplicação ficam em `src/Application/<Nome>/`; implementações MySQL ficam em `src/Infrastructure/Database/Repository/`. Um domínio persistido e exposto por API também precisa de migration, binding no Bootstrap, controller com attributes explícitos de rota e testes. Mantenha os contratos no Domain e os adaptadores na Infrastructure; não altere internals de conexão, controllers ou repositories de outros domínios para acomodar a entidade nova.

## Convenções

- Entidades protegem invariantes; services de aplicação coordenam contratos e regras de caso de uso.
- Use queries PDO parametrizadas e migrations Phinx explícitas. Para uma instalação existente, crie migration aditiva e preserve os dados necessários ao projeto.
- Controllers Slim são adaptadores HTTP finos. Apenas métodos com attributes de rota são publicados.
- Adicione testes unitários para invariantes e casos de uso; use integração quando a mudança envolver SQL, HTTP, sessão ou configuração.
- Não crie interface para cada serviço, abstrações genéricas de plugin ou pastas vazias. Use o caminho mais curto que preserve as fronteiras acima.
