ALTER TABLE user_remember_tokens
    ADD COLUMN context VARCHAR(10) NOT NULL DEFAULT 'user' AFTER user_id;

UPDATE user_remember_tokens SET context = 'user' WHERE context = '' OR context IS NULL;

CREATE INDEX idx_remember_user_context ON user_remember_tokens (user_id, context);
