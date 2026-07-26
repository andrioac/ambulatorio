# Decisões Técnicas Iniciais

## Stack

- PHP compatível com Laravel 13.
- Laravel 13.
- Vue 3.
- Inertia.js.
- Vite.
- PostgreSQL.
- Redis.
- Docker Compose.

## Arquitetura

- monólito modular;
- uma base de código;
- uma aplicação web;
- um banco compartilhado com separação lógica por unidade;
- sem API pública na primeira versão;
- sem microsserviços;
- sem CI no início.

## Reutilização da fundação Escolume

O repositório `andrioac/escolume` será usado como origem técnica para o transplante de componentes genéricos porque utiliza a mesma stack e arquitetura.

Serão copiados e adaptados:

- autenticação e recuperação de senha;
- autorização por perfis, permissões e atribuições escopadas;
- contexto ativo e capacidades calculadas no servidor;
- administração de usuários;
- auditoria persistente append-only;
- segurança HTTP e sanitização de logs;
- layout administrativo, componentes de interface e máscaras;
- testes e convenções de qualidade.

Após a cópia, os projetos não terão dependência de código entre si. Correções futuras deverão ser portadas conscientemente quando forem aplicáveis aos dois produtos.

Não serão transportados módulos acadêmicos, dados de demonstração, identidade visual do Escolume, arquivos de ambiente, segredos ou workflows de GitHub Actions.

## Equivalência de escopos

| Escolume | Ambulatório |
|---|---|
| Sistema | Sistema |
| Rede escolar | Organização de saúde |
| Escola | Unidade de saúde |
| Administrador de rede | Administrador da organização |
| Gestor escolar | Gestor da unidade |

O contexto ativo orienta a navegação, mas nunca concede autorização sozinho. A autorização definitiva continuará sendo calculada no backend.

## Ambiente

O Docker será a forma oficial de desenvolvimento. A aplicação não dependerá de PHP, Composer, Node ou PostgreSQL instalados diretamente na máquina do desenvolvedor.

## Qualidade

Mesmo sem CI, cada entrega deverá executar localmente:

- análise de estilo;
- testes automatizados;
- build do frontend;
- migrations em banco limpo.

## Segurança

- autenticação por sessão;
- CSRF habilitado;
- autorização no backend;
- senhas com o hash padrão seguro do Laravel;
- logs sem conteúdo clínico desnecessário;
- segredos apenas em variáveis de ambiente;
- arquivos sensíveis fora do diretório público;
- auditoria de acesso a informações clínicas;
- registros clínicos concluídos sem exclusão física.

## Pendências que exigirão decisão posterior

- estratégia de armazenamento de anexos;
- assinatura eletrônica ou digital;
- mecanismo de identificação inequívoca do paciente;
- integração e exportação para ecossistema SUS;
- estratégia de deploy de produção;
- adoção futura de CI/CD.
