CREATE TABLE IF NOT EXISTS legal_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    doc_key VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    content MEDIUMTEXT NOT NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    is_required TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uk_legal_documents_key (doc_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO legal_documents (doc_key, title, content, version, is_required, created_at, updated_at) VALUES
('terms', '이용약관', '<h3>제1조 (목적)</h3><p>본 약관은 라벨업(이하 \"회사\")이 제공하는 라벨 디자인·인쇄·쇼핑 관련 서비스(이하 \"서비스\")의 이용조건 및 절차, 회사와 회원의 권리·의무 및 책임사항을 규정함을 목적으로 합니다.</p><h3>제2조 (회원가입)</h3><p>회원은 회사가 정한 가입 양식에 따라 정보를 기입하고 본 약관에 동의함으로써 회원가입을 신청합니다. 회사는 허위 정보가 확인될 경우 가입을 거부하거나 사후에 이용을 제한할 수 있습니다.</p><h3>제3조 (서비스 제공)</h3><p>회사는 AI 기반 라벨 디자인, 템플릿 제공, 규격 추천, 인쇄 주문 등 서비스를 제공합니다. 서비스 내용은 운영상·기술상 필요에 따라 변경될 수 있습니다.</p><h3>제4조 (회원의 의무)</h3><p>회원은 관련 법령, 본 약관, 서비스 이용안내를 준수해야 하며, 타인의 권리를 침해하거나 서비스 운영을 방해하는 행위를 해서는 안 됩니다.</p><h3>제5조 (면책)</h3><p>회사는 천재지변, 시스템 장애 등 불가항력으로 인한 서비스 중단에 대해 책임을 지지 않습니다. 다만 회사의 고의 또는 중대한 과실이 있는 경우에는 관련 법령에 따릅니다.</p><p><em>시행일: 2026년 8월 27일</em></p>', 1, 1, NOW(), NOW()),
('privacy', '개인정보 수집 및 이용 동의', '<h3>1. 수집 항목</h3><ul><li>필수: 이메일, 비밀번호, 이름</li><li>선택: 연락처, 회사/상호</li><li>자동 수집: IP, 접속 로그, 쿠키</li></ul><h3>2. 수집 목적</h3><ul><li>회원 식별 및 서비스 제공</li><li>주문·결제·배송 처리</li><li>고객 문의 응대 및 공지 전달</li><li>부정 이용 방지 및 보안</li></ul><h3>3. 보유 기간</h3><p>회원 탈퇴 시까지 보유하며, 관련 법령에 따라 일정 기간 보관할 수 있습니다.</p><h3>4. 동의 거부 권리</h3><p>필수 항목 동의를 거부할 경우 회원가입 및 서비스 이용이 제한될 수 있습니다.</p><p><em>시행일: 2026년 8월 27일</em></p>', 1, 1, NOW(), NOW()),
('marketing', '마케팅 정보 수신 동의', '<h3>1. 수신 목적</h3><p>신규 서비스, 이벤트, 할인 혜택, 라벨 디자인 트렌드 등 마케팅 정보 제공</p><h3>2. 수신 방법</h3><p>이메일, SMS, 앱 푸시(추후 제공 시)</p><h3>3. 보유 및 이용 기간</h3><p>동의 철회 또는 회원 탈퇴 시까지</p><h3>4. 동의 철회</h3><p>마이페이지 또는 고객센터를 통해 언제든 수신 동의를 철회할 수 있습니다. 철회 후에도 서비스 이용에는 영향이 없습니다.</p><p><em>본 동의는 선택 사항입니다.</em></p>', 1, 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
