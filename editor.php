<?php

require '/home/kasiro/Документы/projects/mphp/file_req/str.php';
require '/home/kasiro/Документы/projects/mphp/file_req/fs.php';

class Editor {
	public static $filePath = '';
	public static $hostsPath = '';
	public static $projectPath = '';

	public static $file = '';
	public static $hostsFile = '';

	public static $ip = '';
	public static $user = function getenv('USER');

	public function __construct($ip){
		if (static::$filePath) {
			static::$file = file_get_contents(static::$filePath);
		}
		if (static::$hostsPath) {
			static::$hostsFile = file_get_contents(static::$hostsPath);
		}
		static::$ip = $ip;
	}

	public function addProject($name){
		$user = static::$user;
		if (!file_exists("/home/$user/www/$name")){
			mkdir("/home/$user/www/$name");
			mkdir("/home/$user/www/$name/public_html");
			echo "проект $name Добавлен" . "\n";
		} else {
			echo "проект $name уже существует" . "\n";
		}
	}

	public function renameProject($prevName, $name){
		$user = static::$user;
		if (!file_exists("/home/$user/www/$name")){
			rename("/home/$user/www/$prevName", "/home/$user/www/$name");
			echo "проект $prevName Переименован в $name" . "\n";
		} else {
			echo "проект $prevName не существует" . "\n";
		}
	}

	public function delProject($name){
		$user = static::$user;
		if (file_exists("/home/$user/www/$name")){
			fs::clean("/home/$user/www/$name");
			rmdir("/home/$user/www/$name");
			echo "проект $name удалён" . "\n";
		} else {
			echo "проект $name не существует" . "\n";
		}
	}

	public function addHosts($name){
		$text = static::$ip."\t".$name;
		if (!str_contains(static::$hostsFile, $text)) {
			static::$hostsFile = str_replace("\n\n", "\n$text\n\n\n", static::$hostsFile);
			file_put_contents(static::$hostsPath, static::$hostsFile);
			return true;
		}
		return false;
	}

	public function renameHosts($prevName, $name){
		$what = static::$ip."\t".$prevName;
		if (str_contains(static::$hostsFile, $what)) {
			if (!str_contains(static::$hostsFile, $text)){
				$text = static::$ip."\t".$name;
				static::$hostsFile = str_replace($what, $text, static::$hostsFile);
				file_put_contents(static::$hostsPath, static::$hostsFile);
			} else {
				echo "проект $name уже существует  в hosts" . "\n";
			}
		} else {
			echo "проект $prevName не существует в hosts" . "\n";
		}
	}

	public function deleteHosts($name){
		$text = static::$ip."\t".$name;
		static::$hostsFile = str_replace($text, '', static::$hostsFile);
		static::$hostsFile = str_replace("\n\n", "\n", static::$hostsFile);
		file_put_contents(static::$hostsPath, static::$hostsFile);
	}

	public function addFile($name){
		$template = file_get_contents(__DIR__.'/template.txt');
		$ready_template = str::sprintf2($template, [
			'name' => $name
		]);
		file_put_contents(static::$hostsPath, "\n\n".$ready_template, FILE_APPEND);
	}

	public function delFile($name){
		$template = file_get_contents(__DIR__.'/template.txt');
		$ready_template = str::sprintf2($template, [
			'name' => $name
		]);

	}
}
$user = Editor::$user;
Editor::$filePath = './file';
Editor::$hostsPath = './hostsFile';
Editor::$projectPath = "/home/$user/www";
$editor = new Editor('127.0.1.1');
$editor->addHosts('test3.local');
// $editor->renameHosts('test2.local', 'test3.local');
$editor->deleteHosts('test2.local');