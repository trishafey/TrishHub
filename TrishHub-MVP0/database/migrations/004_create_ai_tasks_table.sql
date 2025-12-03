CREATE TABLE ai_tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repository_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    token_id BIGINT UNSIGNED NULL,
    branch_name VARCHAR(255) NOT NULL,
    paths TEXT NOT NULL,
    instruction TEXT NOT NULL,
    status ENUM('pending','in_progress','completed','failed','logged_only')
        NOT NULL DEFAULT 'logged_only',
    result_summary TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ai_tasks_repo FOREIGN KEY (repository_id)
        REFERENCES repositories (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_ai_tasks_user FOREIGN KEY (created_by)
        REFERENCES users (id)
        ON DELETE SET NULL,
    CONSTRAINT fk_ai_tasks_token FOREIGN KEY (token_id)
        REFERENCES ai_tokens (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
