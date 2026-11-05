-- 오류&제안 처리 이력: 내부 처리 메모(note) / 회원 통보(result) 구분, 이전 상태 기록
ALTER TABLE error_report_actions
    ADD COLUMN action_type VARCHAR(20) NOT NULL DEFAULT 'result' AFTER admin_id,
    ADD COLUMN status_from VARCHAR(20) NULL AFTER action_type;
