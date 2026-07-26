# S03 — Autorização Escopada, Usuários e Perfis

## Objetivo

Transportar do Escolume a fundação de identidade global, perfis, permissões e atribuições por escopo.

## Entregas implementadas

- usuário com estado ativo/inativo;
- catálogo de permissões;
- perfis protegidos;
- vínculo perfil-permissão;
- atribuições nos escopos `sistema`, `organizacao` e `unidade`;
- vigência, ativação e revogação lógica;
- constraint PostgreSQL para impedir combinações inválidas de escopo;
- autorizador central no backend;
- catálogo estrutural idempotente em seeder;
- perfis iniciais de superadministrador, administrador da organização, gestor da unidade e auditor.

## Princípios preservados do Escolume

- contexto ativo não concede autorização;
- autorização definitiva ocorre no servidor;
- usuário é identidade global;
- profissão não é perfil de acesso;
- uma permissão somente vale quando perfil, atribuição, vigência e escopo são válidos.

## Próximas extensões

- interface administrativa de usuários e atribuições;
- comando explícito de bootstrap do superadministrador;
- regras de delegação e proteção contra autoelevação;
- contexto ativo persistido em sessão;
- testes de isolamento entre organizações e unidades.
