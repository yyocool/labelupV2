-- 1:1 문의 관리자 답변 (회원 마이페이지 「내 문의 내역」에 표시)
ALTER TABLE user_inquiries
    ADD COLUMN answer TEXT NULL AFTER admin_memo,
    ADD COLUMN answered_at DATETIME NULL AFTER answer;
