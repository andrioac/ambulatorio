# Ambulatório Inteligente

Fundação inicial do sistema monolítico definido na documentação de planejamento.

## Stack

- Laravel 13 com PHP 8.4 e Composer;
- Vue 3, Inertia.js e Vite;
- PostgreSQL 16;
- Redis 7;
- Nginx;
- Mailpit.

## Subir o ambiente

```powershell
Copy-Item .env.example .env
docker compose up -d --build
docker compose exec app php artisan migrate
```

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

## Dependências e validações

```powershell
docker compose exec app composer install
docker compose exec node npm install
docker compose exec node npm run build
docker compose exec app php artisan test
docker compose ps
```

## Parar e limpar

```powershell
docker compose down
docker compose down -v
```

`docker compose down -v` remove os dados locais do PostgreSQL e Redis.
