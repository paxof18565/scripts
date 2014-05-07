#!/usr/bin/php5
<?php
/*
    Application:  	  create-domain-apache
    Version:		  0.3
    Author: 		  René Kliment <rene.kliment@gmail.com>
    Website: 		  https://github.com/renekliment/scripts
    License: 		  GNU Affero General Public License
========================================================================
*/

$template_file = './user_website.tld';

// Check we're running this in CLI mode
if (php_sapi_name() != 'cli') {
	exit("Not running in CLI mode!\n");
}

// Check for template
if (!file_exists($template_file)) {
	exit("Template file not found! Check that the template is in the same directory as this script and that you're running this script from the directory it is located in.\n");
}

$status = 0;

echo "Create website:\n";
echo "===============\n";

echo "User: ";
if ($argc == 3 
	&& $argv[1] == '-u'
) { 

	$user = $argv[2];
	echo $user."\n";

} else {
	$user = preg_replace(
		"/[^A-Za-z0-9_]/",
		'',
		trim(fgets(STDIN))
	);
}

if (!$user OR !is_dir('/home/'.$user)) {
	exit("Invalid user or user with no home directory!\n");
}

echo "Website: ";
$website = preg_replace("/[^A-Za-z0-9.-]/",'',trim(fgets(STDIN)));

system('ls -l /home | grep -e _'.$website.' -e '.$website, $status);
if ($status == 0) {
	exit("It seems that a configuration for this domain already exists. If you think this is an error, please contact your system administrator.\n");
}

$username = $user.'_'.$website;
$homedir = '/home/'.$username;

system('useradd -m '.$username, $status);
if ($status != 0) {
	exit("An error occured while adding website user ($status).\n");
}

system('chmod o-r-w-x '.$homedir);
mkdir($homedir.'/websites/'.$website.'/www/', 0770, TRUE);
system('chown '.$username.' '.$homedir.'/websites/ -R');
system('chgrp '.$username.' '.$homedir.'/websites/ -R');

system('mkdir '.$homedir.'/.ssh');
system('cp /home/'.$user.'/.ssh/authorized_keys '.$homedir.'/.ssh/');
system('chown '.$username.' '.$homedir.'/.ssh/authorized_keys -R');
system('chgrp '.$username.' '.$homedir.'/.ssh/authorized_keys -R');

$template = file_get_contents($template_file);
$template = str_replace('user', $username, $template);
$template = str_replace('website.tld', $website, $template);

$filename = '/etc/apache2/sites-available/'.$user.'_'.$website;

if (!$handle = fopen($filename, 'w')) {
	exit("Cannot open file ($filename)\n");
}

if (fwrite($handle, $template) === FALSE) {
	exit("Cannot write to file ($filename)\n");
}

echo "Success writing to file ($filename)\n";

fclose($handle);

system('a2ensite '.$user.'_'.$website);
system('/etc/init.d/apache2 reload');

echo "\n";