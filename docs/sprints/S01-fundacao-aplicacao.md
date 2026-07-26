# S01 — Transplante da Fundação Escolume

## Objetivo

Trazer para o Ambulatório a fundação técnica genérica e já validada no Escolume, aproveitando a compatibilidade entre Laravel 13, Inertia.js e Vue 3.

## Escopo

- consolidar Laravel, Inertia, Vue, Vite, PostgreSQL, Redis e Mailpit;
- copiar a configuração local de qualidade, sem GitHub Actions;
- copiar layout administrativo, componentes básicos e páginas de erro;
- copiar máscaras, formatadores e testes Vitest;
- copiar autenticação, logout, recuperação e redefinição de senha;
- copiar regras de usuário ativo e segurança de sessão;
- remover identidade visual e referências ao domínio escolar;
- documentar a origem dos componentes transplantados.

## Histórias

### S01-H01 — Biblioteca de interface e formatadores

- `FormField` acessível e reutilizável;
- máscaras de CPF, CNPJ, CEP e telefone;
- máscara de CNS específica do Ambulatório;
- testes dos formatadores e do componente;
- estilos mínimos compartilhados.

### S01-H02 — Autenticação

- login e logout;
- recuperação e redefinição de senha;
- estado ativo do usuário;
- rate limit de autenticação;
- proteção contra enumeração;
- eventos de auditoria através de contrato substituível.

### S01-H03 — Layout administrativo

- shell administrativo;
- sidebar responsiva;
- cabeçalho e menu do usuário;
- navegação condicionada a capacidades;
- estados vazios e feedback;
- páginas 403, 404, 419 e 422.

### S01-H04 — Qualidade local

- Pint;
- PHPStan/Larastan;
- PHPUnit;
- TypeScript estrito;
- Prettier e ESLint;
- Vitest;
- scripts Composer e npm para execução dentro dos containers.

## Critérios de aceite

- aplicação abre pelo Nginx;
- Vite funciona em desenvolvimento e produção;
- conexão com PostgreSQL e Redis está validada;
- migrations e testes executam nos containers;
- autenticação e recuperação de senha funcionam;
- usuário inativo não autentica;
- componentes transplantados não contêm referências ao Escolume;
- máscaras possuem testes automatizados;
- nenhuma configuração de CI é adicionada;
- README contém os comandos oficiais.

## Estado atual

A S01-H01 foi iniciada nesta branch com o transplante do `FormField` e dos formatadores de CNPJ, CEP e telefone, além da inclusão inicial de CPF e CNS.

## Fora do escopo

- organizações e unidades de saúde;
- RBAC definitivo;
- auditoria persistente;
- cadastros e regras clínicas.
