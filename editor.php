<?php

class Projector {

	public function getVhostsProjects($text, $black = []) {
		$res = preg_match_all('/^\s{4}ServerName ([^ ]*)$/m', $text, $match);
		if ($res) {
			$arr = array_filter($match[1], fn($e) => !in_array($e, $black));
			$newArr = [];
			foreach ($arr as $el){
				$newArr[] = $el;
			}
			return $newArr;
		} else return false;
	}

	public function getHostsProjects($text, $black = []) {
		$res = preg_match_all('/\t(.*)/m', $text, $match);
		if ($res) {
			$arr = array_filter($match[1], fn($e) => !in_array($e, $black));
			$newArr = [];
			foreach ($arr as $el){
				$newArr[] = $el;
			}
			return $newArr;
		} else return false;
	}

}

class Editor extends Projector {
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
			$this->VhostsProjectList = $this->getVhostsProjects(static::$Vhosts, [
				'localhost'
			]);
			// print_r($this->VhostsProjectList);
		}
		if (static::$hostsPath) {
			static::$hostsFile = file_get_contents(static::$hostsPath);
			$this->hostsProjectList = $this->getHostsProjects(static::$hostsFile, [
				'localhost',
				'kasiro-MS-7788'
			]);
			// print_r($this->hostsProjectList);
		}
		if ($this->VhostsProjectList == $this->hostsProjectList){
			echo 'Projects: hosts == vhosts ('.count($this->VhostsProjectList).')' . "\n";
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
			fs::folder_copy(__DIR__.'/templates/public_html', "$PP/$name/public_html");
			system("chmod 777 -R $PP");
			echo "папка $name Добавлен" . "\n";
			$this->check_ok($name);
		} else {
			echo "папка $name уже существует" . "\n";
		}
	}

	public function renameProject($prevName, $name){
		$PP = static::$projectPath;
		if (!file_exists("$PP/$name")){
			rename("$PP/$prevName", "$PP/$name");
			$this->renameHosts($prevName, $name);
			$this->renameVhosts($prevName, $name);
			system("chmod 777 -R $PP");
			echo "папка $prevName Переименован в $name" . "\n";
			// $this->check_ok($name);
		} else {
			echo "папка $name уже существует" . "\n";
		}
	}

	public function deleteProject($name){
		$PP = static::$projectPath;
		if (file_exists("$PP/$name")){
			fs::clean("$PP/$name");
			$this->deleteHosts($name);
			$this->deleteVhosts($name);
			rmdir("$PP/$name");
			echo "папка $name удалён" . "\n";
			$this->check_ok($name, 'delete');
		} else {
			$this->deleteHosts($name);
			$this->deleteVhosts($name);
			$this->check_ok($name, 'delete');
			echo "папка $name не существует" . "\n";
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
			echo "$name уже существует в hosts" . "\n";
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
				echo "$name уже существует в hosts" . "\n";
			}
		} else {
			echo "$prevName не существует в hosts" . "\n";
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
				echo "$name уже существует в vhosts" . "\n";
			}		
		} else {
			echo "$name не существует в vhosts" . "\n";
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
		}
	}

	public function mycheck($name, $temp, $action){
		switch ($action) {
			case 'hosts':
				$ProjectList = $temp;
				$NewProjectList = $this->hostsProjectList;
				break;

			case 'vhosts':
				$ProjectList = $temp;
				$NewProjectList = $this->VhostsProjectList;
				break;
			
			default:
				return false;
				break;
		}
		if (!in_array($name, $ProjectList) && in_array($name, $NewProjectList)) {
			switch ($action) {
				case 'add':
					echo "$name не добавился в $action" . "\n";
					break;
				
				case 'delete':
					echo "$name удалился из $action" . "\n";
					break;
			}
		} else {
			if (!in_array($name, $NewProjectList)) {
				switch ($action) {					
					case 'delete':
						echo "$name не был в $action" . "\n";
						break;
				}
			} else {
				switch ($action) {
					case 'add':
						echo "$name добавился в $action" . "\n";
						break;
					
					case 'delete':
						echo "$name не удалился из $action" . "\n";
						break;
				}
			}
		}
	}

	public function check_ok($name, $action = 'add'){
		if (!is_writable(static::$VhostsPath) || !is_writable(static::$hostsPath)){
			exit('Нет прав');
		}
		sleep(3);
		if (static::$VhostsPath) {
			static::$Vhosts = file_get_contents(static::$VhostsPath);
			$VhostsProjectList = $this->getVhostsProjects(static::$Vhosts, [
				'localhost'
			]);
			// print_r($this->VhostsProjectList);
		}
		if (static::$hostsPath) {
			static::$hostsFile = file_get_contents(static::$hostsPath);
			$hostsProjectList = $this->getHostsProjects(static::$hostsFile, [
				'localhost',
				'kasiro-MS-7788'
			]);
			// print_r($this->hostsProjectList);
		}
		$this->mycheck($name, $hostsProjectList, 'hosts');
		$this->mycheck($name, $VhostsProjectList, 'vhosts');
	}
}
require './jhp_modules/mfunc.php';
require './jhp_modules/str.php';
require './jhp_modules/fs.php';
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
	case 'remove':
		$editor->deleteProject($second);
		break;

	case 'move':
		$editor->moveProject($second, $third);
		break;

	case 'list':
		$pr = jscandir(Editor::$projectPath);
		foreach ($pr as $f){
			echo $f . "\n";
		}
		break;
}