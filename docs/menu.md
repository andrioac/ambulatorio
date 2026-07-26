# Mapa de Menus

A árvore de navegação é montada no `AppShell` e cada item depende de uma capacidade compartilhada pelo backend. Ocultar um item não substitui a autorização: todas as rotas disponíveis também validam a permissão no servidor.

## Visão geral

- **Visão geral** — `/`
  - disponível para qualquer usuário autenticado e ativo.

## Atendimento

- **Pacientes** — `/pacientes`
  - capacidade: `pacientes.visualizar`;
  - estado: planejado para S06.
- **Recepção e fila** — `/fila`
  - capacidade: `fila.visualizar`;
  - estado: planejado para S07.
- **Triagem** — `/triagem`
  - capacidade: `triagem.realizar`;
  - estado: planejado para S08.
- **Prontuário** — `/prontuario`
  - capacidade: `prontuario.visualizar`;
  - estado: planejado para S09.
- **Documentos clínicos** — `/documentos-clinicos`
  - capacidade: `prescricao.emitir`;
  - estado: planejado para S10.

## Administração

- **Organizações** — `/organizacoes`
  - visualizar: `organizacoes.visualizar`;
  - administrar: `organizacoes.administrar`;
  - estado: disponível.
- **Unidades de saúde** — `/unidades`
  - visualizar: `unidades.visualizar`;
  - administrar: `unidades.administrar`;
  - estado: disponível.
- **Profissionais** — `/profissionais`
  - visualizar: `profissionais.visualizar`;
  - administrar: `profissionais.administrar`;
  - estado: disponível.
- **Usuários e acessos** — `/usuarios`
  - visualizar: `usuarios.visualizar`;
  - administrar: `usuarios.administrar`;
  - estado: disponível.
- **Perfis e permissões** — `/perfis`
  - visualizar: `perfis.visualizar`;
  - estado: catálogo disponível;
  - criação de perfis personalizados depende de decisão futura sobre propriedade e alcance.
- **Salas e equipes** — `/estrutura`
  - capacidade inicial: `unidades.visualizar`;
  - estado: planejado; regras de salas, equipes e escalas ainda precisam de definição.
- **Auditoria** — `/auditoria`
  - capacidade: `auditoria.visualizar`;
  - estado: disponível.

## Gestão

- **Relatórios operacionais** — `/relatorios`
  - capacidade inicial: `auditoria.visualizar`;
  - estado: planejado para S11.

## Regras de apresentação

- item sem capacidade não é exibido;
- item autorizado mas ainda não implementado aparece como **Em breve** e não é clicável;
- ações de criar, editar, inativar, revogar ou reativar somente aparecem quando a capacidade administrativa correspondente está presente;
- o backend recalcula a autorização por usuário, perfil, vigência e escopo em todas as operações protegidas.
