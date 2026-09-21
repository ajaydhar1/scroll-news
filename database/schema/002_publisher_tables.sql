-- Publisher records are shared by multiple Scroll News users.
CREATE TABLE publishers (
    id BIGSERIAL PRIMARY KEY,
    domain VARCHAR(253) NOT NULL UNIQUE,
    name VARCHAR(160) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- A user-to-publisher relationship contains its own verification state.
CREATE TABLE publisher_users (
    id BIGSERIAL PRIMARY KEY,
    publisher_id BIGINT NOT NULL,
    user_id BIGINT NOT NULL,
    email VARCHAR(254) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    verification_token_hash CHAR(64) DEFAULT NULL,
    token_expires_at TIMESTAMP DEFAULT NULL,
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_publisher_users_publisher_id
        FOREIGN KEY (publisher_id) REFERENCES publishers(id) ON DELETE CASCADE,
    CONSTRAINT fk_publisher_users_user_id
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT uq_publisher_users_user_publisher
        UNIQUE (user_id, publisher_id)
);

CREATE INDEX idx_publisher_users_publisher_id ON publisher_users(publisher_id);
CREATE INDEX idx_publisher_users_token_hash ON publisher_users(verification_token_hash);