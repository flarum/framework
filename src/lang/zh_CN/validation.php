<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| 验证
	|--------------------------------------------------------------------------
	*/

	"accepted"         => ":attribute 必须接受。",
	"active_url"       => ":attribute 不是有效的 URL。",
	"after"            => ":attribute 必须是 :date 之后的日期。",
	"alpha"            => ":attribute 只能包含字母。",
	"alpha_dash"       => ":attribute 只能包含字母、数字、中下划线、破折号。",
	"alpha_num"        => ":attribute 只能包含字母、数字。",
	"before"           => ":attribute 必须是 :date 之前的日期。",
	"between"          => array(
		"numeric" => ":attribute 必须在 :min 和 :max 之间。",
		"file"    => ":attribute 必须在 :min KB 到 :max KB 之间。",
		"string"  => ":attribute 必须在 :min 到 :max 个字符之间。",
	),
	"confirmed"        => ":attribute 与重复输入不匹配。",
	"date"             => ":attribute 不是有效的日期。",
	"date_format"      => ":attribute 没有匹配规定的日期格式 :format",
	"different"        => ":attribute 与 :other 必须不相同。",
	"digits"           => ":attribute 必须是 :digits 位数字。",
	"digits_between"   => ":attribute 必须在 :min 到 :max 位数字之间。",
	"email"            => ":attribute 格式不正确。",
	"exists"           => "已经选择的 :attribute 不是有效的值。",
	"image"            => ":attribute 必须是一张图片。",
	"in"               => "已选的 :attribute 非法。",
	"integer"          => ":attribute 必须是一个整数。",
	"ip"               => ":attribute 必须是一个有效的IP地址。",
	"max"              => array(
		"numeric" => ":attribute 必须不能大于 :max 。",
		"file"    => ":attribute 必须不能大于 :max KB。",
		"string"  => ":attribute 必须不能大于 :max 个字符。",
	),
	"mimes"            => ":attribute 必须是一个 :values 类型的文件。",
	"min"              => array(
		"numeric" => ":attribute 必须不能小于 :min 。",
		"file"    => ":attribute 必须不能小于 :min KB。",
		"string"  => ":attribute 必须不能小于 :min 个字符。",
	),
	"not_in"           => "已选的 :attribute 非法。",
	"numeric"          => ":attribute 必须是一个数字。",
	"regex"            => ":attribute 格式不正确。",
	"required"         => ":attribute 不能为空。",
	"required_if"      => "当 :other 为 :value 时 :attribute 不能为空。",
	"required_with"    => "当 :values 存在时 :attribute 不能为空。",
	"required_without" => "当 :values 不存在时 :attribute 不能为空。",
	"same"             => ":attribute 和 :other 必须匹配。",
	"size"             => array(
		"numeric" => ":attribute 大小必须是 :size",
		"file"    => ":attribute 大小必须是 :size KB。",
		"string"  => ":attribute 必须是 :size 个字符。",
	),
	"unique"           => ":attribute 已经存在。",
	"url"              => ":attribute 不是一个有效的 URL。",

	/*
	|--------------------------------------------------------------------------
	| 自定义验证规则
	|--------------------------------------------------------------------------
	|
	| Here you may specify custom validation messages for attributes using the
	| convention "attribute.rule" to name the lines. This makes it quick to
	| specify a specific custom language line for a given attribute rule.
	|
	*/

	'custom' => array(),

	/*
	|--------------------------------------------------------------------------
	| 自定义验证属性
	|--------------------------------------------------------------------------
	|
	| The following language lines are used to swap attribute place-holders
	| with something more reader friendly such as E-Mail Address instead
	| of "email". This simply helps us make messages a little cleaner.
	|
	*/

	'attributes' => array(),

);
