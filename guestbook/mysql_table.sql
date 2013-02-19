SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE IF NOT EXISTS `guestbook` (
  `id` int(255) NOT NULL auto_increment,
  `timestamp` int(255) NOT NULL,
  `content` text collate utf8_bin NOT NULL,
  `name` varchar(255) collate utf8_bin NOT NULL,
  `e-mail` varchar(255) collate utf8_bin NOT NULL,
  `ip` varchar(255) collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=2 ;

INSERT INTO `guestbook` (`id`, `timestamp`, `content`, `name`, `e-mail`, `ip`) VALUES
(1, 0, 'Welcome and thank you for using my Guestbook! I hope, it will be useful for you ...', 'Rene Kliment', 'rene.kliment@gmail.com', '127.0.0.1/localhost');