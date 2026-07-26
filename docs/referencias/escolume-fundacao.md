# Referência técnica — Fundação Escolume

## Decisão

O Ambulatório reutilizará diretamente a fundação genérica implementada no Escolume. A compatibilidade de stack permite copiar arquivos, testes e convenções, substituindo apenas nomes, escopos e regras pertencentes ao domínio escolar.

## Ordem de transplante

1. componentes visuais, campos e formatadores;
2. autenticação, sessão e recuperação de senha;
3. layout administrativo e páginas de erro;
4. organizações e unidades de saúde;
5. perfis, permissões, atribuições e contexto ativo;
6. administração de usuários;
7. auditoria append-only;
8. segurança HTTP e sanitização de logs.

## Componentes de interface

A biblioteca visual poderá ser copiada integralmente como ponto de partida. Devem ser preservados:

- acessibilidade e associação de rótulos;
- apresentação de ajuda e erros;
- navegação por teclado;
- comportamento responsivo;
- máscaras executadas durante a entrada;
- normalização definitiva no backend;
- testes Vitest dos componentes e formatadores.

## Segurança

A auditoria do Ambulatório deverá manter a imutabilidade implementada no Escolume, mas ampliar o catálogo para registrar consultas a dados clínicos. Visualizar prontuário, imprimir documento clínico e acessar paciente fora do fluxo normal serão eventos auditáveis.

## Restrições

- não copiar `.env` ou credenciais;
- não copiar dados de demonstração;
- não copiar workflows de CI neste ciclo;
- não manter nomes Escolume no código final;
- não transportar entidades acadêmicas;
- não conceder acesso clínico apenas pela profissão do usuário;
- revisar qualquer evento de auditoria para impedir armazenamento indevido de conteúdo clínico.

## Critério de conclusão do transplante

O transplante somente estará concluído quando buscas no repositório não encontrarem referências funcionais ao domínio escolar e quando a suíte local, migrations em PostgreSQL limpo e build de produção estiverem aprovados.
