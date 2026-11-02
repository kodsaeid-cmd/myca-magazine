-- MYCA Content Studio: real MySQL persistence on ParsPack cPanel.
-- Import in phpMyAdmin AFTER creating a dedicated database/user.
-- All tables use utf8mb4 and InnoDB; timestamps stored as UTC.
CREATE TABLE IF NOT EXISTS cms_users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  display_name VARCHAR(140) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','editor','author') NOT NULL DEFAULT 'author',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_articles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(160) NOT NULL UNIQUE,
  title VARCHAR(250) NOT NULL,
  excerpt TEXT NOT NULL,
  body_html MEDIUMTEXT NOT NULL,
  status ENUM('draft','review','published','archived') NOT NULL DEFAULT 'draft',
  category_slug VARCHAR(80) NOT NULL DEFAULT 'elder-care',
  cover_url VARCHAR(700) NOT NULL DEFAULT '',
  cover_alt VARCHAR(255) NOT NULL DEFAULT '',
  author_id BIGINT UNSIGNED NOT NULL,
  meta_title VARCHAR(250) NOT NULL DEFAULT '',
  meta_description VARCHAR(320) NOT NULL DEFAULT '',
  canonical_url VARCHAR(700) NOT NULL DEFAULT '',
  robots VARCHAR(35) NOT NULL DEFAULT 'index,follow',
  focus_keyword VARCHAR(190) NOT NULL DEFAULT '',
  faq_json JSON NULL,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  published_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status_published (status,published_at),
  INDEX idx_category_status (category_slug,status),
  CONSTRAINT fk_cms_articles_author FOREIGN KEY (author_id) REFERENCES cms_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_revisions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  article_id BIGINT UNSIGNED NOT NULL,
  editor_id BIGINT UNSIGNED NOT NULL,
  snapshot LONGTEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_article_revision (article_id,id),
  CONSTRAINT fk_cms_revision_article FOREIGN KEY (article_id) REFERENCES cms_articles(id) ON DELETE CASCADE,
  CONSTRAINT fk_cms_revision_editor FOREIGN KEY (editor_id) REFERENCES cms_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_media (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uploader_id BIGINT UNSIGNED NOT NULL,
  path VARCHAR(500) NOT NULL,
  mime VARCHAR(80) NOT NULL,
  bytes INT UNSIGNED NOT NULL,
  alt VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_media_created (created_at),
  CONSTRAINT fk_cms_media_user FOREIGN KEY (uploader_id) REFERENCES cms_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_hash CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_attempts (ip_hash,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(40) NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_recent (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
