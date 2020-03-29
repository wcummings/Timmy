CREATE TABLE IF NOT EXISTS players (
       id INTEGER PRIMARY KEY AUTOINCREMENT,
       nickname VARCHAR(64) UNIQUE
);

CREATE TABLE IF NOT EXISTS games (
       id INTEGER PRIMARY KEY AUTOINCREMENT,
       timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       comment TEXT
);

CREATE TABLE IF NOT EXISTS game_line_item (
       game_id INTEGER NOT NULL,
       player_id INTEGER NOT NULL,
       is_winner BOOLEAN NOT NULL,
       FOREIGN KEY(game_id) REFERENCES games(id),
       FOREIGN KEY(player_id) REFERENCES players(id)
);

CREATE TABLE IF NOT EXISTS webhook_idempotency (
       event_id VARCHAR(24) PRIMARY KEY
);

CREATE TABLE IF NOT EXISTS webhooks (
       channel_id VARCHAR(10) PRIMARY KEY,
       team_id VARCHAR(10) NOT NULL,
       channel VARCHAR(64) NOT NULL,
       url VARCHAR(2048) NOT NULL
);

CREATE TABLE IF NOT EXISTS access_tokens (
       team_id VARCHAR(10) PRIMARY KEY,
       access_token VARCHAR(128) NOT NULL,
       bot_user_id VARCHAR(10) NOT NULL
);
