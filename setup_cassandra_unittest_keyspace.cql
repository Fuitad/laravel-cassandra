DROP KEYSPACE IF EXISTS unittest;

CREATE KEYSPACE unittest WITH replication = {'class': 'SimpleStrategy', 'replication_factor': 1};

USE unittest;

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
    note1       TEXT,
    note2       TEXT,
    birthday    TIMESTAMP,
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP,
    PRIMARY KEY ((id)));


CREATE TABLE soft (
    id          INT,
    name        TEXT,
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP,
    deleted_at  TIMESTAMP,
    PRIMARY KEY ((id), deleted_at));


CREATE TABLE books (
    title        TEXT,
    author       TEXT,
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP,
    PRIMARY KEY ((title)));


CREATE TYPE stats (
   name TEXT,
   value INT
);


CREATE TABLE items (
    id          INT,
    name        TEXT,
    type        TEXT,
    stats       FROZEN<stats>,
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP,
    PRIMARY KEY ((id), name));

