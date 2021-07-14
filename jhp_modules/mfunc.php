<?php

if (!function_exists('is_list')) {
	function is_list($arr){
		return array_values($arr) === $arr;
	}
}

if (!function_exists('jscandir')) {
	function jscandir($s){
		return array_diff(
			scandir($s),
			['.', '..']
		);
	}
}