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

echo "WIPING website:\n";
echo "===============\n";

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

echo "\n";