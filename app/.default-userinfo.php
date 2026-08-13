<?php

return array(
    'email' => array(
        'max_length' => 50,
        'locale' => array(
            'en' => 'Email',
            'zh-cn' => '电子邮箱'
        ),
        'publish' => true,
        'atregister' => true,
        'allow_empty' => false,
        'form' => function ($data) {
            $res = '<input type="email" class="form-control" name="email" id="input-email" value="' . $data . '" placeholder="' . UOJLocale::get('enter your email') . '" maxlength="50" />';
            return $res;
        },
        'validator_php' => function ($email) {
            return is_string($email) && strlen($email) <= 50 && preg_match('/^(.+)@(.+)$/', $email);
        },
        'validator_js' =>
<<<EOD
function (str) {
    if (str.length > 50) {
        return '电子邮箱地址太长。';
    } else if (! /^(.+)@(.+)$/.test(str)) {
        return '电子邮箱地址非法。';
    } else {
        return '';
    }
}
EOD
    ),
    'motto' => array(
        'max_length' => 200,
        'locale' => array(
            'en' => 'Motto',
            'zh-cn' => '格言'
        ),
        'publish' => true,
        'form' => function ($data) {
            $res = '<textarea class="form-control" id="input-motto" name="motto">' . HTML::escape($data) . ' </textarea>';
            return $res;
        },
        'validator_php' => function ($motto) {
            return is_string($motto) && ($len = mb_strlen($motto, 'UTF-8')) !== false && $len <= 100;
        },
        'validator_js' =>
<<<EOD
function (str) {
    if (str.length > 100) {
        return '不能超过 100 字';
    } else {
        return '';
    }
}
EOD
    ),
    'realname' => array(
        'max_length' => 50,
        'locale' => array(
            'en' => 'Real Name',
            'zh-cn' => '真实姓名'
        ),
        'validator_php' => function ($realname) {
            return is_string($realname) && preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]{0,50}$/u', $realname);
        },
        'validator_js' =>
<<<EOD
function (str) {
    if (str.length > 50) {
        return '真实姓名长度不能超过 15。';
    } else if (! /^[a-zA-Z0-9_\u4e00-\u9fa5]*$/.test(str)) {
        return '真实姓名应只包含中英文字符、数字和下划线。';
    } else {
        return '';
    }
}
EOD
    ),
    'aboutme' => array(
        'max_length' => 20,
        'locale' => array(
            'en' => 'About Me',
            'zh-cn' => '关于我'
        ),
        'placeholder' => array(
            'en' => 'ID number of your blog',
            'zh-cn' => "请填你的博客数字 id"
        ),
        'hidden' => true,
        'validator_php' => function ($about_me) {
            if (validateUInt($about_me)) {;
                $blog_id = (int) $about_me;
                if ($blog_id) {
                    $aid = Auth::id();
                    if (!DB::selectFirst("select poster from blogs where id = '$blog_id' and poster = '{$aid}'"))
                        return "失败：非本人博客";
                }
            }
            return true;
        },
        'validator_js' =>
<<<EOD
function (str) {
	if (str.length > 15) {
		return '博客 ID 长度不能超过 15。';
	} else if (/\D/.test(str)) {
		return '博客 ID 应只包含 0~9 的数字。';
	} else {
		return '';
	}
}
EOD
    ),
    'sex' => array(
        'locale' => array(
            'en' => 'Sex',
            'zh-cn' => '性别'
        ),
        'validator_php' => function ($sex) {
            return $sex === 'U' || $sex === 'M' || $sex === 'F';
        },
        'default' => 'U',
        'allow_empty' => false,
        'hidden' => true,
        'form' => function ($data) {
            $res = '<select class="form-control" id="input-sex" name="sex">';
            foreach ([["U", "refuse to answer"], ["M", "male"], ["F", "female"]] as $opt) {
				$res .= '<option value="' . $opt[0] . '"' . ($data == $opt[0] ? ' selected="selected"' : '') . '>' . UOJLocale::get($opt[1]) . '</option>';
            }
			$res .= '</select>';
            return $res;
        }
    )
);
