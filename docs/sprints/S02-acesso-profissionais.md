# S02 — Organizações e Unidades de Saúde

## Objetivo

Substituir a hierarquia escolar do Escolume pela hierarquia operacional do Ambulatório.

## Entregas implementadas

- tabela `organizacoes_saude`;
- tabela `unidades_saude`;
- vínculo obrigatório da unidade com a organização;
- CNES opcional e único;
- situação ativa/inativa;
- models e relacionamentos;
- restrição de exclusão da organização enquanto possuir unidades.

## Equivalência com o Escolume

- rede escolar → organização de saúde;
- escola → unidade de saúde;
- sistema → sistema.

## Critérios de aceite técnico

- migrations executam em PostgreSQL vazio;
- uma unidade não existe sem organização;
- CNPJ e CNES preservam zeros à esquerda;
- organizações e unidades inativas não devem conceder novas operações;
- consultas futuras devem partir de escopo autorizado.

## Continuidade

Salas, equipes e vínculos profissionais foram movidos para a sprint de profissionais, após a fundação de autorização.
