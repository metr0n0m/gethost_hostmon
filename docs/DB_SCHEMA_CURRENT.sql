-- Current database schema for GetHost / HostMon
-- Date: 2026-02-25

CREATE DATABASE IF NOT EXISTS gethost_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gethost_db;

-- Monitoring targets
CREATE TABLE IF NOT EXISTS sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    url VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive', 'no_access') NOT NULL DEFAULT 'inactive',
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    last_check DATETIME NULL,
    response_time_ms INT NULL,
    http_code INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_sites_url (url)
);

-- Query counters (aggregated)
CREATE TABLE IF NOT EXISTS query_counters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    query_type VARCHAR(50) NOT NULL,
    query_value_norm VARCHAR(255) NOT NULL,
    counter INT NOT NULL DEFAULT 1,
    last_requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_query_counters_type_value (query_type, query_value_norm)
);

-- Query history (events)
CREATE TABLE IF NOT EXISTS query_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    raw_query VARCHAR(255) NOT NULL,
    normalized_query VARCHAR(255) NOT NULL,
    query_type VARCHAR(50) NOT NULL,
    result_summary VARCHAR(500) NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    client_ip VARCHAR(64) NULL,
    client_os VARCHAR(100) NULL,
    client_browser VARCHAR(100) NULL,
    client_provider VARCHAR(255) NULL,
    is_proxy TINYINT(1) NOT NULL DEFAULT 0,
    is_tor TINYINT(1) NOT NULL DEFAULT 0,
    user_agent VARCHAR(500) NULL,
    counter_snapshot INT NOT NULL DEFAULT 1
);

-- Runtime module states (optional but recommended)
CREATE TABLE IF NOT EXISTS modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    config_json JSON NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Runtime app settings for About/Contacts/admin overrides (optional but recommended)
CREATE TABLE IF NOT EXISTS app_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Optional seed settings
INSERT INTO app_settings (setting_key, setting_value)
VALUES
    ('about_text', 'GetHost helps resolve hosts and monitor endpoint availability.'),
    ('contact_email', 'support@gethost.local'),
    ('contact_telegram', ''),
    ('contact_github', 'https://github.com/metr0n0m/gethost_hostmon')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    updated_at = CURRENT_TIMESTAMP;

