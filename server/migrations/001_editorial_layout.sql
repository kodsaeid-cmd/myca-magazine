-- MYCA Editorial Layout Studio v1: additive migration; safe for existing CMS data.
-- Execute ONCE on the staging CMS database before installing the new ZIP.
CREATE TABLE IF NOT EXISTS cms_editorial_layout (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  draft_json LONGTEXT NOT NULL,
  published_json LONGTEXT NULL,
  version INT UNSIGNED NOT NULL DEFAULT 0,
  published_version INT UNSIGNED NOT NULL DEFAULT 0,
  updated_by BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  published_at DATETIME NULL,
  CONSTRAINT fk_cms_layout_user FOREIGN KEY (updated_by) REFERENCES cms_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_editorial_layout_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  version INT UNSIGNED NOT NULL,
  layout_json LONGTEXT NOT NULL,
  published_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_layout_version (version),
  CONSTRAINT fk_cms_layout_history_user FOREIGN KEY (published_by) REFERENCES cms_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
