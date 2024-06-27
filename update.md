### update Aug 31 09:00
```sql
alter table group_info add group_type char(1);
update group_info set group_type='N';
update group_info set group_type='S' where group_name='outdated';
update group_info set group_type='S' where group_name='site_manager';
update group_info set group_type='S' where group_name='statement_maintainer';
update group_info set group_type='S' where group_name='problem_manager';
update group_info set group_type='S' where group_name='zhzx';
update group_info set group_type='S' where group_name='zhjc';
update group_info set group_type='S' where group_name='banned';
```

### update Aug 31 11:04
```bash
mv utility/scripts utility/contest_scripts
```

### update Aug 31 11:09
```bash
mkdir utility/group_scripts
```

### update Sep 8
```sql
alter table blogs add latest_comment datetime not null;
alter table blogs add latest_commenter varchar(20) not null;

# History
update blogs left join ( select blog_id,any_value(post_time) as post_time,any_value(poster) as poster from (select id, blog_id, post_time, poster from blogs_comments order by id desc limit 1919810) tmp1 group by blog_id ) tmp2 on blogs.id = tmp2.blog_id set blogs.latest_comment = tmp2.post_time, blogs.latest_commenter = tmp2.poster;

update blogs set latest_comment = post_time where latest_comment = 0 and is_draft = 0;
```

### update Oct 8
```sql
alter table problems add data_locked tinyint(1);
```

### update Dec 28
```sql
drop index is_hidden on submissions;
drop index is_hidden on hacks;
alter table submissions add index contest_id (contest_id, problem_id);
alter table hacks add index contest_id (contest_id, problem_id);
alter table submissions drop column is_hidden;
alter table hacks drop column is_hidden;
```

### update Dec 28 14:29
```sql
CREATE TABLE `contests_visibility` (
  `contest_id` int(11) NOT NULL,
  `group_name` varchar(20) NOT NULL,
  PRIMARY KEY (`contest_id`,`group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

### update Dec 28 18:44
```sql
insert ignore into contests_visibility select id contest_id, 'zhjc' group_name from contests;
```

### update DEC 28 19:19
please remove "default-group" in .config.php

please create group "default"
```sql
update group_info set group_type='S' where group_name='default';
```

### update DEC 28 19:46
```sql
CREATE TABLE `blogs_visibility` (
  `blog_id` int(11) NOT NULL,
  `group_name` varchar(20) NOT NULL,
  PRIMARY KEY (`blog_id`,`group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

```sql
insert ignore into blogs_visibility select id blog_id, 'zhjc' group_name from blogs;
```

# 2021
### update APR 13 11:35
```sql
alter table user_info change extra_config extra_config varchar(1500);

update user_info set extra_config=concat(
'{',
'"qq":', qq, ',',
'"realname":"', real_name, '",',
'"email":"', email, '",',
'"motto":"', motto, '",',
'"aboutme":', about_me, ',',
'"real_name":"', real_name, '",',
'"sex":"', sex, '"',
'}');

alter table user_info drop column qq;
alter table user_info drop column email;
alter table user_info drop column motto;
alter table user_info drop column about_me;
alter table user_info drop column real_name;
alter table user_info drop column sex;
```

### update APR 14 07:43
```sql
CREATE TABLE `judger_data_sync` (
  `judger_name` varchar(50) NOT NULL,
  `problem_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`judger_name`,`problem_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

```sql
alter table user_info change svn_password api_password char(10);
```

### update APR 16 09:53
```sql
update submissions set language="C++98" where language="C++";
update submissions set language="C99" where language="C";
update submissions set language="Python2" where language="Python2.7";
```
### update APR 27 20"43
```sql
alter table problems change zan zan int(11) not null default 0;
```

# 2024

### update JUN 12 16:00

```sql
alter table submissions add column judger_name varchar(50) not null default '' after judge_time;
alter table custom_test_submissions add column judger_name varchar(50) not null default '' after judge_time;
alter table hacks add column judger_name varchar(50) not null default '' after judge_time;
```

### update JUN 13 10:00

```sql
CREATE TABLE `submissions_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `submission_id` int(10) unsigned NOT NULL,
  `judge_time` datetime DEFAULT NULL,
  `judger_name` varchar(50) NOT NULL DEFAULT '',
  `result` mediumblob NOT NULL,
  `status` varchar(20) NOT NULL,
  `result_error` varchar(20) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `used_time` int(11) NOT NULL DEFAULT '0',
  `used_memory` int(11) NOT NULL DEFAULT '0',
  `status_details` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `submission` (`submission_id`,`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

alter table submissions add column contest_final_version_id int(10) unsigned default NULL after contest_id;
alter table submissions add column active_version_id int(10) unsigned default NULL after contest_id;
insert into submissions_history (submission_id, judge_time, judger_name, result, status, result_error, score, used_time, used_memory, status_details) select id, judge_time, judger_name, result, status, result_error, score, used_time, used_memory, status_details from submissions where status="Judged" order by judge_time asc;
update submissions inner join submissions_history on submissions.id = submissions_history.submission_id set submissions.active_version_id = submissions_history.id;
```

### update JUN 24 20:45

```sql
CREATE TABLE `important_system_updates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `scope` varchar(20) NOT NULL,
  `title` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `blog_id` int(10) unsigned NOT NULL,
  `update_time` datetime NOT NULL,
  `updater` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `scope` (`scope`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `audit_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `scope` varchar(20) NOT NULL,
  `type` varchar(50) NOT NULL,
  `id_in_scope` int(10) unsigned NOT NULL,
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actor` varchar(20) NOT NULL,
  `actor_remote_addr` varchar(50) NOT NULL,
  `actor_http_x_forwarded_for` varchar(50) NOT NULL,
  `reason` varchar(100) NOT NULL,
  `details` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_in_scope` (`scope`,`id_in_scope`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
```
