# LabelUp Dev

실서비스 개발용 `/dev` 프로젝트 (Phase 1 기반)

## 구조

- `public/` — 웹 루트 (Nginx document root)
- `app/` — Controller / Model / Service / Middleware
- `config/` — 환경설정
- `storage/` — uploads, designs, pdf, logs
- `views/` — PHP 템플릿
- `database/migrations/` — SQL 마이그레이션

## 로컬 실행

1. `.env.example` → `.env` 복사 후 DB 설정
2. 웹서버 document root를 `dev/public`으로 지정
3. `POST /api/system/migrate` 로 마이그레이션 실행 (debug 모드)

## API

- `GET /api/health` — 헬스체크
- `POST /api/system/migrate` — 마이그레이션 (개발용)

## 브랜드

- Burgundy `#7B2840` (PANTONE 696C)
- Ivory `#F7F3ED`
- Beige `#E8DFD0`
- Charcoal `#2E2A27`

메인 UI는 `draft/labelup_new_publish` 시안 기반.
