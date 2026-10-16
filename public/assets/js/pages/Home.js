export default {
    template: `
        <article class="home-page">
            <header class="hero">
                <p class="eyebrow">SANDBOX PHP · PHP 8.4 · SLIM 4 · VUE 3</p>
                <h1>Uma base reutilizável para aplicações PHP</h1>
                <p class="lead">Um site e uma API no mesmo deploy, com autenticação e um blog de Posts em Markdown.</p>
                <p>O sandbox_php mostra uma estrutura pequena, explícita e pronta para adaptar ao seu produto.</p>
                <nav aria-label="Nesta página" class="home-toc">
                    <a href="#capacidades">Capacidades</a><a href="#stack">Stack</a><a href="#arquitetura">Arquitetura</a>
                    <a href="#instalacao">Instalação</a><a href="#primeiro-uso">Primeiro uso</a><a href="#rotas">Rotas</a>
                    <a href="#qualidade">Testes</a><a href="#producao">Produção</a><a href="#customizacao">Customização</a><a href="#sdd-lite">SDD Lite</a>
                </nav>
            </header>

            <section id="capacidades" class="content-card">
                <h2>O que a base oferece</h2>
                <p>Visitantes leem Posts publicados. Qualquer pessoa pode criar uma conta; cada usuário autenticado cria, edita, publica e remove somente os próprios Posts. O cadastro está disponível pelo fluxo de Login.</p>
                <ul><li>Cadastro, login e logout com sessão no servidor.</li><li>Blog público e editor em <code>/my-posts</code>.</li><li>Prévia Markdown segura antes da publicação.</li><li>API JSON e referência OpenAPI no ambiente local.</li></ul>
            </section>

            <section id="stack" class="content-card">
                <h2>Stack e execução sem build</h2>
                <p>O backend usa PHP 8.4, Slim 4, PDO/MySQL e Phinx. A interface usa Vue 3 e Vue Router em módulos ES servidos diretamente pelo Apache. Não há Node.js, npm, bundler nem etapa de compilação JavaScript. Um deploy PHP/Apache serve o site e a API.</p>
                <p>Para desenvolvimento, Docker Compose fornece a aplicação e MySQL. Também é possível usar PHP 8.4, Composer 2, as extensões <code>pdo_mysql</code> e <code>mbstring</code>, MySQL 8.4 e Apache com <code>mod_rewrite</code>.</p>
            </section>

            <section id="arquitetura" class="content-card">
                <h2>Organização e fluxo de dependências</h2>
                <p>O caminho de uma requisição é Controller HTTP → serviço de Application → entidade e contrato de repository no Domain. A implementação PDO fica em Infrastructure; Bootstrap conecta as dependências e registra as rotas.</p>
                <ul><li><code>src/Domain/User</code> e <code>src/Domain/Post</code>: regras, invariantes e contratos.</li><li><code>src/Application</code>: operações de aplicação, sem Slim ou PDO.</li><li><code>src/Infrastructure</code>: controllers, sessões, Markdown e repositories MySQL.</li><li><code>src/Bootstrap</code>: configuração, composição e rotas.</li><li><code>public/assets</code>: módulos Vue, CSS e bibliotecas locais.</li><li><code>database/migrations</code> e <code>tests</code>: schema versionado e verificações.</li></ul>
                <p>Controllers adaptam HTTP e não contêm SQL. Repositories persistem dados sem decidir regras de negócio. Para adicionar um domínio, crie seus contratos e implementações nas respectivas camadas e conecte-os no Bootstrap.</p>
            </section>

            <section id="instalacao" class="content-card">
                <h2>Instalação e primeiro uso</h2>
                <p>Com Docker Engine e Docker Compose v2 instalados, clone o repositório, configure as credenciais locais e inicie os serviços:</p>
                <pre><code>git clone https://github.com/paulobezerra/sandbox_php.git meu-projeto
cd meu-projeto
cp .env.example .env
# Edite .env e troque as senhas de exemplo.
docker compose up -d --build</code></pre>
                <p>A aplicação fica em <code>http://localhost:8080</code>. A migration não roda automaticamente; aplique-a explicitamente:</p>
                <pre><code>docker compose exec -T app composer migrate
docker compose exec -T app composer migrate:status</code></pre>
                <p>Use <code>.env</code> apenas para configuração local e mantenha-o fora do controle de versão.</p>
            </section>

            <section id="primeiro-uso" class="content-card">
                <h2>Cadastro, login e ciclo de Posts</h2>
                <ol><li>Abra <a href="/login">Login</a> e use o link de cadastro para criar uma conta.</li><li>Entre; o menu mostra <strong>Meus Posts</strong> e <strong>Sair</strong>.</li><li>Em Meus Posts, crie um rascunho com título e Markdown.</li><li>Confira a prévia, salve e publique. O Post publicado aparece no <a href="/blog">Blog</a>.</li><li>Edite, despublique ou exclua somente os Posts da sua conta.</li></ol>
                <p>Senhas são armazenadas com hash. A sessão é regenerada no login, mutações autenticadas exigem CSRF e o backend confere a autoria em cada operação. Este demonstrador não inclui recuperação de senha nem limite de tentativas; um projeto público deve definir seu próprio procedimento de recuperação e controles contra abuso antes da produção.</p>
            </section>

            <section id="rotas" class="content-card">
                <h2>Rotas e OpenAPI</h2>
                <ul><li><code>/</code>: apresentação e documentação.</li><li><code>/blog</code> e <code>/blog/{slug}</code>: leitura pública de Posts publicados.</li><li><code>/login</code> e <code>/register</code>: autenticação.</li><li><code>/my-posts</code>: editor dos Posts da conta autenticada.</li><li><code>/api/auth/*</code>, <code>/api/posts*</code> e <code>/api/me/posts*</code>: API de sessão, leitura e edição.</li><li><code>/docs</code>: Swagger UI e OpenAPI quando habilitados na configuração.</li></ul>
                <p>Rotas desconhecidas em <code>/api/*</code> respondem 404 JSON; caminhos de página desconhecidos exibem a tela de não encontrado.</p>
            </section>

            <section id="qualidade" class="content-card">
                <h2>Testes e qualidade</h2>
                <p>Os comandos Composer rodam no container <code>app</code>. Formatação, lint, testes e o gate completo:</p>
                <pre><code>docker compose exec -T app composer format
docker compose exec -T app composer lint
docker compose exec -T app composer test
docker compose exec -T app composer quality</code></pre>
                <p>Com Chrome ou Chromium instalado no host, valide também o navegador e os módulos Vue:</p>
                <pre><code>./tools/test-frontend.sh</code></pre>
                <p>O gate <code>composer quality</code> inclui lint, PHPUnit com cobertura mínima, PHPStan e checagem OpenAPI. <code>composer format</code> altera arquivos; os outros comandos verificam.</p>
            </section>

            <section id="producao" class="content-card">
                <h2>Produção e segurança</h2>
                <p>Use <code>compose.prod.yaml</code> e um arquivo de ambiente externo ao repositório. Defina segredos próprios, <code>APP_ENV=production</code>, <code>APP_DEBUG=false</code>, <code>OPENAPI_ENABLED=false</code> e <code>APP_URL</code> com HTTPS público. Termine TLS em um proxy reverso e não exponha MySQL à internet.</p>
                <p>O cookie de sessão recebe <code>Secure</code> quando a URL pública usa HTTPS. Faça backup do banco antes de atualizar, aplique migrations como etapa controlada e mantenha <code>.env</code> e backups fora do diretório público. Cadastro é aberto e não há recuperação de senha nem rate limiting neste demonstrador; avalie e implemente essas proteções antes de publicar uma aplicação derivada.</p>
                <pre><code>cp .env.example .env
                # Configure segredos próprios, APP_ENV=production, APP_DEBUG=false,
                # OPENAPI_ENABLED=false e APP_URL=https://www.exemplo.com.
                docker compose --env-file .env -f compose.prod.yaml up -d --build
                docker compose --env-file .env -f compose.prod.yaml exec -T app composer migrate --no-interaction</code></pre>
                <p>Os templates Vue são compilados pelo runtime no navegador. Não aceite templates Vue ou HTML executável de usuários; a prévia de Markdown usa renderização que escapa HTML bruto e bloqueia links inseguros.</p>
            </section>

            <section id="customizacao" class="content-card">
                <h2>Caminhos de customização</h2>
                <p>Adapte os campos de User e os fluxos de Auth ao produto derivado. Preserve hash de senha, sessão segura, regeneração no login, CSRF e autorização no backend. Atualize juntos entidade, contrato de repository, implementação PDO, migration, API e testes quando mudar o modelo.</p>
                <p>Para um domínio novo, mantenha regras em Domain, orquestração em Application e integrações em Infrastructure; registre dependências e rotas no Bootstrap. Prefira contracts específicos e evite abstrações genéricas de extensão. Os arquivos de código e testes mostram os exemplos atuais.</p>
            </section>

            <section id="sdd-lite" class="content-card">
                <h2>SDD Lite</h2>
                <p>O SDD Lite organiza funcionalidades em entregas rastreáveis: <strong>Feature → Spec → Plan → Code → Review</strong>. Cada etapa registra necessidade, decisões técnicas, tarefas, implementação e auditoria.</p>
                <p><code>.specs/</code> é a fonte dos comandos e artefatos do fluxo. Uma funcionalidade guarda <code>feature.md</code>, <code>spec.md</code>, <code>plan.md</code> e <code>review.md</code>; os comandos compartilhados ficam em <code>.specs/commands/</code>.</p>
                <p>Para começar uma funcionalidade, descreva o resultado desejado e execute o comando Feature. Por exemplo, em um agente com o comando disponível: <code>$sdd-feature adicionar categorias aos Posts</code>. Para seguir um ID do Plan depois de validado: <code>$sdd-code T003-01</code>. Leia <a href="https://github.com/paulobezerra/sandbox_php/blob/main/.specs/README.md">a visão geral do SDD Lite no repositório</a> para encontrar os comandos e registros detalhados.</p>
            </section>
        </article>
    `
};
