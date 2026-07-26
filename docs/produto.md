# Visão do Produto

## Problema

Postos de saúde precisam coordenar recepção, identificação do paciente, espera, triagem, atendimento clínico e emissão de documentos sem perder o histórico, a responsabilidade profissional e a privacidade dos dados.

## Proposta

O Ambulatório Inteligente será um sistema web para organizar a jornada do paciente:

1. identificação ou cadastro;
2. abertura do atendimento;
3. entrada em fila;
4. acolhimento e triagem;
5. consulta clínica;
6. prescrição, solicitação ou encaminhamento;
7. encerramento e registro no prontuário longitudinal.

## Público inicial

- postos de saúde;
- ambulatórios municipais;
- pequenas unidades de atenção primária;
- equipes administrativas, de enfermagem e médicas.

## Perfis iniciais

- administrador do sistema;
- gestor da unidade;
- atendente ou recepcionista;
- técnico de enfermagem;
- enfermeiro;
- médico.

A categoria profissional e as permissões do usuário serão conceitos separados.

## Escopo da primeira versão

- unidades, salas, equipes e profissionais;
- autenticação e autorização;
- cadastro administrativo e clínico básico de pacientes;
- recepção e abertura de atendimento;
- fila com estados e chamada;
- triagem e sinais vitais;
- prontuário longitudinal;
- consulta estruturada em SOAP;
- diagnóstico, conduta e encaminhamento;
- receituário e documentos básicos;
- auditoria de acessos e alterações;
- relatórios operacionais essenciais.

## Fora do escopo inicial

- faturamento;
- estoque e dispensação de medicamentos;
- laboratório próprio;
- odontologia;
- vacinação;
- telemedicina;
- aplicativo móvel nativo;
- integração em produção com e-SUS APS;
- assinatura digital qualificada;
- CI/CD automatizado.

## Requisitos regulatórios e de segurança

- tratamento de dados de saúde como dados pessoais sensíveis;
- princípio do menor privilégio;
- trilha de auditoria;
- preservação de registros clínicos concluídos;
- correções por adendo ou versionamento;
- proteção de informações exibidas em painéis públicos;
- política de backup e recuperação;
- registro de autor, data, hora e unidade em ações clínicas.

## Critérios de sucesso da primeira versão

- um paciente percorre o fluxo completo sem controle paralelo em papel para a operação básica;
- cada ação relevante possui responsável e horário registrados;
- profissionais veem apenas dados necessários ao seu trabalho;
- gestores acompanham filas e tempos de espera;
- o histórico clínico permanece íntegro e consultável.
