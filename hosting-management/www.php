#!/usr/bin/php
<?php
/*
	Application:	hosting-management
	Version:		2.0
	Author:			René Kliment <rene@renekliment.cz>
	Website:		https://github.com/renekliment/scripts
	License:		DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
*/

$template_file['php'] = './www-php-pool.template';
$template_file['apache'] = './www-apache.template';

// Check we're running this in CLI mode
if (php_sapi_name() != 'cli') {
	exit("Not running in CLI mode!\n");
}

// Check for template files
foreach ($template_file as $file) {
	if (!file_exists($file)) {
		exit("Template file '". $file ."' not found! Check that the template is in the same directory as this script and that you're running this script from the directory it is located in.\n");
	}
}

function print_help()
{
	echo "
	./www.php [OPTIONS]
	
	OPTIONS:
		-o username
			sets the website (domain) owner
		-d domain
			specifies the website domain ( usage: -d example.com )
		-a action
			where action is one of these: add, wipe, ac, de
		-h
			prints out this help and terminates script execution\n\n";
}

$clp = array(
	'owner'		=> '',
	'domain'	=> '',
	'action'	=> '',
);

function plain_parse($string)
{
	return preg_replace(
		"/[^A-Za-z0-9_.-]/",
		'',
		trim($string)
	);
}


function user_exists($user)
{
	if (empty($user) OR !is_dir('/home/'.$user)) {
		return false;
	}

	return true;
}

function domain_exists($domain)
{
	$status = 0;
	
	system('ls -l /home | grep -e _'.$domain.' -e '.$domain, $status);
	if ($status == 0) {
		return true;
	}
	 
	return false;
}

// Parse command line parameters
for ($i=1; $i < $argc; $i++) {
	
	if ($argv[$i] == '-h') {
		print_help();
		exit(0);
	} elseif ($argv[$i] == '-o' && $i != $argc-1) {
		$clp['owner'] = trim($argv[$i+1]);
	} elseif ($argv[$i] == '-d' && $i != $argc-1) {
		$clp['domain'] = trim($argv[$i+1]);
	} elseif ($argv[$i] == '-a' && $i != $argc-1) {
		$clp['action'] = trim($argv[$i+1]);
	}
	
}

$status = 0;

echo "Website management:\n";
echo "===================\n";

// Set owner
$owner = '';

if (!empty($clp['owner'])) { 

	$owner = plain_parse($clp['owner']);
	if (!user_exists($owner)) {
		$owner = '';
		echo "Given owner username does not seem to exist. Please enter a valid username of the owner.\n";
	} else {
		echo "Owner: ";
		echo $owner."\n";
	}
}

if (empty($owner)) {

	echo "Owner: ";
	$owner = plain_parse(fgets(STDIN));
	if (!user_exists($owner)) {
		exit("Invalid user or user with no home directory!\n");
	}
	
}

// Set domain
echo "Domain: ";
if (!empty($clp['domain'])) { 
	$domain = plain_parse($clp['domain']);
	echo $domain."\n";
} else {
	$domain = plain_parse(fgets(STDIN));
}

if (domain_exists($domain)) {
	echo "Domain is in the system. Available actions are: wipe, ac, de\n";
	$allowed_actions = array('wipe', 'ac', 'de');
} else {
	echo "Domain does not seem to exist in the system. Available actions are: add\n";
	$allowed_actions = array('add');
}

// Set action
echo "Action: ";
if (!empty($clp['action'])) { 
	$action = plain_parse($clp['action']);
	echo $action."\n";
} else {
	$action = plain_parse(fgets(STDIN));
}

if (!in_array($action, $allowed_actions)) {
	exit("Action not valid.\n");
}

$user = $owner.'_'.$domain;
$home = '/home/'.$user;

if ($action == 'add') {

	system('useradd -m '.$user, $status);
	if ($status != 0) {
		exit("An error occured while adding website user ($status).\n");
	}

	system('chmod o-r-w-x '.$home);
	mkdir($home.'/websites/'.$domain.'/www/', 0770, TRUE);
	system('ln -s '.$home.'/websites/'.$domain.'/www/'.' '.$home.'/websites/'.$domain.'/_');
	system('chown '.$user.':'.$user.' '.$home.'/websites/ -R');

	system('mkdir '.$home.'/.ssh');
	system('cp /home/'.$owner.'/.ssh/authorized_keys '.$home.'/.ssh/');
	system('chown '.$user.':'.$user.' '.$home.'/.ssh/ -R');

	// Apache configuration

	$template = file_get_contents($template_file['apache']);
	$template = str_replace('{user}', $user, $template);
	$template = str_replace('{domain}', $domain, $template);

	$filename = '/etc/apache2/sites-available/'.$user;

	if (!$handle = fopen($filename, 'w')) {
		exit("Cannot open file ($filename)\n");
	}

	if (fwrite($handle, $template) === FALSE) {
		exit("Cannot write to file ($filename)\n");
	}

	echo "Success writing to file ($filename)\n";
	fclose($handle);

	// PHP pool configuration

	$template = file_get_contents($template_file['php']);
	$template = str_replace('{user}', $user, $template);

	$filename = '/etc/php5/fpm/pool.d/'.$user.'.conf';

	if (!$handle = fopen($filename, 'w')) {
			exit("Cannot open file ($filename)\n");
	}

	if (fwrite($handle, $template) === FALSE) {
			exit("Cannot write to file ($filename)\n");
	}

	echo "Success writing to file ($filename)\n";
	fclose($handle);

	system("gpasswd -a www-data ". $user);

	system("/etc/init.d/php5-fpm reload");

	system('a2ensite '.$user);
	system('/etc/init.d/apache2 reload');
} elseif ($action == 'wipe') {

	echo "You are about to WIPE domain ". strtoupper($domain) .' belonging to '. $owner . ' ... ARE YOU SURE? CTRL+C to abort, enter to continue';
	fgets(STDIN);

	system('a2dissite '.$user);
	system('/etc/init.d/apache2 reload');

	echo unlink('/etc/php5/fpm/pool.d/'.$user.'.conf') ? "PHP pool configuration deleted successfully.\n" : "Error occured when deleting PHP pool configuration!\n";
	echo unlink('/etc/apache2/sites-available/'.$user) ? "Apache domain configuration deleted successfully.\n" : "Error occured when deleting Apache domain configuration!\n";

	system("/etc/init.d/php5-fpm reload");

	system("gpasswd -d www-data ". $user);

	system('userdel -r -f '.$user, $status);
	if ($status != 0) {
		exit("An error occured while removing website user ($status).\n");
	}

} elseif ($action == 'ac') {

	system('a2ensite '.$user);
	system('mv /etc/php5/fpm/pool.d/'.$user.'.conf.de /etc/php5/fpm/pool.d/'.$user.'.conf');
	system('mv '.$home.'/.ssh/authorized_keys.de '.$home.'/.ssh/authorized_keys');
	
	system("/etc/init.d/php5-fpm reload");
	system('/etc/init.d/apache2 reload');
	
} elseif ($action == 'de') {

	system('a2dissite '.$user);
	system('mv /etc/php5/fpm/pool.d/'.$user.'.conf /etc/php5/fpm/pool.d/'.$user.'.conf.de');
	system('mv '.$home.'/.ssh/authorized_keys '.$home.'/.ssh/authorized_keys.de');
	
	system('/etc/init.d/apache2 reload');
	system("/etc/init.d/php5-fpm reload");
	
}

echo "\n";