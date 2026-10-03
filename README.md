# PHP CRM Assessment Starter

This is a standalone, clean-room hiring exercise. It contains no production CRM code or data.

## Requirements

- Docker Desktop with Docker Compose
- Git

## Start the project

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app composer setup
```

On Windows PowerShell, copy the environment file with:

```powershell
Copy-Item .env.example .env
```

Open <http://localhost:8080>.

Useful checks:

```bash
curl http://localhost:8080/health
curl -H "X-User-Id: 1" http://localhost:8080/api/customers
curl -H "X-User-Id: 2" http://localhost:8080/api/customers
docker compose exec app composer test
```

User `1` is an administrator. Users `2` and `3` are agents assigned to different customers.

## Commands

```bash
docker compose exec app composer migrate
docker compose exec app composer seed
docker compose exec app composer test
```

## Architecture

The starter uses a deliberately small Controller/Service/Model structure:

- Controllers translate HTTP input and output.
- Services contain use-case and authorization decisions.
- Models contain database queries.
- `X-User-Id` is intentionally used as fake authentication for this exercise.

Read [ASSIGNMENT.md](ASSIGNMENT.md) before starting. Do not implement real authentication or replace the supplied stack unless your reviewer explicitly approves it.

