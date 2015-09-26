#!/usr/bin/php
<?php
/*
	Application:	hosting-management
	Version:		0.1
	Author:			René Kliment <rene@renekliment.cz>
	Website:		https://github.com/renekliment/scripts
	License:		DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
*/

// TODO: there is no ACL yet - the owner parameter is not used

$dkimDirectory = '/etc/opendkim/'; 
$keyDirectory = $dkimDirectory . 'keys/';

// Check we're running this in CLI mode
if (php_sapi_name() != 'cli') {
	exit("### Not running in CLI mode!\n");
}

function print_help()
{
	echo "
	./e-mail.php [OPTIONS]
	
	OPTIONS:
		-o username
			sets the domain owner
		-d domain
			specifies the website domain ( usage: -d example.com )
		-a action
			where action is one of these: dadd, dget
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

$parray = array(
	'-o'	=> 'owner',
	'-d'	=> 'domain',
	'-a'	=> 'action',
);

// Parse command line parameters
for ($i=1; $i < $argc; $i++) {
	
	if ($argv[$i] == '-h') {
	
		print_help();
		exit(0);
	
	} elseif ( $i != $argc-1 && in_array($argv[$i], array_keys($parray)) ) {
	
		$clp[ $parray[$argv[$i]] ] = trim($argv[$i+1]);
		$i++;

	}
	
}

$status = 0;

echo "E-mail management:\n";
echo "==================\n";

// Set owner
echo "Owner: ";
if (!empty($clp['owner'])) { 
	$owner = plain_parse($clp['owner']);
	echo $owner."\n";
} else {
	$owner = plain_parse(fgets(STDIN));
}

if (!user_exists($owner)) {
	exit("### Invalid user or user with no home directory!\n");
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
	echo "### Domain is in the system.";
	$allowed_actions = array('dadd', 'dget');
} else {
	echo "### Domain does not seem to exist in the system.";
	$allowed_actions = array();
}

echo " Available actions are: ";
foreach ($allowed_actions as $action) {
	echo $action . ' ';
}
echo "\n";

// Set action
echo "Action: ";
if (!empty($clp['action'])) { 
	$action = plain_parse($clp['action']);
	echo $action."\n";
} else {
	$action = plain_parse(fgets(STDIN));
}

if (!in_array($action, $allowed_actions)) {
	exit("### Action not valid.\n");
}

if ($action == 'dadd') {
    
    if (file_exists($keyDirectory.$domain)) {
        exit("DKIM key for this domain exists already!\n");
    }
    
    system("
        cd $keyDirectory;
        mkdir $domain;
        cd $domain;
        opendkim-genkey -s mail -d $domain;
        chown opendkim:opendkim mail.private;
    ");
    
    system('echo "mail._domainkey.'. $domain .' '. $domain .':mail:'. $keyDirectory . $domain . '/mail.private' . "\n" . '" >> '. $dkimDirectory .'/KeyTable');
    system('echo "*@'. $domain .' mail._domainkey.'. $domain ."\n" . '" >> '. $dkimDirectory .'/SigningTable');    

    system('/etc/init.d/opendkim reload');
    
} elseif ($action == 'dget') {
    if (!file_exists($keyDirectory . $domain . '/mail.txt')) {
        exit("There is no DKIM key for this domain.\n");
    }
    
    echo file_get_contents($keyDirectory . $domain . '/mail.txt');
}

echo "\n";