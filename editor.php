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
			system("chmod 777 -R $PP");
			echo "проект $name Добавлен" . "\n";
			$this->check_ok($name);
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
			system("chmod 777 -R $PP");
			echo "проект $prevName Переименован в $name" . "\n";
			// $this->check_ok($name);
		} else {
			echo "проект $name уже существует" . "\n";
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
			$this->check_ok($name, 'delete');
		} else {
			$this->deleteHosts($name);
			$this->deleteVhosts($name);
			$this->check_ok($name, 'delete');
			echo "проект $name не существует" . "\n";
		}
	}

	public function moveProject($prevName, $name){
		$PP = static::$projectPath;
		fs::folder_copy("$PP/$prevName/public_html", "$PP/$name/public_html");
		fs::clean("$PP/$prevName/public_html");
		system("chmod 777 -R $PP");
		echo "проект $prevName Перемешён в $name" . "\n";
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
		if (str_contains(static::$hostsFile, $name)) {
			static::$hostsFile = str_replace($text, '', static::$hostsFile);
			static::$hostsFile = str_replace("\n\n", "\n", static::$hostsFile);
			file_put_contents(static::$hostsPath, static::$hostsFile);
		}
	}

	public function addVhosts($name){
		$PP = static::$projectPath;
		$template = file_get_contents(__DIR__.'/template.txt');
		$ready_template = str::sprintf2($template, [
			'Project' => $PP,
			'name' => $name
		]);
		if (!str_contains(static::$Vhosts, $ready_template)) {
			file_put_contents(static::$VhostsPath, "\n\n".$ready_template, FILE_APPEND);
		} else {
			echo "проект $name уже существует в vhosts" . "\n";
		}
	}

	public function renameVhosts($prevName, $name){
		$PP = static::$projectPath;
		$template = file_get_contents(__DIR__.'/template.txt');
		$ready_template_prev = str::sprintf2($template, [
			'Project' => $PP,
			'name' => $prevName
		]);
		$ready_template = str::sprintf2($template, [
			'Project' => $PP,
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
		$PP = static::$projectPath;
		$template = file_get_contents(__DIR__.'/template.txt');
		$ready_template = str::sprintf2($template, [
			'Project' => $PP,
			'name' => $name
		]);
		if (str_contains(static::$Vhosts, $name)) {
			$res = str_replace("\n\n".$ready_template, '', static::$Vhosts);
			file_put_contents(static::$VhostsPath, $res);
		} else {
			echo "проект $name не существует в vhosts" . "\n";
		}
	}

	public function check_ok($name, $action = 'add'){
		if (!is_writable(static::$VhostsPath) || !is_writable(static::$hostsPath)){
			exit('Нет прав');
		}
		sleep(3);
		if (static::$VhostsPath) {
			static::$Vhosts = file_get_contents(static::$VhostsPath);
		}
		if (static::$hostsPath) {
			static::$hostsFile = file_get_contents(static::$hostsPath);
		}
		if (!str_contains(static::$hostsFile, $name)) {
			switch ($action) {
				case 'add':
					echo "проект $name не добавился в hosts" . "\n";
					break;
				
				case 'delete':
					echo "$name удалился из hosts" . "\n";
					break;
			}
		} else {
			switch ($action) {
				case 'add':
					echo "проект $name добавился в hosts" . "\n";
					break;
				
				case 'delete':
					echo "$name не удалился из hosts" . "\n";
					break;
			}
		}
		if (!str_contains(static::$Vhosts, $name)) {
			switch ($action) {
				case 'add':
					echo "проект $name не добавился в vhosts" . "\n";
					break;
				
				case 'delete':
					echo "$name удалился из vhosts" . "\n";
					break;
			}
		} else {
			switch ($action) {
				case 'add':
					echo "проект $name добавился в vhosts" . "\n";
					break;
				
				case 'delete':
					echo "$name не удалился из vhosts" . "\n";
					break;
			}
		}
	}
}
Editor::$VhostsPath = '/opt/lampp/etc/extra/httpd-vhosts.conf';
Editor::$hostsPath = '/etc/hosts';
Editor::$projectPath = '/home/kasiro/www';
$editor = new Editor('127.0.0.1');
$first  = @$argv[1];
$second = @$argv[2];
$third  = @$argv[3];
switch ($first) {
	case 'add':
		switch ($second) {
			case 'hosts':
				$editor->addHosts($third);
				break;

			case 'vhosts':
				$editor->addVhosts($third);
				break;
			
			default:
				$editor->addProject($second);
				break;
		}
		break;
	
	case 'rename':
		$editor->renameProject($second, $third);
		break;
	
	case 'delete':
		$editor->deleteProject($second);
		break;

	case 'move':
		$editor->moveProject($second, $third);
		break;
}