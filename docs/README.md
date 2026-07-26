# Documentação do Ambulatório Inteligente

## Objetivo

Construir um sistema web monolítico para apoiar o fluxo de atendimento de postos de saúde e ambulatórios, desde a recepção do paciente até a conclusão clínica do atendimento.

## Tecnologia definida

- Backend: PHP com Laravel 13.
- Frontend: Vue 3 integrado por Inertia.js.
- Arquitetura: monólito modular.
- Banco de dados: PostgreSQL.
- Ambiente de desenvolvimento: Docker Compose.
- Repositório e gestão do código: GitHub.
- CI: fora do escopo inicial.

## Documentos

- [Visão do produto](produto.md)
- [Arquitetura](arquitetura.md)
- [Roadmap](roadmap.md)
- [Decisões técnicas](decisoes-tecnicas.md)

## Sprints

1. [S00 — Ambiente Docker](sprints/S00-ambiente-docker.md)
2. [S01 — Fundação da aplicação](sprints/S01-fundacao-aplicacao.md)
3. [S02 — Acesso, usuários e profissionais](sprints/S02-acesso-profissionais.md)
4. [S03 — Unidades e estrutura operacional](sprints/S03-unidades-estrutura.md)
5. [S04 — Cadastro de pacientes](sprints/S04-pacientes.md)
6. [S05 — Recepção e fila](sprints/S05-recepcao-fila.md)
7. [S06 — Triagem e acolhimento](sprints/S06-triagem.md)
8. [S07 — Prontuário e consulta](sprints/S07-prontuario-consulta.md)
9. [S08 — Receituário e documentos](sprints/S08-receituario.md)
10. [S09 — Auditoria, relatórios e preparação da primeira versão](sprints/S09-auditoria-relatorios.md)

## Princípios

- Dados clínicos são sensíveis e exigem controle de acesso e rastreabilidade.
- Registros clínicos concluídos não serão excluídos fisicamente.
- Perfis de acesso não serão confundidos com categorias profissionais.
- O sistema nascerá preparado para múltiplas unidades de saúde.
- Compatibilidade futura com padrões e cadastros do SUS será considerada na modelagem, sem prometer integração na primeira versão.
