-- Create tables
CREATE TABLE segnalatori (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL
);

CREATE TABLE fenolizzazioni (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    cognome TEXT NOT NULL,
    data TEXT NOT NULL,
    tempo INTEGER NOT NULL,
    tampone TEXT NOT NULL,
    recidiva TEXT,
    segnalatore_id INTEGER,
    FOREIGN KEY (segnalatore_id) REFERENCES segnalatori(id)
);