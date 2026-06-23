-- Live Chat messages table
CREATE TABLE IF NOT EXISTS messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  sender_role ENUM('admin','coordinator','student','partner') NOT NULL,
  receiver_id INT NOT NULL,
  receiver_role ENUM('admin','coordinator','student','partner') NOT NULL,
  message_text TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_messages_conversation (sender_id, sender_role, receiver_id, receiver_role, created_at),
  INDEX idx_messages_inbox (receiver_id, receiver_role, is_read, created_at),
  CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_messages_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
