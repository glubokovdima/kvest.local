CREATE TABLE IF NOT EXISTS games (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    slug TEXT,
    description TEXT,
    start_time TEXT,
    is_active INTEGER,
    cover_image TEXT
);

CREATE TABLE IF NOT EXISTS teams (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    team_number TEXT,
    email TEXT,
    start_time TEXT
);

CREATE TABLE IF NOT EXISTS team_games (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    team_id INTEGER,
    game_id INTEGER,
    status TEXT,
    is_paid INTEGER,
    answers TEXT
);
