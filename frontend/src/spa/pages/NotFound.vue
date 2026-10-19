<script>
import { Button } from '../components/ui/button/index.js';
import { Card } from '../components/ui/card/index.js';

export default { components: { Button, Card } };
</script>

<template>
    <section class="mx-auto w-full max-w-2xl">
        <Card class="space-y-4 p-8 text-center">
            <h1 class="text-3xl font-bold tracking-tight">{{ $t('notFound.title') }}</h1>
            <p class="text-muted-foreground">{{ $t('error.notFound') }}</p>
            <Button as-child><router-link to="/">{{ $t('notFound.back') }}</router-link></Button>
        </Card>
    </section>
</template>
