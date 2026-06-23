-- Typing indicator support for Live Chat
CREATE TABLE IF NOT EXISTS chat_typing (
  user_id INT NOT NULL,
  user_role ENUM('admin','coordinator','student','partner') NOT NULL,
  partner_id INT NOT NULL,
  partner_role ENUM('admin','coordinator','student','partner') NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, user_role, partner_id, partner_role),
  INDEX idx_chat_typing_partner (partner_id, partner_role, updated_at),
  CONSTRAINT fk_chat_typing_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_chat_typing_partner_user FOREIGN KEY (partner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
