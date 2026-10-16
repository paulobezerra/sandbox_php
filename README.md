# Skeleton PHP + Slim + Vue

Base reutilizável para aplicações PHP 8.4 com Slim 4, MySQL/PDO, Phinx e Vue 3. Backend e frontend são servidos pelo mesmo deploy PHP/Apache. O JavaScript usa módulos ES nativos e arquivos locais: não há Node.js nem etapa de build.

## Repositório

Este projeto está publicado como [`paulobezerra/sandbox_php`](https://github.com/paulobezerra/sandbox_php).

## Requisitos

- Docker Engine com Docker Compose v2 (caminho recomendado) ou PHP 8.4, Composer 2, extensões `pdo_mysql` e `mbstring`, MySQL 8.4 e Apache com `mod_rewrite`.
- Chrome ou Chromium no host apenas para `tools/test-all.sh`.

## Início local

```sh
git clone https://github.com/paulobezerra/sandbox_php.git meu-projeto
cd meu-projeto
cp .env.example .env
# Troque todas as senhas de exemplo antes de iniciar.
docker compose up -d --build
docker compose exec -T app composer migrate
```

Abra `http://localhost:8080`. A migration é explícita e não roda no startup. A Home documental está em `/`, o blog em `/blog`, o cadastro e login em `/login`, a área pessoal de Posts em `/my-posts`, a API em `/api` e a documentação OpenAPI em `/docs` no ambiente local. O MySQL de desenvolvimento publica a porta `127.0.0.1:3306`.

Crie uma conta pelo link de cadastro na tela de login. Toda conta autenticada pode criar, editar, publicar e remover somente os próprios Posts. O demonstrador não tem recuperação de senha: defina um procedimento operacional de recuperação antes de usar contas importantes.

## Verificações

```sh
docker compose exec -T app composer format
docker compose exec -T app composer lint
docker compose exec -T app composer test
docker compose exec -T app composer quality
docker compose exec -T app composer migrate:status
./tools/test-all.sh
```

`composer format` aplica PSR-12 aos arquivos PHP próprios; `composer lint` verifica estilo e sintaxe sem escrever. `composer test` executa PHPUnit no container e `composer quality` inclui lint, testes com cobertura, cobertura mínima, PHPStan e checagem OpenAPI. `./tools/test-all.sh` também abre as rotas centrais com Chrome/Chromium headless no host. Para rodar apenas o navegador, use `./tools/test-frontend.sh`; defina `CHROME_BIN` se o executável não estiver no PATH. `composer migrate:status` lista o estado das migrations. `composer openapi:generate` regenera `docs/openapi.json` a partir dos atributos PHP.

## Produção em Docker

O arquivo `compose.prod.yaml` usa uma imagem otimizada sem Xdebug, sem bind mount e com documentação OpenAPI desligada. Use um `.env` de produção fora do controle de versão, com credenciais únicas e `APP_URL` apontando para o domínio HTTPS público. A aplicação define o cookie `Secure` a partir dessa URL. O Compose publica HTTP; termine TLS em um proxy reverso ou balanceador.

```sh
cp .env.example .env
# Edite .env: segredos fortes, APP_ENV=production, APP_DEBUG=false,
# OPENAPI_ENABLED=false e APP_URL=https://www.exemplo.com.
docker compose --env-file .env -f compose.prod.yaml up -d --build
docker compose --env-file .env -f compose.prod.yaml exec -T app composer migrate --no-interaction
```

Faça backup do banco antes de atualizar a aplicação e aplique migrations como etapa controlada do deploy. Não exponha a porta MySQL à internet. Guarde `.env` e backups fora do diretório público; o Apache usa `public/` como DocumentRoot. A imagem contém o código e dependências de produção. Para hospedagem compartilhada, publique somente `public/` como raiz web, mantenha `src/`, `vendor/` e `.env` fora dela e configure as mesmas variáveis no ambiente PHP.

## Estrutura e extensão

```text
src/Domain/             Entidades, invariantes e contratos de repository
src/Application/        Operações de aplicação
src/Infrastructure/     Adaptadores HTTP, PDO, sessão e views
src/Bootstrap/          Composição das dependências e rotas
database/migrations/    Schema inicial e migrations explícitas
public/assets/          Vue, Vue Router, CSS e bibliotecas locais
tests/                  Unitários, integração e arquitetura
docs/                   OpenAPI e guias do skeleton
```

As dependências seguem `Infrastructure → Application → Domain`; o Bootstrap compõe os adaptadores. Leia [o guia de customização e extensão](docs/customization.md) antes de adaptar User/Auth ou adicionar um domínio. A aplicação padrão contém Auth e Posts.

## Segurança e manutenção

- Senhas são armazenadas com `password_hash` e verificadas com `password_verify`. Sessões ficam no servidor, são regeneradas no login e usam cookies HttpOnly; mutações autenticadas exigem CSRF e o backend confere a autoria de cada Post.
- O cadastro é aberto. Este demonstrador não inclui recuperação de senha nem limita tentativas de autenticação; antes de expô-lo publicamente, defina controles contra abuso e um procedimento de recuperação adequados ao projeto.
- Posts armazenam Markdown e escapam HTML bruto na renderização. As views usam `v-html` somente com HTML produzido pelo `MarkdownRenderer`, que rejeita HTML bruto e links inseguros; preview e publicação compartilham esse renderer. Os templates Vue ficam em módulos ES sem build e são compilados no navegador, por isso a CSP mantém `unsafe-eval`; não forneça templates Vue nem HTML renderizável por usuários.
- Para mudar o schema de uma instalação existente, crie uma migration nova; não altere uma migration já aplicada.
- Nunca coloque credenciais reais, tokens ou dumps no repositório.

## Documentação adicional

- [Customizar User/Auth e adicionar domínios](docs/customization.md)
- [Contrato OpenAPI](docs/openapi.json)
- [AGENTS.md](AGENTS.md): convenções de trabalho para agentes neste repositório
- [SDD Lite](.specs/README.md): índice das especificações e auditorias
- [Spec do skeleton](.specs/features/001-skeleton/spec.md): requisitos e histórico de fases
