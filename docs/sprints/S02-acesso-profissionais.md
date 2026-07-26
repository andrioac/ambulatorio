# S02 — Acesso, Usuários e Profissionais

## Objetivo
Entregar autenticação, autorização e cadastro dos profissionais da unidade.

## Escopo
- login, logout e recuperação de senha;
- usuários ativos e inativos;
- perfis e permissões;
- cadastro central de profissionais;
- categorias: atendente, técnico de enfermagem, enfermeiro, médico e gestor;
- CNS, CBO, conselho, número e UF quando aplicável;
- separação entre categoria profissional e perfil de acesso;
- trilha mínima de eventos de autenticação.

## Critérios de aceite
- usuário inativo não acessa o sistema;
- permissões são validadas no backend;
- profissional pode ter usuário ou existir sem acesso;
- dados de conselho são condicionais à categoria;
- testes cobrem autenticação e autorizações críticas.

## Fora do escopo
Unidades completas, pacientes e atendimento clínico.
