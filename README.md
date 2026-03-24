# ContractMirror Skeleton

Symfony 7.3 pragmatic MVP skeleton for ContractMirror.

## Included
- Symfony-like project structure without `vendor/`
- Docker (php-fpm + nginx)
- Contract analyzer module skeleton
- AI prompt pipeline skeleton
- Risk score calculator
- Document extractor facade

## Quick start

### 1. Copy env
```bash
cp .env.example .env
```

### 2. Install dependencies
```bash
docker compose run --rm php composer install
```

### 3. Start containers
```bash
docker compose up --build
```

### 4. Open
- App: http://localhost:8080
- Health: http://localhost:8080/health

## Notes
- PDF/DOCX extraction classes are stubs for now.
- `OpenAiLlmClient` contains request wiring and expects a valid API key.
- The default endpoint is:

```http
POST /api/contracts/analyze
```

Multipart fields:
- `file`
- `preferredLanguage` (optional, default `en`)

## Suggested next steps
1. Wire real PDF/DOCX extraction.
2. Add response validation against schema.
3. Add persistence for shareable reports.
4. Add `rewrite-clause` endpoint.
