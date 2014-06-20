#!/usr/bin/php5
<?php
/*
	Application:	hosting-management
	Version:		1.0
	Author:			René Kliment <rene@renekliment.cz>
	Website:		https://github.com/renekliment/scripts
	License:		DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
*/

// Check we're running this in CLI mode
if (php_sapi_name() != 'cli') {
	exit("Not running in CLI mode!\n");
}

$status = 0;

echo "(de)activating website:\n";
echo "=======================\n";

echo "Owner: ";
if ($argc == 3 
	&& $argv[1] == '-o'
) { 

	$owner = preg_replace(
		"/[^A-Za-z0-9_]/",
		'',
		trim($argv[2])
	);
	echo $owner."\n";

} else {
	$owner = preg_replace(
		"/[^A-Za-z0-9_]/",
		'',
		trim(fgets(STDIN))
	);
}

if (!$owner OR !is_dir('/home/'.$owner)) {
	exit("Invalid user or user with no home directory!\n");
}

echo "Domain: ";
$domain = preg_replace("/[^A-Za-z0-9.-]/",'',trim(fgets(STDIN)));

$user = $owner.'_'.$domain;
$home = '/home/'.$user;

system('ls -l /home | grep -e _'.$domain.' -e '.$domain, $status);
if ($status != 0 || !file_exists($home)) {
	exit("It seems that this domain is not registered in our databse. If you think this is an error, please contact your system administrator.\n");
}

echo "Action (ac = activate, de = deactivate): ";
$action = trim(fgets(STDIN));
if ($action == 'ac') {

	system('a2ensite '.$user);
	system('mv /etc/php5/fpm/pool.d/'.$user.'.conf.de /etc/php5/fpm/pool.d/'.$user.'.conf');
	system('mv '.$home.'/.ssh/authorized_keys.de '.$home.'/.ssh/authorized_keys');
	
} elseif ($action == 'de') {

	system('a2dissite '.$user);
	system('mv /etc/php5/fpm/pool.d/'.$user.'.conf /etc/php5/fpm/pool.d/'.$user.'.conf.de');
	system('mv '.$home.'/.ssh/authorized_keys '.$home.'/.ssh/authorized_keys.de');
	
} else {
	exit("You entered rubbish!\n");
}

system('/etc/init.d/apache2 reload');
system("/etc/init.d/php5-fpm reload");

echo "\n";