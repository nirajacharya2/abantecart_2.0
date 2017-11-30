<?php
namespace abc;
require 'cli/interface.php';

//process command
$args = $argv;
array_shift($args);
$class = array_shift($args);
if(!preg_match('/^--(.*)$/', $args[0])) {
    $action = array_shift($args);
}else{
    $action = '';
}

if(!$class){
	showError('Syntax Error!');
	echo "Example:\n";
	echo "php do.php [command] [action] [--option1=value] [--option2=value]...\n";
	exit(1);
}

$run_file = __DIR__.'/cli/scripts/'.$class.'/'.$class.'.php';
if(!is_file($run_file)){
	showError("Error: Script ".$class.".php not found in ".__DIR__."/cli/ directory!");
	exit(1);
}
try{
    require $run_file;
	/**
	 * @var \abc\cli\scripts\Install $provider
	 */
	$class_name = "\abc\cli\scripts\\".$class;
	$provider = new $class_name();
}catch(\Exception $e){
	showError('Error: '.$e->getMessage());
	exit(1);
}
//get options
$options = parseOptions($args);

if(!$options && !$action){
    echo $provider->help();
    exit(0);
}

//validate command and options
$errors = (array)$provider->validate($action, $options);

if($errors){
	showError("Validation errors occurred");
	foreach($errors as $error){
		showError("\t".$error);
	}
	exit(1);
}

//run command
try{
	$result = $provider->run($action, $options);
    showResult($result);
}catch(\Exception $e){
	showError('Error: '.$e->getMessage());
	exit(1);
}

//if all fine - run post-trigger

try{
	$result = $provider->finish($action, $options);
    showResult($result);
}catch(\Exception $e){
	showError('Error: '.$e->getMessage());
	exit(1);
}

exit(0);


######################################################
function showResult($result){
    if(is_string($result) && $result){
        echo $result."\n";
    }elseif(is_array($result) && $result){
        showError("Runtime errors occurred");
            foreach($result as $error){
                showError("\t\t".$error);
            }
        exit(1);
    }
}
function parseOptions($args){
	$options = array ();
	foreach ($args as $v) {
		$is_flag = preg_match('/^--(.*)$/', $v, $match);
		//skip commands
		if (!$is_flag){
			continue;
		}

		$arg = $match[1];
		$array = explode('=', $arg);
		if (sizeof($array) > 1) {
			list($name, $value) = $array;
		} else {
			$name = $arg;
			$value = true;
		}
		$options[$name] = trim($value);
	}
	return $options;
}

function showError($text){
	echo("\n\033[0;31m".$text."\033[0m\n\n");
}



