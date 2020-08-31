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
