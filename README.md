# Label-UP

웹 프로젝트 관리 도구 (PHP 5.6+ / MySQL)

## 기능

- 대시보드, 메뉴 구성도 (목록·조직도 트리)
- 스토리보드, 이슈, 마일스톤, 일정관리
- 공지사항, 자료실, 관리자 페이지

---

## Docker로 실행 (권장 · 팀 협업)

PHP·MySQL 설치 없이 동일 환경에서 작업할 수 있습니다.

### 사전 준비

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) 설치 후 실행

### 1. 클론

```bash
git clone https://github.com/yyocool/labelup-.git
cd labelup
```

### 2. 환경 변수 (선택)

```bash
copy .env.example .env    # Windows
# cp .env.example .env    # Mac/Linux
```

기본값: 앱 `http://localhost:8080`, MySQL 포트 `3307`

### 3. 컨테이너 실행

```bash
docker compose up -d --build
```

### 4. 설치

브라우저에서 접속:

```
http://localhost:8080/project/install.php
```

「설치 시작」 클릭 → 완료 후 로그인

- 아이디: `admin`
- 비밀번호: `admin1234`

### Docker 명령어

| 명령 | 설명 |
|------|------|
| `docker compose up -d` | 백그라운드 실행 |
| `docker compose down` | 중지 |
| `docker compose logs -f app` | PHP 로그 |
| `docker compose exec app bash` | 컨테이너 셸 |

코드는 `./` 가 컨테이너에 마운트되어 **수정 즉시 반영**됩니다.

---

## phpStudy / 로컬 PHP로 실행

### 1. DB 설정

```bash
cd project/config
copy database.local.example.php database.local.php
```

`database.local.php` 에 DB 접속 정보 입력.

### 2. 설치

`http://localhost/labelup/project/install.php`

---

## 디렉터리 구조

```
labelup/
├── docker-compose.yml
├── Dockerfile
├── index.php
└── project/
    ├── config/          database.docker.php (Docker 자동 사용)
    ├── includes/
    ├── views/
    ├── admin/
    ├── assets/
    └── sql/schema.sql
```

## 협업 규칙

- **Git에 올리지 말 것**: `database.local.php`, `database.remote.php`, `.env`, `*.sql`(백업), `storage/installed.lock`
- **브랜치**: `feature/기능명`, `fix/버그설명`
- **Pull Request** → 리뷰 → `main` 머지

## GitHub push (관리자)

```bash
git remote set-url origin https://github.com/yyocool/labelup-.git
git push -u origin main
```

저장소가 없으면 GitHub에서 **Private** 저장소 `labelup` 을 먼저 생성하세요.

## 환경

- PHP 7.4+ (Docker), PHP 5.6+ (레거시 서버)
- MySQL 8.0
- Apache
