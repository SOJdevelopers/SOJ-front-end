### update Aug 31 09:00
```sql
alter table group_info add group_type char(1);
update group_info set group_type='N';
update group_info set group_type='S' where group_name='outdated';
update group_info set group_type='S' where group_name='site_manager';
update group_info set group_type='S' where group_name='statement_maintainer';
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
update blogs left join (
select * from (select id, blog_id, post_time, poster from blogs_comments order by id desc limit 1919810) tmp1 group by blog_id
) tmp2 on blogs.id = tmp2.blog_id set blogs.latest_comment = tmp2.post_time, blogs.latest_commenter = tmp2.poster;

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