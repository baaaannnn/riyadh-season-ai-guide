-- Run once on an existing database; fresh installations already include liked.
ALTER TABLE reviews ADD COLUMN liked TINYINT(1) NOT NULL DEFAULT 0;
