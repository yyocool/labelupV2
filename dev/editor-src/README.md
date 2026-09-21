# LabelUp Blazor WASM Editor

## Develop
```bash
cd LabelUp.Editor
dotnet run
```

로컬(localhost)에서 실행하면 용지·상품 데이터는 `wwwroot/appsettings.json` 의
`Api:LocalDevBaseUrl` 서버(`/api/editor/papers`)에서 가져온다. 서버는 해당 출처를
`app/Helpers/Cors.php` 허용 목록으로 받아준다(필요 시 `.env` 의 `CORS_EXTRA_ORIGINS` 로 추가).
`Api:BaseUrl` 을 채우면 호스트와 무관하게 그 서버를 사용하고, 배포본은 두 값이 모두
비어 있어 편집기를 서빙한 서버(같은 출처)를 호출한다.

## Publish into PHP public root
```bash
python scripts/publish_editor.py
```

Output: `public/editor/` (served at `/editor/`).
