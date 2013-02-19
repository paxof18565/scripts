<?php
/*
    Application:  	  Simple single-file guestbook using MySQL
    Version:		  0.2
    Author: 		  René Kliment <rene.kliment@gmail.com>
    Website: 		  https://github.com/renekliment/scripts
    License: 		  GNU Affero General Public License
========================================================================
*/

/* Session start */
session_start();

/* Some config */
define('CONF_GB_PASSWORD'   , 'secretpassword'); // Password to delete a comment
define('CONF_GB_CMTSPP'     ,  10);	 	 // Number of comments shown per page
define('CONF_GB_DB_SERVER'  , 'localhost');	 // MySQL server
define('CONF_GB_DB_USER'    , 'root');		 // MySQL user
define('CONF_GB_DB_PASSWORD', '');		 // MySQL user's password
define('CONF_GB_DB_DATABASE', 'guestbook');	 // MySQL database
define('CONF_GB_DB_TABLE'   , 'guestbook');	 // MySQL table

/* Database connection */
$mysqli = new mysqli(CONF_GB_DB_SERVER, 
		     CONF_GB_DB_USER, 
		     CONF_GB_DB_PASSWORD, 
		     CONF_GB_DB_DATABASE
);

if ($mysqli->connect_errno) {
    exit('Failed to connect to MySQL: (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

/* Some functions */
function tokenGenerate()
{
    $_SESSION['token'] = md5(microtime().rand(0,1000000));
    $_SESSION['token_name'] = substr(md5(microtime().rand(0,1000000)),0,8);
}

function tokenVerify()
{
    $name = $_SESSION['token_name'];
    if (isset($_POST[$name]) AND $_POST[$name] == $_SESSION['token']) {
	tokenGenerate(); 
	return TRUE;
    } else {
	tokenGenerate(); 
	return FALSE;
    }
}

function generateNums()
{
    $_SESSION['num1'] = rand(0,30);
    $_SESSION['num2'] = rand(5,50);
}

/* We need some stuff to be present, so if not, let's generate it! */
if (!isset($_SESSION['token_name']) OR !isset($_SESSION['token'])) {
    tokenGenerate();
}

if (!isset($_SESSION['num1']) OR !isset($_SESSION['num2'])) {
    generateNums();
}

$msg = '';

/* Wanna delete a comment? */
if (isset($_POST['password']) 
    AND $_POST['password']==CONF_GB_PASSWORD 
    AND isset($_POST['id']) 
    AND tokenVerify()
) {
    if ($mysqli->query("DELETE FROM `".CONF_GB_DB_TABLE."` WHERE `id`='".(integer)$_POST['id']."'")) {
	$msg = '<p class="green">Comment with ID \''.(integer)$_POST['id'].'\' has been deleted successfully.</p>';
    } else {
	$msg = '<p class="red">An error has occured while deleting comment with ID \''.(integer)$_POST['id'].'\'.</p>';
    }
}

/* Wanna post a comment? */
if (!empty($_POST['content']) AND tokenVerify()) {
    if ($_POST['num'] != ($_SESSION['num1'] + $_SESSION['num2'])) {
	generateNums();
	$msg = '<p class="red">You have not entered or entered wrong the key!</p>';
    } else {
	generateNums();

        $ip = $_SERVER["REMOTE_ADDR"];
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip .= '|'.$_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ip .= '|'.$_SERVER['HTTP_FORWARDED'];
        }
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip .= '|'.$_SERVER['HTTP_CLIENT_IP'];
        }
        if (isset($_SERVER['X_HTTP_FORWARDED_FOR'])) {
            $ip .= '|'.$_SERVER['X_HTTP_FORWARDED_FOR'];
        }
        if (isset($_SERVER['X_FORWARDED_FOR'])) {
            $ip .= '|'.$_SERVER['X_FORWARDED_FOR'];
        }
        $ip .= "/".gethostbyaddr($_SERVER["REMOTE_ADDR"]);

	$pContent = substr($_POST['content'],0,2000);
	$pName    = substr($_POST['name']   ,0,30);
	$pEmail   = substr($_POST['e-mail'] ,0,100);
	$pIP	  = $ip;

	$stmt = $mysqli->prepare("INSERT INTO `".CONF_GB_DB_TABLE."` (`id`,`timestamp`,`content`,`name`,`e-mail`,`ip`) VALUES (NULL,".time().",?,?,?,?)");
	$stmt->bind_param('ssss', $pContent, $pName, $pEmail, $pIP);
	if ($stmt->execute()) {
	    $msg = '<p class="green">Comment has been added successfully.</p>';
	} else {
	    $msg = '<p class="red">An error has occured while adding your comment.</p>';
	}
	$stmt->close();
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="cs" lang="cs">
<head>
    <meta http-equiv="content-language" content="cs" />
    <meta http-equiv="content-Type" content="application/xhtml+xml; charset=utf-8" />
    <title>Guestbook</title>
    <meta name="description" content="Guestbook" />
    <style type="text/css">
	body {
	    width: 700px;
	}

	h1 {
	    border-bottom: 1px solid black;
	}

	.green {
	    border: 1px groove black;
	    background-color: LimeGreen;
	    font-weight: bold;
	    color: white;
	    padding: 3px;
	}

	.red {
	    border: 1px groove black;
	    background-color: red;
	    font-weight: bold;
	    color: white;
	    padding: 3px;
	}

	.orange {
	    border: 1px groove black;
	    background-color: DarkOrange;
	    font-weight: bold;
	    color: white;
	    padding: 3px;
	}

	.commentTable {
	    font-size: 0.95em;
	    border-collapse: collapse;
	}

	.commentContent {
	    padding: 5px;
	    background-color: #fff0f5;
	    width: 100%;
	}

	#paging {

	}
    </style>
</head>
<body>

<?php
echo $msg;
echo '<h1>Guestbook</h1>';

/* How many comments are there? */
$result = $mysqli->query("SELECT `id` FROM `".CONF_GB_DB_TABLE."`");
$comments = $result->num_rows;
$result->close();

/* Let's show the comments (or not)! */
if (!$comments) {
    echo '<p class="orange">No comment post yet. Be the first!</p>';
} else {
    /* Some paging stuff */
    $perPage = CONF_GB_CMTSPP;
    $pages = ceil($comments/$perPage);
    $page = 1;
    if (isset($_GET['page'])) {
	$page = (int)$_GET['page'];
    
        if (!$page < 1 
	    OR $page > $pages 
	) {
	    $page = 1;
	}
    } 
    
    $beginning = $page * $perPage - $perPage;

    /* Let's get the mess from DB and show it */
    $result = $mysqli->query("SELECT `id`,`timestamp`,`content`,`name`,`e-mail`,`ip` FROM `".CONF_GB_DB_TABLE."` ORDER BY `id` DESC LIMIT ".(int)$beginning.",".(int)$perPage);
    while ($row = $result->fetch_assoc()) {
	$name = $row['name'] ? htmlspecialchars($row['name'], ENT_QUOTES) : '<i>Anonymous</i>';
	$mail = $row['e-mail'] ? '<a href="mailto:'.str_replace('@', ' (at) ', htmlspecialchars($row['e-mail'], ENT_QUOTES).'">[mail]</a>') : '';

	echo '
	<table border="1" width="95%" align="center" class="commentTable">
	<tbody>
	    <tr><td><small>ID: '.$row['id'].'</small> | '.date('Y-m-d H:i:s', $row['timestamp']).' | '.$name.' '.$mail.' - <small>IP: '.$row['ip'].'</small></td></tr>
	    <tr><td class="commentContent">'.nl2br(htmlspecialchars($row['content'], ENT_QUOTES)).'</td></tr>
	</tbody>
	</table><br />
	';
    }
    $result->close();

    /* Are there more pages, than just one? Ok, let's make some code for user to choose the page */
    if ($comments > $perPage) {
	$paging = '<p id="paging">Pages: ';
	for ($a = 1; $a <= $pages; $a++) {
	    $paging .= '<a href="?page='.$a.'">'.$a.'</a>&nbsp;';
	}
	$paging = str_replace('<a href="?page='.$page.'">'.$page.'</a>', '<b><u><a href="?page='.$page.'">'.$page.'</a>', $paging);
	$paging .= '<i>('.$comments.' comments in total)</i></p>';
	
	echo $paging;
    }
}

/* HTML form to post a comment */
echo '
<hr size="1" />
<form action="?" method="post">
<fieldset><legend>Post a comment</legend>
<table>
<tbody>
    <tr>
	<td><label for="name">Your name: </label></td>
	<td><input type="text" name="name" id="name" maxlength="30" /></td>
    </tr>
    <tr>
	<td><label for="e-mail">E-Mail: </label></td>
	<td><input type="text" name="e-mail" id="e-mail" maxlength="100" /></td>
    </tr>
    <tr>
	<td><label for="num">'.$_SESSION['num1'].' + '.$_SESSION['num2'].' = ?</label></td>
	<td><input type="text" name="num" id="num" /></td>
    </tr>
    <tr>
	<td><label for="content">Content <br />(max. 2000 chars.): </label></td>
	<td><textarea name="content" id="content" cols="70" rows="8"></textarea></td>
    </tr>
    <tr>
	<td><input type="hidden" name="'.$_SESSION['token_name'].'" value="'.$_SESSION['token'].'" /></td>
	<td><input type="submit" value="Post" /></td>
    </tr>
</tbody>
</table>
</fieldset>
</form>

<br />

<form action="?" method="post">
<fieldset><legend>Delete a comment</legend>
<table>
<tbody>
    <tr>
	<td><label for="id">ID: </label></td>
	<td><input type="text" name="id" id="id"/></td>
    </tr>
    <tr>
	<td><label for="password">Password: </label></td>
	<td><input type="password" name="password" id="password" /></td>
    </tr>
    <tr>
	<td><input type="hidden" name="'.$_SESSION['token_name'].'" value="'.$_SESSION['token'].'" /></td>
	<td><input type="submit" value="Delete" /></td>
    </tr>
</tbody>
</table>
</fieldset>
</form>

</body>
</html>';

?>