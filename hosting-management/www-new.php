#!/usr/bin/php5
<?php
/*
	Application:	hosting-management
	Version:		1.0
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

$status = 0;

echo "Create website:\n";
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

system('ls -l /home | grep -e _'.$domain.' -e '.$domain, $status);
if ($status == 0) {
	exit("It seems that a configuration for this domain already exists. If you think this is an error, please contact your system administrator.\n");
}

$user = $owner.'_'.$domain;
$home = '/home/'.$user;

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
echo "\n";