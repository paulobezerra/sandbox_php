export default {
    template: `
        <section>
            <h1>{{ $t('notFound.title') }}</h1>
            <p>{{ $t('error.notFound') }}</p>
            <router-link to="/">{{ $t('notFound.back') }}</router-link>
        </section>
    `
};
