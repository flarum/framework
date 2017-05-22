<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| 驗證
	|--------------------------------------------------------------------------
	*/


	"accepted"         => ":attribute 必須接受。",
	"active_url"       => ":attribute 不是有效的 URL。",
	"after"            => ":attribute 必須是 :date 之後的日期。",
	"alpha"            => ":attribute 只能包含字母。",
	"alpha_dash"       => ":attribute 只能包含字母、數字、中下劃線、破折號。",
	"alpha_num"        => ":attribute 只能包含字母、數字。",
	"before"           => ":attribute 必須是 :date 之前的日期。",
	"between"          => array(
		"numeric" => ":attribute 必須在 :min 和 :max 之間。",
		"file"    => ":attribute 必須在 :min KB 到 :max KB 之間。",
		"string"  => ":attribute 必須在 :min 到 :max 個字符之間。",
	),
	"confirmed"        => ":attribute 與重復輸入不匹配。",
	"date"             => ":attribute 不是有效的日期。",
	"date_format"      => ":attribute 沒有匹配規定的日期格式 :format",
	"different"        => ":attribute 與 :other 必須不相同。",
	"digits"           => ":attribute 必須是 :digits 位數字。",
	"digits_between"   => ":attribute 必須在 :min 到 :max 位數字之間。",
	"email"            => ":attribute 格式不正確。",
	"exists"           => "已經選擇的 :attribute 不是有效的值。",
	"image"            => ":attribute 必須是壹張圖片。",
	"in"               => "已選的 :attribute 非法。",
	"integer"          => ":attribute 必須是壹個整數。",
	"ip"               => ":attribute 必須是壹個有效的IP地址。",
	"max"              => array(
		"numeric" => ":attribute 必須不能大於 :max 。",
		"file"    => ":attribute 必須不能大於 :max KB。",
		"string"  => ":attribute 必須不能大於 :max 個字符。",
	),
	"mimes"            => ":attribute 必須是壹個 :values 類型的文件。",
	"min"              => array(
		"numeric" => ":attribute 必須不能小於 :min 。",
		"file"    => ":attribute 必須不能小於 :min KB。",
		"string"  => ":attribute 必須不能小於 :min 個字符。",
	),
	"not_in"           => "已選的 :attribute 非法。",
	"numeric"          => ":attribute 必須是壹個數字。",
	"regex"            => ":attribute 格式不正確。",
	"required"         => ":attribute 不能為空。",
	"required_if"      => "當 :other 為 :value 時 :attribute 不能為空。",
	"required_with"    => "當 :values 存在時 :attribute 不能為空。",
	"required_without" => "當 :values 不存在時 :attribute 不能為空。",
	"same"             => ":attribute 和 :other 必須匹配。",
	"size"             => array(
		"numeric" => ":attribute 大小必須是 :size",
		"file"    => ":attribute 大小必須是 :size KB。",
		"string"  => ":attribute 必須是 :size 個字符。",
	),
	"unique"           => ":attribute 已經存在。",
	"url"              => ":attribute 不是壹個有效的 URL。",

	/*
	|--------------------------------------------------------------------------
	| 自定義驗證規則
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
	| 自定義驗證屬性
	|--------------------------------------------------------------------------
	|
	| The following language lines are used to swap attribute place-holders
	| with something more reader friendly such as E-Mail Address instead
	| of "email". This simply helps us make messages a little cleaner.
	|
	*/

	'attributes' => array(),

);
