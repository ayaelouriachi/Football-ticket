-- Add title column to matches table
ALTER TABLE matches
ADD COLUMN title VARCHAR(255) NOT NULL DEFAULT 'Match' AFTER id; 