<?php

require 'str.php';
require 'fs.php';

class Editor {
	public static $VhostsPath = '';
	public static $hostsPath = '';
	public static $projectPath = '';

	public static $Vhosts = '';
	public static $hostsFile = '';

	public static $ip = '';

	public function __construct($ip){
		if (!is_writable(static::$VhostsPath) && !is_writable(static::$hostsPath)){
			exit('Нет прав');
		}
		if (static::$VhostsPath) {
			static::$Vhosts = file_get_contents(static::$VhostsPath);
		}
		if (static::$hostsPath) {
			static::$hostsFile = file_get_contents(static::$hostsPath);
		}
		static::$ip = $ip;
	}

	public function addProject($name){
		$PP = static::$projectPath;
		if (!file_exists("$PP/$name")){
			mkdir("$PP/$name");
			mkdir("$PP/$name/public_html");
			$this->addHosts($name);
			$this->addVhosts($name);
			echo "проект $name Добавлен" . "\n";
		} else {
			echo "проект $name уже существует" . "\n";
		}
	}

	public function renameProject($prevName, $name){
		$PP = static::$projectPath;
		if (!file_exists("$PP/$name")){
			rename("$PP/$prevName", "$PP/$name");
			$this->renameHosts($prevName, $name);
			$this->renameVhosts($prevName, $name);
			echo "проект $prevName Переименован в $name" . "\n";
		} else {
			echo "проект $prevName не существует" . "\n";
		}
	}

	public function deleteProject($name){
		$PP = static::$projectPath;
		if (file_exists("$PP/$name")){
			fs::clean("$PP/$name");
			$this->deleteHosts($name);
			$this->deleteVhosts($name);
			rmdir("$PP/$name");
			echo "проект $name удалён" . "\n";
		} else {
			echo "проект $name не существует" . "\n";
		}
	}

	public function addHosts($name){
		$text = static::$ip."\t".$name;
		if (!str_contains(static::$hostsFile, $text)) {
			static::$hostsFile = str_replace("\n\n", "\n$text\n\n", static::$hostsFile);
			file_put_contents(static::$hostsPath, static::$hostsFile);
		} else {
			echo "проект $name уже существует в hosts" . "\n";
		}
	}

	public function renameHosts($prevName, $name){
		$what = static::$ip."\t".$prevName;
		if (str_contains(static::$hostsFile, $what)) {
			if (!str_contains(static::$hostsFile, $name)){
				$text = static::$ip."\t".$name;
				static::$hostsFile = str_replace($what, $text, static::$hostsFile);
				file_put_contents(static::$hostsPath, static::$hostsFile);
			} else {
				echo "проект $name уже существует в hosts" . "\n";
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

	public function addVhosts($name){
		$template = file_get_contents(__DIR__.'/template.txt');
		$ready_template = str::sprintf2($template, [
			'name' => $name
		]);
		if (!str_contains(static::$Vhosts, $ready_template)) {
			file_put_contents(static::$VhostsPath, "\n\n".$ready_template, FILE_APPEND);
		} else {
			echo "проект $name уже существует в vhosts" . "\n";
		}
	}

	public function renameVhosts($prevName, $name){
		$template = file_get_contents(__DIR__.'/template.txt');
		$ready_template_prev = str::sprintf2($template, [
			'name' => $prevName
		]);
		$ready_template = str::sprintf2($template, [
			'name' => $name
		]);
		if (str_contains(static::$Vhosts, $ready_template_prev)) {
			if (!str_contains(static::$Vhosts, $ready_template)){
				$res = str_replace($ready_template_prev, $ready_template, static::$Vhosts);
				file_put_contents(static::$VhostsPath, $res);
			} else {
				echo "проект $name уже существует в vhosts" . "\n";
			}		
		} else {
			echo "проект $name не существует в vhosts" . "\n";
		}
	}

	public function deleteVhosts($name){
		$template = file_get_contents(__DIR__.'/template.txt');
		$ready_template = str::sprintf2($template, [
			'name' => $name
		]);
		if (str_contains(static::$Vhosts, $ready_template)) {
			$res = str_replace("\n\n".$ready_template, '', static::$Vhosts);
			file_put_contents(static::$VhostsPath, $res);
		} else {
			echo "проект $name не существует в vhosts" . "\n";
		}
	}
}
$user = getenv('USER');
Editor::$VhostsPath = '/opt/lampp/etc/extra/httpd-vhosts.conf';
Editor::$hostsPath = '/etc/hosts';
Editor::$projectPath = "/home/$user/www";
$editor = new Editor('127.0.1.1');
$first  = @$argv[1];
$second = @$argv[2];
$third  = @$argv[3];
switch ($first) {
	case 'add':
		$editor->addProject($second);
		break;
	
	case 'rename':
		$editor->renameProject($second, $third);
		break;
	
	case 'delete':
		$editor->deleteProject($second);
		break;
}