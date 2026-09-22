-- Optional profile details for a verified publisher (one row per publisher).
-- Canonical identity (name, domain) stays on the publishers table.
CREATE TABLE publisher_profiles (
    id BIGSERIAL PRIMARY KEY,
    publisher_id BIGINT NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    location VARCHAR(160) DEFAULT NULL,
    website_url VARCHAR(2048) DEFAULT NULL,
    rss_url VARCHAR(2048) DEFAULT NULL,
    x_url VARCHAR(2048) DEFAULT NULL,
    instagram_url VARCHAR(2048) DEFAULT NULL,
    facebook_url VARCHAR(2048) DEFAULT NULL,
    linkedin_url VARCHAR(2048) DEFAULT NULL,
    youtube_url VARCHAR(2048) DEFAULT NULL,
    tiktok_url VARCHAR(2048) DEFAULT NULL,
    threads_url VARCHAR(2048) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_publisher_profiles_publisher_id
        FOREIGN KEY (publisher_id) REFERENCES publishers(id) ON DELETE CASCADE
);
