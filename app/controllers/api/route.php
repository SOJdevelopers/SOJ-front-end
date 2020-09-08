<?php

Route::pattern('tab', '\S{1,20}');
Route::group([
        'domain' => '('.UOJConfig::$data['web']['main']['host'].'|127.0.0.1'.')'
    ], function() {
        Route::any('/api/hidden', '/api/hidden.php');
        Route::any('/api/user', '/api/user.php');
        Route::any('/api/problem', '/api/problem.php');
        Route::any('/api/contest', '/api/contest.php');
        Route::any('/api/group', '/api/group.php');
        Route::any('/api/regentoken', '/api/regentoken.php');
    }
);
