<?php
global $trans;
$trans = array(
	"C++" => "C++98",
	"C" => "C99",
	"Python2.7" => "Python2",
	"Java7" => "Java8"
);
function solve(&$dd) {
	global $trans;
	if (is_array($dd)) {
		foreach ($dd as &$i)
			solve($i);
		unset($i);
	} else if (isset($trans[$dd])) {
		$dd = $trans[$dd];
	}
}
$problems = DB::select("select id, content from submissions");
while ($pr = DB::fetch($problems, MYSQLI_ASSOC)) {
	global $solve;
	$content = json_decode($pr['content'], true);
	solve($content);
	$esc_c = DB::escape(json_encode($content));
	DB::update("update submissions set content=\"" . $esc_c . "\" where id=" . $pr['id']);
	error_log('now ' . $pr['id']);
}