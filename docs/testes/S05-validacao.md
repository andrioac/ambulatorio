# Roteiro de Validação — S05

## Preparação

1. atualizar a branch local;
2. reconstruir os containers PHP;
3. executar migrations e o catálogo de autorização;
4. confirmar que `app`, `web`, `node`, `db`, `redis`, `mailpit` e `queue` estão ativos;
5. entrar com um superadministrador.

## Validação automatizada

```bash
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec node npm run build
```

## Menus e capacidades

- superadministrador visualiza todos os itens administrativos disponíveis;
- usuário sem permissão não visualiza o item correspondente e recebe 403 ao acessar a URL manualmente;
- itens clínicos planejados aparecem como `Em breve` somente quando o usuário possui a capacidade correspondente;
- em resolução móvel, o menu abre, fecha e não bloqueia a página após a navegação;
- páginas 403 e 404 utilizam o layout unificado de erro.

## Organizações e unidades

- cadastrar organização com e sem CNPJ;
- validar unicidade e normalização do CNPJ;
- cadastrar unidade com e sem CNES;
- validar unicidade do CNES;
- impedir unidade ativa em organização inativa;
- confirmar isolamento entre duas organizações;
- confirmar auditoria de criação e atualização.

## Usuários e acessos

- cadastrar usuário ativo;
- redefinir senha administrativamente pela edição do usuário;
- criar atribuição nos escopos sistema, organização e unidade conforme o alcance do ator;
- impedir autoelevação de privilégio;
- impedir atribuição de perfil com permissões que o ator não pode delegar;
- impedir revogação do próprio superadministrador;
- impedir inativação do último superadministrador ativo;
- confirmar que usuário inativado perde uma sessão já aberta;
- confirmar que atribuição vencida, unidade inativa ou organização inativa não concede capacidade.

## Profissionais e vínculos

- cadastrar profissional sem usuário de acesso como superadministrador;
- cadastrar profissional com unidade inicial como gestor de unidade;
- impedir gestor de cadastrar profissional sem unidade inicial;
- impedir associação com usuário fora do escopo;
- cadastrar CPF, CNS, CBO e conselho;
- criar, editar, desativar e reativar vínculo;
- impedir vínculo ou reativação com unidade ou organização inativa;
- confirmar isolamento entre unidades;
- confirmar auditoria de todas as alterações.

## Auditoria

- filtrar por evento, ator, entidade, organização, unidade e período;
- confirmar que gestor de unidade não visualiza eventos de outra unidade da mesma organização;
- confirmar que administrador da organização visualiza os eventos de seu alcance;
- conferir estados anterior e posterior;
- tentar atualizar ou excluir um registro de auditoria e confirmar a proteção append-only.

## Recuperação de senha

1. solicitar recuperação em `/esqueci-minha-senha`;
2. confirmar a mensagem genérica e a espera de 60 segundos;
3. verificar o job no worker da fila;
4. abrir o e-mail no Mailpit;
5. redefinir a senha;
6. confirmar login com a nova senha;
7. confirmar que o token não pode ser reutilizado;
8. confirmar que usuário inativo não recebe um fluxo utilizável.

## Validação opcional em banco vazio

Esta operação remove os dados locais existentes:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Execute somente em ambiente descartável.
