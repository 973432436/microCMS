set names utf8;

CREATE TABLE IF NOT EXISTS cart(id INT(11) NOT NULL AUTO_INCREMENT,user_id INT(11) NOT NULL DEFAULT '0',product_id INT(11) NOT NULL DEFAULT '0',numbers	INT(11) NOT NULL DEFAULT '0',exts varchar(5120) not null default '', price decimal(10, 4) NOT NULL DEFAULT '0.00', money decimal(10, 4) NOT NULL DEFAULT '0.00',utime INT(11) NOT NULL DEFAULT '0', PRIMARY KEY (id))ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS page (id INT(11) AUTO_INCREMENT not null,category VARCHAR (32) NOT NULL DEFAULT '',filename varchar (30) NOT NULL DEFAULT '', parent_id smallint (5) NOT NULL DEFAULT '0', page_name varchar (150) NOT NULL DEFAULT '', title VARCHAR (128) DEFAULT '', content longtext default '', keywords varchar (255) NOT NULL DEFAULT '', description varchar (255) NOT NULL DEFAULT '', sort INT (8) NOT NULL DEFAULT '0', pv int(10) NOT NULL DEFAULT '0', PRIMARY KEY (id))ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS url_maps ( url_new VARCHAR (128) NOT NULL, url_sys VARCHAR (128) NOT NULL DEFAULT '', lavel VARCHAR (4) NOT NULL DEFAULT 'user', PRIMARY KEY (url_new))ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS product ( id INT(11) AUTO_INCREMENT, cat_id smallint (5) NOT NULL DEFAULT '0', filename varchar (32) NOT NULL DEFAULT '', name varchar (150) NOT NULL DEFAULT '', price decimal(10, 4) NOT NULL DEFAULT '0.00', weight decimal(10, 4) NOT NULL DEFAULT '0.00', defined varchar(32) DEFAULT '', title VARCHAR (128) DEFAULT '', keywords varchar (255) NOT NULL DEFAULT '', description varchar (255) NOT NULL DEFAULT '', content longtext default '', image varchar (5120) NOT NULL DEFAULT '', edit_time int (10) NOT NULL DEFAULT '0', sort tinyint (1) NOT NULL DEFAULT '0', ext1 varchar (255) NOT NULL DEFAULT '', ext2 varchar (255) NOT NULL DEFAULT '', ext3 varchar (255) NOT NULL DEFAULT '', ext4 varchar (255) NOT NULL DEFAULT '', ext5 varchar (255) NOT NULL DEFAULT '', ext6 varchar (255) NOT NULL DEFAULT '', ext7 varchar (255) NOT NULL DEFAULT '', ext8 varchar (255) NOT NULL DEFAULT '', ext9 varchar (255) NOT NULL DEFAULT '', ext10 varchar (255) NOT NULL DEFAULT '', ext11 varchar (255) NOT NULL DEFAULT '', ext12 varchar (255) NOT NULL DEFAULT '', ext13 varchar (255) NOT NULL DEFAULT '', ext14 varchar (255) NOT NULL DEFAULT '', ext15 varchar (255) NOT NULL DEFAULT '', pv int(10) NOT NULL DEFAULT '0', PRIMARY KEY (id))ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS slide ( id INT(11) AUTO_INCREMENT, name varchar (60) NOT NULL DEFAULT '', url varchar (255) NOT NULL DEFAULT '', img varchar (255) NOT NULL, category varchar (32) NOT NULL, sort tinyint (1) NOT NULL DEFAULT '50', notes VARCHAR (1024) DEFAULT '', PRIMARY KEY(id))ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS task_list ( id INT(11) AUTO_INCREMENT, task_url VARCHAR (128) NOT NULL DEFAULT '', add_time INT(11) NOT NULL DEFAULT '0', last_time INT(11) NOT NULL DEFAULT '0', status TINYINT(1) DEFAULT '0', `cycle` INT(11) DEFAULT '0', PRIMARY KEY(id), UNIQUE KEY(task_url))ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS product_category ( cat_id INT(11) AUTO_INCREMENT, category VARCHAR (32) NOT NULL DEFAULT '', parent_id smallint (5) NOT NULL DEFAULT '0', unique_id varchar (30) NOT NULL DEFAULT '', cat_name varchar (32) NOT NULL DEFAULT '', title VARCHAR (128) DEFAULT '', keywords varchar (255) NOT NULL DEFAULT '', description varchar (255) NOT NULL DEFAULT '', content TEXT DEFAULT '', sort tinyint (1) NOT NULL DEFAULT '50', PRIMARY KEY(cat_id), UNIQUE KEY(unique_id))ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS nav ( id INT(11) AUTO_INCREMENT, parent_id SMALLINT (5) NOT NULL DEFAULT '0', category VARCHAR (32) DEFAULT '' NOT NULL, nav_name VARCHAR (255) NOT NULL, nav_url VARCHAR (256) NOT NULL, sort TINYINT (3) NOT NULL DEFAULT '0', PRIMARY KEY(id))ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS mailbox ( id INT(11) AUTO_INCREMENT, mail_title VARCHAR (128) NOT NULL DEFAULT '', mail_body TEXT NOT NULL DEFAULT '', mail_from VARCHAR (64) NOT NULL DEFAULT '', mail_to VARCHAR (64) NOT NULL DEFAULT '', user_ip VARCHAR (16) NOT NULL DEFAULT '0.0.0.0', add_time INT (11) NOT NULL DEFAULT '0', send_time INT (11) NOT NULL DEFAULT '0', err_count int (11) NOT NULL DEFAULT '0', sending TINYINT(1) DEFAULT '0', PRIMARY KEY(id))ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS article ( id INT(11) AUTO_INCREMENT, cat_id smallint (5) NOT NULL DEFAULT '0', filename varchar (32) NOT NULL DEFAULT '', defined text NOT NULL, title varchar (150) NOT NULL DEFAULT '', keywords varchar (255) NOT NULL DEFAULT '', description varchar (255) NOT NULL DEFAULT '', content longtext default '', image varchar (5120) NOT NULL DEFAULT '', edit_time int (10) NOT NULL DEFAULT '0', click smallint (6) NOT NULL DEFAULT '0', sort tinyint (1) NOT NULL DEFAULT '0', pv int(10) NOT NULL DEFAULT '0', PRIMARY KEY(id))ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS config ( name varchar (80) NOT NULL, `value` text NOT NULL, `type` varchar (10) NOT NULL DEFAULT '', tab varchar (32) NOT NULL DEFAULT 'main', box varchar (255) NOT NULL DEFAULT '', sort tinyint (3) NOT NULL DEFAULT '1', PRIMARY KEY (`name`))ENGINE=MyISAM DEFAULT CHARSET=utf8;
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('site_name', '智网企业网站管理系统', 'text', 'base', '', 1);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('site_title', '智网ECMS', 'text', 'base', '', 2);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('site_keywords', '智网企业网站管理系统', 'text', 'base', '', 3);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('site_description', '智网企业网站管理系统,是一个PHP开源的企业模板建站系统', 'textarea', 'base', '', 4);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('site_address', '深圳市罗湖区', 'text', 'base', '', 6);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('site_closed', '0', 'radio', 'base', '', 7);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('icp', '', 'text', 'base', '', 8);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('tel', '0755-8888888', 'text', 'base', '', 9);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('fax', '0788-8888888', 'text', 'base', '', 10);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('qq', '', 'text', 'base', '', 11);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('email', '973432436@qq.com', 'text', 'base', '', 12);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('thumb_width', '135', 'text', 'base', '', 13);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('thumb_height', '135', 'text', 'base', '', 14);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('mail_host', 'smtp.qq.com', 'text', 'mail', '', 2);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('mail_port', '25', 'text', 'mail', '', 3);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('mail_ssl', '0', 'radio', 'mail', '', 4);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('mail_username', '', 'text', 'mail', '', 5);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('mail_password', '', 'text', 'mail', '', 6);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('mobile_name', '智网企业建站系统', 'text', 'base', '', 17);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('mobile_title', '智网企业建站系统', 'text', 'base', '', 18);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('mobile_keywords', '智网企业建站系统', 'text', 'base', '', 19);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('site_theme', 'default', 'text', 'base', '', 15);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('mobile_theme', 'default', 'text', 'base', '', 16);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('site_pv', '0', 'text', 'base', '', 20);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('mail_cloud', '0', 'radio', 'mail', '', 1);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('mail_rss_url', '', 'text', 'mail', '', 7);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('db_cache_time', '300', 'text', 'cacheing', '', 1);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('tpl_cache_time', '86400', 'text', 'cacheing', '', 2);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('img_captcha', '1', 'radio', 'base', '', 20);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('weight_unit', 'Kg', 'text', 'shopping', '', 2);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('unit_freight', '3', 'text', 'shopping', '', 3);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('basic_freight', '8', 'text', 'shopping', '', 4);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('free_freight', '1000', 'text', 'shopping', '', 5);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('clientId', '', 'text', 'payment-paypal', '', 1);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('clientSecret', '', 'text', 'payment-paypal', '', 2);
INSERT IGNORE INTO config (name, value, type, tab, box, sort) VALUES ('upfile_extension', 'gif|jpg|jpeg|png', 'text', 'file', '', 1);

