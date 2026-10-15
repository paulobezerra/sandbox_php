# Dependências Vendor Frontend

Este diretório contém as bibliotecas JavaScript de terceiros servidas estaticamente, sem necessidade de ferramentas de build (Node.js, npm, bundlers).

## Bibliotecas atuais

- **Vue 3**: `vue.global.prod.js` (versão `3.5.13`)
- **Vue Router 4**: `vue-router.global.prod.js` (versão `4.5.0`)

## Como atualizar manualmente

Para atualizar as versões das bibliotecas, baixe as distribuições globais de produção (formato UMD/IIFE global) diretamente das versões desejadas (ex: via unpkg, jsDelivr ou repositório oficial):

```bash
# Exemplo para atualizar Vue (substitua <versao>):
curl -sL https://unpkg.com/vue@<versao>/dist/vue.global.prod.js -o public/assets/vendor/vue.global.prod.js

# Exemplo para atualizar Vue Router (substitua <versao>):
curl -sL https://unpkg.com/vue-router@<versao>/dist/vue-router.global.prod.js -o public/assets/vendor/vue-router.global.prod.js
```

Após atualizar, verifique a compatibilidade entre as versões e teste a navegação da aplicação.
