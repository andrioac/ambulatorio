## Subir os containers

```powershell
docker compose up -d --build
```

Aplicação: http://localhost:8082

PostgreSQL: `localhost:5433`

```text
Banco: ambulatorio
Usuário: ambulatorio
Senha: ambulatorio
```

## Verificar

```powershell
docker compose ps
docker compose logs -f app
```

## Parar

```powershell
docker compose down
```
