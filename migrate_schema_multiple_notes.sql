DROP TABLE IF EXISTS note;
CREATE TABLE `note` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `path` varchar(255) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `version` int(11) NOT NULL DEFAULT '0',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_client_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`path`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

INSERT INTO note (user_id, name, path, content, version, updated, last_client_id)
  SELECT id, 'My Notes', 'my-notes', content, version, updated, last_client_id
  FROM user;

ALTER TABLE user DROP COLUMN content;
ALTER TABLE user DROP COLUMN version;
ALTER TABLE user DROP COLUMN updated;
ALTER TABLE user DROP COLUMN last_client_id;
