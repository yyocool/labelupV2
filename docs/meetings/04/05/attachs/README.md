# 첨부파일 폴더

이 폴더에 회의 관련 파일을 넣으면, 발표 자료 **마지막 페이지**에 자동으로 다운로드 목록이 표시됩니다.

- 숨김 파일(.으로 시작)과 .gitkeep은 제외됩니다.
- 발표 URL: .../docs/meetings/05/index.php
"@ -Encoding UTF8
Set-Content "docs\meetings\05\index.html" -Value @"
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="0;url=index.php">
  <title>Label-UP · 5회차 회의</title>
  <script>location.replace('index.php');</script>
</head>
<body>
  <p><a href="index.php">발표 자료로 이동</a></p>
</body>
</html>
