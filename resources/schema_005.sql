ALTER TABLE game_line_item ADD COLUMN channel_id VARCHAR(10);
UPDATE game_line_item SET channel_id = 'CFVSDQPTL';
