# S05 — Profissionais e Vínculos com Unidades

## Objetivo

Entregar o cadastro central de profissionais, mantendo separadas a identidade profissional e a identidade de acesso, com atuação autorizada por unidade de saúde.

## Entregas

- cadastro de profissional com nome, CPF, CNS, categoria, CBO e registro em conselho;
- associação opcional e única com usuário de acesso;
- ativação e inativação lógica;
- vínculo do profissional com uma ou mais unidades;
- vigência inicial e final do vínculo;
- criação, edição, desativação e reativação de vínculos;
- bloqueio de novos vínculos com unidade ou organização inativa;
- exigência de unidade inicial para cadastros realizados por gestores sem alcance de sistema;
- pesquisa e filtros por nome, documento, categoria e situação;
- autorização definitiva no backend por perfil, permissão e escopo;
- isolamento de profissionais entre unidades não autorizadas;
- navegação e ações condicionadas às capacidades do usuário;
- auditoria de criação e atualização do profissional e de todas as mudanças de vínculo;
- testes de interface, autorização, isolamento e auditoria.

## Permissões

- `profissionais.visualizar`: lista e consulta profissionais alcançados pelo escopo vigente;
- `profissionais.administrar`: cadastra, atualiza e gerencia vínculos dentro do escopo vigente.

A profissão não concede acesso ao sistema. Perfis e permissões continuam sendo atribuídos separadamente ao usuário.

## Critérios de aceite

- profissional pode existir sem usuário de acesso;
- um usuário pode estar associado a no máximo um profissional;
- CPF e CNS são únicos quando informados;
- gestor de unidade somente visualiza e altera profissionais vinculados a unidades autorizadas;
- cadastro realizado por gestor já nasce vinculado a uma unidade autorizada;
- unidade ou organização inativa não recebe novo vínculo nem permite reativação;
- alterações relevantes geram registros de auditoria imutáveis;
- migrations funcionam em PostgreSQL vazio;
- testes, Pint e build de produção passam localmente.

## Decisões futuras

As regras de sobreposição temporal entre múltiplos vínculos do mesmo profissional com a mesma unidade deverão ser definidas antes de introduzir escalas, agenda ou carga horária. A S05 preserva vínculos históricos e impede apenas a duplicidade estrutural já protegida pela chave única existente.
