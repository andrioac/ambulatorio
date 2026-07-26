# S04 — Auditoria Persistente e Segurança HTTP

## Objetivo

Transportar do Escolume a trilha de auditoria imutável e a camada defensiva HTTP antes do domínio clínico.

## Entregas implementadas

- tabela `registros_auditoria` append-only;
- estados anterior e posterior em JSONB;
- IP em tipo nativo PostgreSQL;
- escopo histórico de organização e unidade;
- trigger que rejeita `UPDATE` e `DELETE`;
- model com proteção adicional contra mutação;
- registrador central de eventos;
- sanitização recursiva de credenciais, tokens e segredos;
- registro de autenticação bem-sucedida;
- cabeçalhos `nosniff`, `DENY`, Referrer Policy e Permissions Policy;
- política de cache `no-store`;
- Content Security Policy compatível com Vite local;
- HSTS somente em produção HTTPS;
- rate limit de login por e-mail normalizado e IP.

## Regras para o domínio clínico

- acessos a prontuário deverão gerar evento de visualização;
- registros clínicos assinados não serão alterados ou excluídos;
- correções clínicas serão novos adendos;
- auditoria não armazenará senha, token, cookie, conteúdo clínico desnecessário ou e-mail tentado em falha de login.

## Validação necessária antes do merge

- executar migrations em PostgreSQL vazio;
- testar imutabilidade por SQL e Eloquent;
- validar cabeçalhos em desenvolvimento e produção simulada;
- testar rate limiting;
- executar Pint, testes backend, build e testes frontend.

## Fora do escopo

Cadastro de pacientes, prontuário, triagem e receituário passam para as sprints seguintes.
