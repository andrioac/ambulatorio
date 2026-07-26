# S00 — Ambiente Docker

## Objetivo

Entregar um ambiente de desenvolvimento reproduzível antes de iniciar a aplicação Laravel.

## Escopo

- definir versões de PHP, Node.js, PostgreSQL, Redis e Nginx;
- criar `compose.yaml`;
- criar Dockerfile da aplicação;
- configurar Nginx;
- criar volumes persistentes para banco e dependências quando apropriado;
- disponibilizar Mailpit para desenvolvimento;
- criar `.env.example` inicial;
- criar comandos de conveniência em Makefile ou scripts;
- documentar instalação, inicialização, parada, limpeza e diagnóstico;
- configurar usuário do container para evitar arquivos pertencentes a root;
- preparar healthchecks.

## Serviços esperados

- `app`: PHP-FPM e Composer;
- `web`: Nginx;
- `node`: execução do Vite;
- `db`: PostgreSQL;
- `redis`: cache e filas;
- `mailpit`: e-mails locais.

## Entregáveis

- `compose.yaml`;
- arquivos em `docker/`;
- `.dockerignore`;
- `.env.example`;
- `Makefile` ou scripts equivalentes;
- instruções no README.

## Critérios de aceite

- `docker compose up -d` inicia todos os serviços;
- os serviços possuem nomes e portas documentados;
- PostgreSQL e Redis respondem aos healthchecks;
- um arquivo PHP simples pode ser servido pelo Nginx;
- Composer e Node podem ser executados dentro dos containers;
- reiniciar os containers não perde os dados do PostgreSQL;
- a limpeza completa do ambiente está documentada;
- nenhum segredo real é versionado.

## Fora do escopo

- instalação do Laravel;
- autenticação;
- domínio clínico;
- deploy de produção;
- CI.
