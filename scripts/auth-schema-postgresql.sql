CREATE TABLE roles
(
  id serial,
  "name" varchar(32) NOT NULL,
  description text NOT NULL,
  CONSTRAINT roles_id_pkey PRIMARY KEY (id),
  CONSTRAINT roles_name_key UNIQUE (name)
);

CREATE TABLE roles_users
(
  user_id integer,
  role_id integer
);

CREATE TABLE groups
(
  id serial,
  "name" varchar(32) NOT NULL,
  description text NOT NULL,
  CONSTRAINT groups_id_pkey PRIMARY KEY (id),
  CONSTRAINT groups_name_key UNIQUE (name)
);

CREATE TABLE groups_users
(
  user_id integer,
  group_id integer
);

CREATE TABLE permissions
(
  id serial,
  "name" varchar(32) NOT NULL,
  description text NOT NULL,
  CONSTRAINT permissions_id_pkey PRIMARY KEY (id),
  CONSTRAINT permissions_name_key UNIQUE (name)
);

CREATE TABLE permissions_roles
(
  role_id integer,
  permission_id integer
);

CREATE TABLE permissions_groups
(
  group_id integer,
  permission_id integer
);

CREATE TABLE permissions_users
(
  user_id integer,
  permission_id integer
);

CREATE TABLE users
(
  id serial,
  email varchar(254) NOT NULL,
  username varchar(32) NOT NULL,
  "password" varchar(64) NOT NULL,
  logins integer NOT NULL DEFAULT 0,
  last_login integer,
  CONSTRAINT users_id_pkey PRIMARY KEY (id),
  CONSTRAINT users_username_key UNIQUE (username),
  CONSTRAINT users_email_key UNIQUE (email),
  CONSTRAINT users_logins_check CHECK (logins >= 0)
);

CREATE TABLE user_tokens
(
  id serial,
  user_id integer NOT NULL,
  user_agent varchar(40) NOT NULL,
  token character varying(32) NOT NULL,
  created integer NOT NULL,
  expires integer NOT NULL,
  CONSTRAINT user_tokens_id_pkey PRIMARY KEY (id),
  CONSTRAINT user_tokens_token_key UNIQUE (token)
);

CREATE INDEX roles_users_user_id_idx ON roles_users (user_id);
CREATE INDEX roles_users_role_id_idx ON roles_users (role_id);

ALTER TABLE roles_users
  ADD CONSTRAINT roles_users_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  ADD CONSTRAINT roles_users_role_id_fkey FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE;

CREATE INDEX groups_users_user_id_idx ON groups_users (user_id);
CREATE INDEX groups_users_group_id_idx ON groups_users (group_id);

ALTER TABLE groups_users
  ADD CONSTRAINT groups_users_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  ADD CONSTRAINT groups_users_group_id_fkey FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE;

CREATE INDEX permissions_users_user_id_idx ON permissions_users (user_id);
CREATE INDEX permissions_users_permission_id_idx ON permissions_users (permission_id);

ALTER TABLE permissions_users
  ADD CONSTRAINT permissions_users_user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  ADD CONSTRAINT permissions_users_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE;

CREATE INDEX permissions_roles_role_id_idx ON permissions_roles (role_id);
CREATE INDEX permissions_roles_permission_id_idx ON permissions_roles (permission_id);

ALTER TABLE permissions_roles
  ADD CONSTRAINT permissions_roles_role_id_fkey FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  ADD CONSTRAINT permissions_roles_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE;

CREATE INDEX permissions_groups_group_id_idx ON permissions_groups (group_id);
CREATE INDEX permissions_groups_permission_id_idx ON permissions_groups (permission_id);

ALTER TABLE permissions_groups
  ADD CONSTRAINT permissions_groups_group_id_fkey FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
  ADD CONSTRAINT permissions_groups_permission_id_fkey FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE;

ALTER TABLE user_tokens
  ADD CONSTRAINT user_id_fkey FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

INSERT INTO roles (name, description) VALUES ('login', 'Login privileges, granted after account confirmation');
INSERT INTO roles (name, description) VALUES ('admin', 'Administrative user, has access to everything.');

INSERT INTO permissions (name, description) VALUES('create', 'Can create any data');
INSERT INTO permissions (name, description) VALUES('read', 'Can read any data');
INSERT INTO permissions (name, description) VALUES('update', 'Can update any data');
INSERT INTO permissions (name, description) VALUES('delete', 'Can delete any data');
