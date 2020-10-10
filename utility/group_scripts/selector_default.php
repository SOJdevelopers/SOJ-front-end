<?php
$cols = preg_split('/[\n\r]/', $args, -1, PREG_SPLIT_NO_EMPTY);
$result = array();
foreach ($cols as $col) {
    $tmp = array();
    $ele = preg_split('/[,"\']/', $col, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($ele as $p) {
        $p = trim($p);
        // if (!($p = trim($p))) continue;
        $tmp []= $p;
    }
    $result []= $tmp;
}
