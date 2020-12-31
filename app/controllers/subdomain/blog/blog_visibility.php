<?php
	requirePHPLib('form');

	if (!isset($_GET['id']) || !validateUInt($_GET['id']) || !($blog = queryBlog($_GET['id']))) {
		become404Page();
	}

	if (!isSuperUser(Auth::user())) {
		become403Page();
	}

	$visibility_form = newAddDelCmdForm('visibility',
		function($groupname) {
			if (!queryGroup($groupname)) {
				return "不存在名为 {$groupname} 的组";
			}
			return '';
		},
		function($type, $groupname) {
			global $blog;
			if ($type === '+') {
				DB::insert("insert into blogs_visibility (blog_id, group_name) values ({$blog['id']}, '$groupname')");
			} else if ($type === '-') {
				DB::delete("delete from blogs_visibility where blog_id = {$blog['id']} and group_name = '$groupname'");
			}
		}
	);

	$visibility_form->runAtServer();
?>
<?php echoUOJPageHeader('修改博客可视权限 - ' . HTML::stripTags($blog['title'])) ?>
<table class="table table-hover">
    <thead>
        <tr>
            <th>#</th>
            <th>组名</rh>
        </tr>
    </thead>
    <tbody>
<?php
    $result = DB::select("select group_name from blogs_visibility where blog_id = {$blog['id']}");
    for ($row_id = 1; $row = DB::fetch($result); ++$row_id)
        echo '<tr>', '<td>', $row_id, '</td>', '<td>', getGroupLink($row['group_name']), '</td>', '</tr>';
?>
    </tbody>
</table>
<p class="text-center">命令格式：命令一行一个，+zhjc 表示把 zhjc 加入可见组，-zhjc 表示把 zhjc 从可见组中移除</p>
<?php $visibility_form->printHTML(); ?>
<?php echoUOJPageFooter() ?>
