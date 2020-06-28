ALTER TABLE players ADD COLUMN team_id VARCHAR(10);
UPDATE players SET team_id = 'TFVMPACSG';

ALTER TABLE games ADD COLUMN team_id VARCHAR(10);
UPDATE games SET team_id = 'TFVMPACSG';

ALTER TABLE game_line_item ADD COLUMN team_id VARCHAR(10);
UPDATE game_line_item SET team_id = 'TFVMPACSG';
CREATE INDEX idx_game_line_item_team_id ON game_line_item (team_id);
