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
