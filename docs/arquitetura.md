# Arquitetura

## Estilo

A aplicação será um monólito modular em Laravel 13. O frontend Vue 3 será entregue pelo mesmo projeto usando Inertia.js. Não haverá API separada nem microsserviços na primeira versão.

## Componentes principais

- Nginx: entrada HTTP no ambiente Docker.
- PHP-FPM: execução da aplicação Laravel.
- Node.js: compilação dos ativos com Vite.
- PostgreSQL: persistência transacional.
- Redis: cache, sessões e filas assíncronas quando necessário.
- Mailpit: captura de e-mails em desenvolvimento.

## Organização sugerida

A estrutura padrão do Laravel será preservada. Os domínios serão separados por namespaces e serviços de aplicação, evitando uma arquitetura excessivamente abstrata no início.

Domínios iniciais:

- Identidade e Acesso;
- Estrutura Organizacional;
- Profissionais;
- Pacientes;
- Atendimento;
- Fila;
- Triagem;
- Prontuário;
- Prescrição e Documentos;
- Auditoria e Relatórios.

## Diretrizes de implementação

- controllers pequenos e orientados à coordenação;
- validação em Form Requests;
- autorização por Policies e Gates;
- regras de negócio em Actions ou Services explícitos;
- transações para mudanças que envolvam vários registros;
- enums PHP para estados estáveis;
- eventos de domínio apenas quando reduzirem acoplamento real;
- jobs para processamento demorado, nunca para ocultar regras centrais;
- testes Feature para fluxos e Unit para regras isoladas.

## Multiunidade

A primeira versão suportará múltiplas unidades no mesmo banco. O acesso será restrito por vínculo do usuário com uma ou mais unidades. Não será adotado banco separado por unidade.

## Dados clínicos

- registros clínicos concluídos serão imutáveis;
- correções ocorrerão por adendo ou nova versão;
- exclusão física será evitada para entidades clínicas;
- auditoria registrará ator, unidade, ação, objeto, data, IP e contexto disponível;
- dados administrativos e clínicos terão permissões distintas.

## Integrações futuras

A modelagem deve reservar identificadores como CNS, CNES, CBO, CID-10 e CIAP-2. A integração oficial com sistemas do SUS dependerá de descoberta técnica e validação normativa específica.

## Deploy inicial

O projeto será hospedado no GitHub. Não haverá CI no início. O processo inicial será manual e documentado, com comandos reproduzíveis para build, testes, migrations e publicação.
