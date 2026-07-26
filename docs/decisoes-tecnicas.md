# Decisões Técnicas Iniciais

## Stack

- PHP compatível com Laravel 13.
- Laravel 13.
- Vue 3.
- Inertia.js.
- Vite.
- PostgreSQL.
- Redis.
- Docker Compose.

## Arquitetura

- monólito modular;
- uma base de código;
- uma aplicação web;
- um banco compartilhado com separação lógica por unidade;
- sem API pública na primeira versão;
- sem microsserviços;
- sem CI no início.

## Ambiente

O Docker será a forma oficial de desenvolvimento. A aplicação não dependerá de PHP, Composer, Node ou PostgreSQL instalados diretamente na máquina do desenvolvedor.

## Qualidade

Mesmo sem CI, cada entrega deverá executar localmente:

- análise de estilo;
- testes automatizados;
- build do frontend;
- migrations em banco limpo.

## Segurança

- autenticação por sessão;
- CSRF habilitado;
- autorização no backend;
- senhas com o hash padrão seguro do Laravel;
- logs sem conteúdo clínico desnecessário;
- segredos apenas em variáveis de ambiente;
- arquivos sensíveis fora do diretório público.

## Pendências que exigirão decisão posterior

- biblioteca de componentes visuais;
- estratégia de armazenamento de anexos;
- assinatura eletrônica ou digital;
- mecanismo de identificação inequívoca do paciente;
- integração e exportação para ecossistema SUS;
- estratégia de deploy de produção;
- adoção futura de CI/CD.