CREATE TABLE IF NOT EXISTS mail_subscribe ( mail_to VARCHAR (64)  NOT NULL, user_ip VARCHAR (16)  NOT NULL DEFAULT '0.0.0.0', add_time INT (11) NOT NULL DEFAULT '0', PRIMARY KEY(mail_to))ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS article_category ( cat_id INT(11) AUTO_INCREMENT, category VARCHAR (32) NOT NULL DEFAULT '', parent_id smallint (5) NOT NULL DEFAULT '0', unique_id varchar(30) NOT NULL DEFAULT '', cat_name varchar (32) NOT NULL DEFAULT '', title VARCHAR (128) DEFAULT '', keywords varchar (255) NOT NULL DEFAULT '', description varchar (255) NOT NULL DEFAULT '', content TEXT DEFAULT '', sort tinyint (1) NOT NULL DEFAULT '50', PRIMARY KEY(cat_id), UNIQUE KEY(unique_id))ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS member_list(user_id INT(11) not null AUTO_INCREMENT, email varchar(32) not null default '', username VARCHAR(32) not null default '', phone varchar(16) not null default '', reg_time INT(11) NOT NULL DEFAULT '0', last_login INT(11) NOT NULL DEFAULT '0', login_count INT(11) NOT NULL DEFAULT '0', status INT(11) NOT NULL DEFAULT '0', PRIMARY KEY(user_id), UNIQUE KEY(email))ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS member_orders(orders_id int(11) not null AUTO_INCREMENT, user_id int(11) NOT NULL DEFAULT '0', pay_type varchar(16) not null default '', pay_key varchar(32) not null default '', pay_time int(11) NOT NULL DEFAULT '0', orders_money DECIMAL(13,4) NOT NULL DEFAULT '0', pay_money DECIMAL(13,4) NOT NULL DEFAULT '0', `status` tinyint(1) not null default '0', notes varchar(128) not null default '', atime int(11) NOT NULL DEFAULT '0', address_id int(11) NOT NULL DEFAULT '0', tracking_no VARCHAR (32) DEFAULT '',utime INT(11) DEFAULT '0', PRIMARY KEY(orders_id))ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS member_orders_goods(orders_id int(11) not null default '0', goods_id int(11) not null default '0', goods_name varchar(64) not null default '', goods_price decimal(10, 4) not null default '0', goods_weight decimal(10, 4) not null default '0', goods_num int(11) NOT NULL DEFAULT '0',exts VARCHAR (5120) DEFAULT '',unique key(orders_id,goods_id))ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE member_orders_status(orders_id int(11) not null default '0', status TINYINT(1) not null default '0', notes varchar(128) not null default '', atime int(11) NOT NULL DEFAULT '0')ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS member_msg(id int(11) not null AUTO_INCREMENT, user_id int(11) NOT NULL DEFAULT '0', title varchar(64) not null default '', content TEXT default '', atime int(11) NOT NULL DEFAULT '0', unique key(user_id, title),PRIMARY KEY(id))ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS member_msg_reply(id int(11) not null AUTO_INCREMENT, msg_id int(11) NOT NULL DEFAULT '0', user_id int(11) NOT NULL DEFAULT '0', content TEXT default '', atime int(11) NOT NULL DEFAULT '0', PRIMARY KEY(id))ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS member_orders_invoice (orders_id int(11) NOT NULL DEFAULT '0', orders_info text default '', atime int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`orders_id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;

create table IF NOT EXISTS member_address(id int(11) not null AUTO_INCREMENT, user_id int(11) NOT NULL DEFAULT '0', username VARCHAR(32) NOT NULL DEFAULT '', line1 VARCHAR(64) NOT NULL DEFAULT '', line2 VARCHAR(64) NOT NULL DEFAULT '', city VARCHAR(64) NOT NULL DEFAULT '', `state` VARCHAR(64) NOT NULL DEFAULT '', phone VARCHAR(32) NOT NULL DEFAULT '', postalCode VARCHAR(32) NOT NULL DEFAULT '', countryCode VARCHAR(8) NOT NULL DEFAULT '', PRIMARY KEY (id))ENGINE=MyISAM DEFAULT CHARSET=utf8;

create table IF NOT EXISTS pageview(sn varchar(16) not null default '', last_pv int(11) not null default '0', temp_pv int(11) not null default '0', utime int(11) not null default '0', PRIMARY KEY(sn))ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE admin (user_id int(11) NOT NULL AUTO_INCREMENT, user_name varchar(60) NOT NULL DEFAULT '', email varchar(60) NOT NULL DEFAULT '', password varchar(32) NOT NULL DEFAULT '', action_list text NOT NULL default 'all', add_time int(11) NOT NULL DEFAULT '0', last_login int(11) NOT NULL DEFAULT '0', last_ip varchar(15) NOT NULL DEFAULT '', PRIMARY KEY(user_id),unique key(user_name)) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO admin (user_id, user_name, email, password, action_list, add_time, last_login, last_ip) VALUES (1, 'admin', '', 'e10adc3949ba59abbe56e057f20f883e', 'all', 1467365040, 1534867579, '192.168.80.1');

CREATE TABLE admin_log (id int(11) NOT NULL AUTO_INCREMENT, create_time int(10) NOT NULL DEFAULT '0', user_name varchar(32) DEFAULT '', action varchar(255) NOT NULL DEFAULT '', ip varchar(15) NOT NULL DEFAULT '', primary key(id)) ENGINE=MyISAM DEFAULT CHARSET=utf8;
