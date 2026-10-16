# FEATURE — Simplificação do skeleton e do blog

**ID:** 002-simplificacao-skeleton · **Estado:** proposta · **Data:** 2026-10-16

## Necessidade de negócio

Quem usa o skeleton como ponto de partida encontra funcionalidades demonstrativas que aumentam a superfície do produto e o trabalho de adaptação sem servir ao objetivo atual. O CRUD de Plan já saiu da interface padrão, mas permanece como módulo legado opcional. O mini CMS ainda oferece Posts e Pages, embora o blog precise apenas de Posts. A recuperação de senha por códigos exige que a pessoa guarde códigos previamente e traz uma experiência que não se deseja manter. A apresentação do próprio sandbox_php está espalhada por páginas de recursos, arquitetura e guia, em vez de estar reunida na Home. Na autenticação, há limites de tentativas vinculados ao IP de origem; pessoas em uma mesma rede podem compartilhar esse IP e sofrer bloqueios umas pelas outras.

O código, os testes, as migrations e a documentação também ainda carregam referências a essas capacidades. O usuário aponta outras funcionalidades sem uso no aplicativo de produção. A intenção é identificá-las e deixar a base mais simples para quem a instala, mantém e adapta, preservando as funcionalidades que continuam fazendo parte do produto.

## Resultado desejado

O produto oferece apenas registro de usuário, login, blog e CRUD de Posts, com saída da sessão para quem estiver autenticado. Administradores gerenciam Posts; visitantes leem os publicados. A Home reúne a documentação do sandbox_php apresentada no site, sem páginas separadas para recursos, arquitetura ou guia e sem teste ou atalho de health no seu conteúdo. O menu público contém exatamente Home, Blog e Login. Após o login, Login sai do menu e aparecem Admin, para quem tem permissão administrativa, e Sair. Plan, Pages e recuperação de senha deixam de aparecer como recursos disponíveis, inclusive nas interfaces e na API. Pessoas que compartilham um IP conseguem se cadastrar e entrar sem que as tentativas de outra pessoa nesse IP, por si só, bloqueiem seu acesso. Outras funcionalidades sem uso são identificadas e retiradas, junto com código, testes, migrations e documentação exclusivos delas. Mecanismos usados em um único lugar também saem quando sua utilidade não justifica a complexidade que acrescentam.

## Limites e contexto

- **Inclui:** retirar os fluxos de Plan, Pages e recuperação de senha por códigos, além de suas referências, testes e migrations; concentrar na Home a documentação do sandbox_php exibida no site e retirar as páginas de apresentação separadas e o teste/atalho de health da Home; aplicar o menu conforme o estado de autenticação; investigar o aplicativo padrão e os procedimentos necessários para instalá-lo e operá-lo, identificando outras funcionalidades sem uso real; retirar os artefatos exclusivos dessas funcionalidades; avaliar mecanismos pontuais de baixo valor que aumentem a complexidade; revisar os limites de autenticação que usam o IP compartilhado como motivo de bloqueio; manter cobertura para os comportamentos que continuarem disponíveis.
- **Fora do escopo:** ampliar o CMS além de Posts ou acrescentar novas funcionalidades de autenticação.
- **Restrições de produto:** preservar cadastro, login, logout, sessão, autorização administrativa, proteção das operações mutáveis, publicação e leitura de Posts, bem como os procedimentos necessários para instalar e operar esses recursos. O registro continua acessível a partir do fluxo de Login, sem acrescentar um quarto item ao menu público. O link Admin não concede permissão; a autorização continua no backend. O uso em um único lugar, por si só, não justifica remoção: é preciso demonstrar ausência de valor relevante e que a simplificação não prejudica segurança, integridade dos dados ou operação. Existe apenas esta instalação de desenvolvimento; compatibilidade de atualização e preservação de registros de Plan ou Pages não são requisitos de produto. As migrations antigas que criam entidades removidas devem ser eliminadas ou ajustadas quando também criarem entidades mantidas; não devem restar tabelas dessas funcionalidades em uma instalação nova. Esta feature substitui, para o produto futuro, as partes correspondentes da spec histórica `001-skeleton`, sem reescrever seu registro.

## Sinais de sucesso

- Uma instalação nova apresenta somente Posts no blog e na administração, sem navegação, formulários ou API de Plan, Pages ou recuperação de senha.
- A Home permite consultar a documentação apresentada pelo site em um só lugar, sem depender de páginas separadas de recursos, arquitetura ou guia e sem mostrar teste ou atalho de health.
- Antes do login, o menu mostra apenas Home, Blog e Login. Depois do login, Login desaparece, Sair aparece e Admin aparece para uma conta autorizada a administrar Posts; o registro permanece acessível pelo fluxo de Login.
- Uma instalação nova não cria tabelas para as funcionalidades retiradas.
- Duas pessoas na mesma rede podem autenticar suas contas sem que as tentativas de uma bloqueiem a outra apenas por compartilharem IP; abuso contra uma conta ainda recebe proteção adequada.
- O inventário identifica o que foi retirado e por quê, incluindo funcionalidades não usadas no aplicativo padrão e mecanismos pontuais sem benefício proporcional à sua complexidade; código, testes, migrations e documentação correspondem às capacidades mantidas.
