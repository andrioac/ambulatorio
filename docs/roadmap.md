# Roadmap de Desenvolvimento

O desenvolvimento será incremental. Cada sprint deve terminar com uma entrega demonstrável, documentação atualizada e critérios de aceite verificáveis.

| Sprint | Entrega principal |
|---|---|
| S00 | Ambiente Docker reproduzível |
| S01 | Laravel, Inertia e Vue funcionando |
| S02 | Autenticação, usuários, perfis e profissionais |
| S03 | Unidades, salas, equipes e vínculos |
| S04 | Cadastro e pesquisa de pacientes |
| S05 | Recepção, atendimento inicial e fila |
| S06 | Acolhimento, sinais vitais e triagem |
| S07 | Prontuário longitudinal e consulta SOAP |
| S08 | Receituário e documentos clínicos básicos |
| S09 | Auditoria, relatórios e preparação da primeira versão |

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
