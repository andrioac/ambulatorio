# S04 — Cadastro de Pacientes

## Objetivo
Entregar identificação, cadastro, pesquisa e atualização administrativa dos pacientes.

## Escopo
- nome civil e nome social;
- CPF e CNS;
- nascimento, sexo, raça ou cor e filiação;
- contatos e endereço;
- responsável legal;
- unidade, equipe e território de referência quando disponíveis;
- alertas administrativos de possível duplicidade;
- busca por CPF, CNS, nome, nascimento, mãe e telefone;
- histórico básico de alterações cadastrais.

## Critérios de aceite
- CPF e CNS, quando informados, obedecem unicidade e validação definida;
- pesquisa tolera dados parciais sem expor informações indevidas;
- sistema alerta possíveis duplicidades antes de criar novo cadastro;
- atendente não altera informações clínicas;
- alterações guardam autor, data e unidade.

## Fora do escopo
Prontuário clínico, triagem e consulta.
