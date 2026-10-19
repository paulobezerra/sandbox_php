export default {
    template: `
        <article class="home-page">
            <header class="hero">
                <p class="eyebrow">{{ $t('home.eyebrow') }}</p>
                <h1>{{ $t('home.title') }}</h1>
                <p class="lead">{{ $t('home.lead') }}</p>
                <p>{{ $t('home.intro') }}</p>
                <p><a class="home-repository-link" href="https://github.com/paulobezerra/sandbox_php" target="_blank" rel="noopener noreferrer">{{ $t('home.repositoryLink') }}</a></p>
                <nav :aria-label="$t('home.contents')" class="home-toc">
                    <a href="#capacidades">{{ $t('home.features') }}</a><a href="#stack">{{ $t('home.stack') }}</a>
                    <a href="#arquitetura">{{ $t('home.architecture') }}</a><a href="#instalacao">{{ $t('home.install') }}</a>
                    <a href="#primeiro-uso">{{ $t('home.auth') }}</a><a href="#rotas">{{ $t('home.routes') }}</a>
                    <a href="#qualidade">{{ $t('home.quality') }}</a><a href="#producao">{{ $t('home.security') }}</a>
                    <a href="#customizacao">{{ $t('home.customization') }}</a><a href="#sdd-lite">{{ $t('home.sdd') }}</a>
                </nav>
            </header>
            <section id="capacidades" class="content-card"><h2>{{ $t('home.features') }}</h2><p>{{ $t('home.featuresText') }}</p>
                <ul><li v-for="(item, index) in $t('home.featureList').split('|')" :key="index">{{ item }}</li></ul>
            </section>
            <section id="stack" class="content-card"><h2>{{ $t('home.stack') }}</h2><p>{{ $t('home.stackText') }}</p><p>{{ $t('home.stackEnvironment') }}</p>
                <nav class="technology-marks" :aria-label="$t('home.technologyMarks')">
                    <a href="https://www.php.net/" target="_blank" rel="noopener noreferrer"><img src="/assets/technology/php.svg" alt="PHP"><span>PHP</span></a>
                    <a href="https://www.slimframework.com/" target="_blank" rel="noopener noreferrer"><img src="/assets/technology/slim.svg" alt="Slim"><span>Slim</span></a>
                    <a href="https://vuejs.org/" target="_blank" rel="noopener noreferrer"><img src="/assets/technology/vue.svg" alt="Vue"><span>Vue</span></a>
                    <a href="https://dev.mysql.com/doc/" target="_blank" rel="noopener noreferrer"><img src="/assets/technology/mysql.svg" alt="MySQL"><span>MySQL</span></a>
                    <a href="https://docs.docker.com/compose/" target="_blank" rel="noopener noreferrer"><img src="/assets/technology/docker.svg" alt="Docker Compose"><span>Docker Compose</span></a>
                    <a href="https://phinx.org/" target="_blank" rel="noopener noreferrer"><img src="/assets/technology/phinx.svg" alt="Phinx"><span>Phinx</span></a>
                </nav>
            </section>
            <section id="arquitetura" class="content-card"><h2>{{ $t('home.architecture') }}</h2><p>{{ $t('home.architectureText') }}</p>
                <ul><li v-for="(item, index) in $t('home.architecturePaths').split('|')" :key="index">{{ item }}</li></ul>
                <p>{{ $t('home.architectureExtension') }}</p>
            </section>
            <section id="instalacao" class="content-card"><h2>{{ $t('home.install') }}</h2><p>{{ $t('home.installText') }}</p>
                <p><a href="https://getcomposer.org/doc/03-cli.md#create-project" target="_blank" rel="noopener noreferrer">{{ $t('home.composerDocs') }}</a></p>
                <pre><code>{{ $t('home.installCommands') }}</code></pre>
                <p>{{ $t('home.installMigration') }}</p><p>{{ $t('home.installEnvironment') }}</p>
            </section>
            <section id="primeiro-uso" class="content-card"><h2>{{ $t('home.auth') }}</h2>
                <ol><li v-for="(item, index) in $t('home.authList').split('|')" :key="index">{{ item }}</li></ol>
                <p>{{ $t('home.authSecurity') }}</p>
            </section>
            <section id="rotas" class="content-card"><h2>{{ $t('home.routes') }}</h2>
                <ul><li v-for="(item, index) in $t('home.routesList').split('|')" :key="index">{{ item }}</li></ul>
                <p>{{ $t('home.routesText') }}</p>
                <p><a href="/docs">{{ $t('home.openApiRoute') }}</a> · <a href="https://swagger.io/docs/" target="_blank" rel="noopener noreferrer">{{ $t('home.swaggerDocs') }}</a></p>
                <pre><code>composer openapi:generate</code></pre>
            </section>
            <section id="qualidade" class="content-card"><h2>{{ $t('home.quality') }}</h2><p>{{ $t('home.qualityText') }}</p><p>{{ $t('home.qualityCommands') }}</p>
                <pre><code>docker compose exec -T app composer format
docker compose exec -T app composer lint
docker compose exec -T app composer test
docker compose exec -T app composer quality
docker compose exec -T app composer migrate:status
./tools/test-frontend.sh
./tools/test-all.sh</code></pre>
                <p>{{ $t('home.qualityDetails') }}</p>
            </section>
            <section id="producao" class="content-card"><h2>{{ $t('home.security') }}</h2><p>{{ $t('home.securityText') }}</p>
                <p>{{ $t('home.securityCookie') }}</p>
                <pre><code>cp .env.example .env
# Set production secrets, APP_ENV=production, APP_DEBUG=false,
# OPENAPI_ENABLED=false, and APP_URL=https://www.example.com.
docker compose --env-file .env -f compose.prod.yaml up -d --build
docker compose --env-file .env -f compose.prod.yaml exec -T app composer migrate --no-interaction</code></pre>
                <p>{{ $t('home.securityVue') }}</p>
                <p>{{ $t('home.hostingText') }}</p>
                <p><a href="https://github.com/paulobezerra/sandbox_php/blob/main/README.md#shared-hosting-configuration" target="_blank" rel="noopener noreferrer">{{ $t('home.hostingGuide') }}</a></p>
            </section>
            <section id="customizacao" class="content-card"><h2>{{ $t('home.customization') }}</h2><p>{{ $t('home.customizationText') }}</p>
                <p>{{ $t('home.customizationModel') }}</p><p>{{ $t('home.customizationDomain') }}</p>
                <p><a href="https://github.com/paulobezerra/sandbox_php/blob/main/docs/customization.md" target="_blank" rel="noopener noreferrer">{{ $t('home.customizationGuideLink') }}</a></p>
            </section>
            <section id="sdd-lite" class="content-card"><h2>{{ $t('home.sdd') }}</h2><p>{{ $t('home.sddText') }}</p>
                <p><a href="https://github.com/paulobezerra/sdd-lite" target="_blank" rel="noopener noreferrer">{{ $t('home.sddLink') }}</a></p>
            </section>
        </article>
    `
};
