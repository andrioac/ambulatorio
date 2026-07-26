# S11 — Auditoria, Relatórios e Primeira Versão

## Objetivo

Consolidar controles de segurança, indicadores operacionais e preparação da primeira versão utilizável.

## Escopo

- auditoria de autenticação, acesso ao prontuário e alterações relevantes;
- filtros por usuário, paciente, unidade, ação e período;
- atendimentos por unidade, profissional e período;
- tempos médios de espera e atendimento;
- abandonos, ausências e cancelamentos;
- produtividade por etapa;
- revisão de permissões e proteção de dados;
- política e teste de backup e restauração;
- documentação de operação e deploy manual;
- revisão completa dos fluxos da primeira versão.

## Critérios de aceite

- auditoria não pode ser alterada pela interface comum;
- relatórios respeitam escopo de unidade e permissão;
- backup e restauração são executados em ambiente controlado;
- fluxo completo da recepção ao encerramento é demonstrável;
- testes críticos passam localmente;
- migrations executam em banco vazio;
- build de produção é gerado;
- riscos e pendências da próxima versão estão documentados.

## Fora do escopo

CI/CD, integrações oficiais, faturamento, estoque, laboratório e aplicativo móvel.
