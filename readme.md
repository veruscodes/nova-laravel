# Nova Laravel

Fork/customizacao do Laravel Nova com interface baseada em Inertia.js, Vue 3 e Tailwind CSS.

O objetivo deste repositorio e reunir a camada de interface, assets e codigo-fonte principal do Nova em uma base pronta para evolucao visual, ajustes de comportamento e manutencao do painel administrativo.

## Preview

<img width="1570" height="773" alt="Captura de tela 2025-11-04 115638" src="https://github.com/user-attachments/assets/57b1de1f-850f-4edc-a945-04ce6af63b28" />
<img width="3008" height="2014" alt="laravel-nova-mockup-dark" src="https://github.com/user-attachments/assets/3b90b869-5d73-459a-9cf0-e137809dd003" />

## O que tem aqui

- Backend em PHP com namespace `Laravel\\Nova\\` dentro de `src/`
- Frontend em `resources/js` usando Vue 3, Inertia.js e Vuex
- Estilos em `resources/css` com Tailwind CSS
- Rotas e configuracoes do Nova em `routes/` e `config/nova.php`
- Build de assets com Laravel Mix
- Scripts de teste, sincronizacao e ambiente de desenvolvimento via Composer

## Stack principal

- PHP 8.1+
- Laravel components 10, 11 e 12
- Inertia Laravel 1.x e 2.x
- Vue 3
- Tailwind CSS 3
- Jest para testes de frontend
- PHPUnit / Testbench para fluxo de testes e workbench

## Estrutura resumida

```text
src/            Codigo principal do pacote
resources/js/   Componentes, paginas, composables e store do frontend
resources/css/  Estilos globais e temas
routes/         Rotas HTTP, API e assets
config/         Configuracao do Nova
database/       Migrations do painel
public/         Assets compilados
```

## Como rodar localmente

Este repositorio esta estruturado como pacote do Nova, nao como uma aplicacao Laravel tradicional.

### Requisitos

- PHP 8.1 ou superior
- Composer
- Node.js 18+ recomendado
- NPM

### Instalacao

```bash
composer install
npm install
```

### Desenvolvimento

Para subir os assets em modo de desenvolvimento:

```bash
npm run dev
```

Para preparar e servir o ambiente de workbench usado pelo pacote:

```bash
composer run serve
```

## Scripts uteis

```bash
# Build de desenvolvimento
npm run dev

# Watch de assets
npm run watch

# Build de producao
npm run prod

# Testes de frontend
npm test

# Testes PHP
composer test

# Lint e analise estatica
composer run lint

# Sincroniza workbench e assets
composer run build:sync
```

## Personalizacao

Os pontos mais comuns de customizacao estao aqui:

- `config/nova.php`: path, middleware, branding, disco, paginacao e guard
- `resources/js/components`: componentes compartilhados da UI
- `resources/js/pages`: telas principais do painel
- `resources/css`: identidade visual e estilos globais

O path padrao da interface esta configurado como `/nova`.

## Licenciamento

O arquivo `config/nova.php` usa `NOVA_LICENSE_KEY` para validacao em ambientes nao locais. Se voce for executar este fork fora de ambiente local, confira os requisitos de licenciamento do Laravel Nova.

## Status do repositorio

- Branch atual: `main`
- Sincronizado com `origin/main`
- Ultimo commit local no momento desta atualizacao: `a8462f5`
