DROP KEYSPACE IF EXISTS unittest;

CREATE KEYSPACE unittest WITH replication = {'class': 'SimpleStrategy', 'replication_factor': 1};

use unittest;

CREATE TABLE testtable (
    id      INT,
    name    TEXT,
    PRIMARY KEY ((id)));

INSERT INTO unittest.testtable (id, name) VALUES (1,'foo');
INSERT INTO unittest.testtable (id, name) VALUES (2,'bar');
INSERT INTO unittest.testtable (id, name) VALUES (3,'moo');
INSERT INTO unittest.testtable (id, name) VALUES (4,'cow');

CREATE TABLE users (
    id          INT,
    name        TEXT,
    title       TEXT,
    age         INT,
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP,
    PRIMARY KEY ((id)));
