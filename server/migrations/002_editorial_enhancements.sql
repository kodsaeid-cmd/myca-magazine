-- MYCA Magazine Editorial Enhancements (2026-11-22)
-- Back up cms_articles and cms_media first. Execute ONCE after reviewing the live schema.
-- The application code in this branch requires this migration before deployment.
ALTER TABLE cms_articles
  ADD COLUMN expert_name VARCHAR(150) NOT NULL DEFAULT '' AFTER author_id,
  ADD COLUMN expert_title VARCHAR(200) NOT NULL DEFAULT '' AFTER expert_name,
  ADD COLUMN expert_quote TEXT NULL AFTER expert_title,
  ADD COLUMN sources_json JSON NULL AFTER faq_json,
  ADD COLUMN related_article_ids JSON NULL AFTER sources_json;
