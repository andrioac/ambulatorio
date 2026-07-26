# Ambulatório Inteligente

Sistema web para organizar a jornada do paciente com autenticação, autorização escopada, auditoria imutável e gestão por organizações e unidades de saúde.

## Stack

- Laravel 13 com PHP 8.4 e Composer;
- Vue 3, Inertia.js e Vite;
- PostgreSQL 16;
- Redis 7 para cache, sessões e filas;
- Nginx;
- Mailpit.

## Subir o ambiente

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

O serviço `queue` executa o worker de filas. O container `node` instala dependências somente quando o `package-lock.json` muda.

## Portas

| Serviço | Porta local |
|---|---:|
| Aplicação | 8082 |
| PostgreSQL | 5433 |
| Redis | 6380 |
| Vite | 5174 |
| Mailpit Web | 8026 |
| Mailpit SMTP | 1026 |

Aplicação: http://localhost:8082  
Mailpit: http://localhost:8026

## Criar o primeiro administrador

Crie a identidade de acesso:

```bash
docker compose exec app php artisan ambulatorio:criar-usuario "Administrador" admin@example.com --senha="Senha1234"
```

Depois atribua o perfil global de superadministrador:

```bash
docker compose exec app php artisan ambulatorio:atribuir-superadministrador admin@example.com --force
```

Sem argumentos ou opções, os comandos solicitam os dados interativamente. A senha é solicitada de forma oculta.

## Módulos administrativos disponíveis

- `/organizacoes`: organizações de saúde;
- `/unidades`: unidades de atendimento;
- `/profissionais`: profissionais e vínculos com unidades;
- `/usuarios`: usuários e atribuições de perfil;
- `/perfis`: catálogo de perfis e permissões;
- `/auditoria`: consulta escopada da trilha de auditoria;
- `/esqueci-minha-senha`: recuperação assíncrona de senha.

Os menus e as ações são apresentados conforme as capacidades do usuário. A autorização definitiva sempre ocorre no backend.

## Atualizar o ambiente depois de um `git pull`

```bash
docker compose down
docker compose build app queue
docker compose up -d --force-recreate
docker compose exec app composer install
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed --class=CatalogoAutorizacaoSeeder
```

O script do container Node executará `npm ci` automaticamente se o lockfile tiver mudado. Para forçar a reinstalação:

```bash
docker compose run --rm node npm ci --no-audit --no-fund
```

## Validações locais

```bash
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint --test
docker compose exec node npm run build
docker compose ps
docker compose logs --tail=100 queue
```

Para validar o banco desde o início, operação que remove os dados locais da aplicação:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

## Parar e limpar

```bash
docker compose down
docker compose down -v
```

`docker compose down -v` remove os dados locais do PostgreSQL, Redis e os demais volumes do Compose.
