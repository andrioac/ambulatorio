# Roadmap de Desenvolvimento

O desenvolvimento será incremental. Cada sprint deve terminar com uma entrega demonstrável, documentação atualizada e critérios de aceite verificáveis.

O Escolume é a base técnica de referência do projeto. Componentes genéricos compatíveis com Laravel 13, Inertia.js e Vue 3 serão transplantados para o Ambulatório e mantidos de forma independente após a cópia.

| Sprint | Entrega principal | Estado |
|---|---|---|
| S00 | Ambiente Docker reproduzível | Concluída |
| S01 | Transplante da fundação técnica do Escolume | Concluída |
| S02 | Organizações e unidades de saúde | Fundação concluída; interface incluída na S05 |
| S03 | Autorização escopada, usuários e perfis | Fundação concluída; interface incluída na S05 |
| S04 | Auditoria persistente e segurança HTTP | Fundação concluída; consulta incluída na S05 |
| S05 | Profissionais e vínculos com unidades | Em validação |
| S06 | Cadastro e pesquisa de pacientes | Planejada |
| S07 | Recepção, atendimento inicial e fila | Planejada |
| S08 | Acolhimento, sinais vitais e triagem | Planejada |
| S09 | Prontuário longitudinal e consulta SOAP | Planejada |
| S10 | Receituário e documentos clínicos básicos | Planejada |
| S11 | Relatórios, hardening e preparação da primeira versão | Planejada |

## Entregas administrativas consolidadas na S05

Para tornar a gestão de profissionais demonstrável e segura, a S05 também conclui as interfaces administrativas que dependiam da fundação anterior:

- organizações e unidades de saúde;
- usuários e atribuições de perfil;
- catálogo de perfis e permissões;
- consulta escopada da auditoria;
- capacidades compartilhadas com o Inertia;
- menus condicionados às permissões;
- páginas de erro 403, 404, 419 e 422.

## Estratégia de transplante

A S01 reutilizará do Escolume:

- autenticação, logout e recuperação de senha;
- layout administrativo e componentes de formulário;
- máscaras e formatadores de campos;
- páginas de erro e tratamento Inertia;
- configuração de testes e análise estática;
- padrões de sessão, logs e segurança básica.

As sprints S02 a S04 reutilizarão, com adaptação nominal do domínio:

- contexto ativo;
- perfis, permissões e atribuições escopadas;
- administração de usuários;
- auditoria append-only;
- segurança HTTP e sanitização de logs.

Não serão copiados módulos acadêmicos, dados de demonstração, identidade visual, segredos ou GitHub Actions.

## Regra de encerramento de sprint

Uma sprint somente será considerada concluída quando:

- os critérios de aceite estiverem atendidos;
- os testes previstos estiverem passando localmente;
- as migrations funcionarem em banco vazio;
- o frontend gerar build de produção;
- não houver segredo versionado;
- a documentação relevante estiver atualizada;
- o fluxo entregue puder ser demonstrado.

## Estratégia GitHub inicial

- `master`: branch estável;
- branches por sprint ou história;
- pull requests para revisão antes do merge;
- commits pequenos e intencionais;
- sem GitHub Actions neste primeiro ciclo;
- validações executadas manualmente e registradas no pull request.
