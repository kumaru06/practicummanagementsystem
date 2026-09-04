-- Live Chat upgrade: idempotent send, replies, image attachments.
-- Safe to re-run: each ALTER is guarded in ChatController::migrateMessagesSchema().

ALTER TABLE messages
  ADD COLUMN client_message_id VARCHAR(64) NULL AFTER message_text,
  ADD COLUMN reply_to_id INT UNSIGNED NULL AFTER client_message_id,
  ADD COLUMN delivery_status ENUM('sent') NOT NULL DEFAULT 'sent' AFTER reply_to_id;

ALTER TABLE messages
  ADD UNIQUE KEY uq_messages_client (sender_id, client_message_id),
  ADD INDEX idx_messages_created_id (created_at, id),
  ADD CONSTRAINT fk_messages_reply FOREIGN KEY (reply_to_id) REFERENCES messages(id) ON DELETE SET NULL;

ALTER TABLE messages
  ADD COLUMN is_pinned TINYINT(1) NOT NULL DEFAULT 0 AFTER delivery_status,
  ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER is_pinned;

CREATE TABLE IF NOT EXISTS chat_attachments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  message_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  original_name VARCHAR(180) NOT NULL,
  mime VARCHAR(80) NOT NULL,
  byte_size INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_chat_attachments_message (message_id),
  INDEX idx_chat_attachments_path (file_path),
  CREATE TABLE IF NOT EXISTS chat_message_reactions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  message_id INT UNSIGNED NOT NULL,
  user_id INT NOT NULL,
  user_role ENUM('admin','coordinator','student','partner') NOT NULL,
  emoji VARCHAR(16) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_chat_reaction (message_id, user_id, user_role),
  INDEX idx_chat_reaction_message (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

