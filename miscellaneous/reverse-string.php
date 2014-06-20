<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<!--
    Application:	reverse-string
    Version:		1.0
    Author:			René Kliment <rene@renekliment.cz>
    Website:		https://github.com/renekliment/scripts
    License:		DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
-->
	<meta http-equiv="content-language" content="en" />
	<meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8" />
	<title>JS/PHP reverse-string</title>
	<style type="text/css">
		body {
			background-color: #333333;
		}

		textarea[disabled='disabled'] {
			background-color: #444444;
			color: #bbbbbb;
		}
	</style>
	<script type="text/javascript">
		function myReverse()
		{
			var reverse_text = document.getElementById('reverse_text').value;
			var newtext = '';

			for (i=reverse_text.length; i>0; i--) {
				newtext += reverse_text.substr(i-1, 1);
			}

			document.getElementById('reverse_js').value = newtext;
			newtext = null;
		}

		function setOtf()
		{
			var reverse_js_otf = document.getElementById('reverse_js_otf');
			var reverse_text = document.getElementById('reverse_text');

			if (reverse_js_otf.checked == true) {
				myReverse();
				reverse_text.onkeyup = function(){ myReverse(); };
			} else {
				reverse_text.onkeyup = null;
			}
		}

		window.onload = function() {
			var reverse_js_otf = document.getElementById('reverse_js_otf');
			var reverse_js_submit = document.getElementById('reverse_js_submit');

			setOtf();

			reverse_js_submit.onclick = function(){ myReverse(); };
			reverse_js_otf.onchange = function(){ setOtf(); };
		}
	</script>
</head>
<body>

	<h1>JS/PHP reverse-string</h1>

	<form method="post" action="?">
	<fieldset><legend>input</legend>
	<table>
	<tbody>
		<tr>
			<td><label for="reverse_text">Text: </label></td>
			<td><textarea name="reverse_text" id="reverse_text" rows="10" cols="80"><?php if (isset($_POST['reverse_text'])) { echo $_POST['reverse_text']; } ?></textarea></td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" checked="checked" id="reverse_js_otf" />
				<label for="reverse_js_otf">On-the-fly JS reverse</label>
			</td>
		</tr>

		<tr>
			<td></td>
			<td>
				<input type="button" id="reverse_js_submit" value="reverse using JS" />
				<?php echo '<input type="submit" value="reverse using PHP" />'; ?>
			</td>
		</tr>
	</tbody>
	</table>
	</fieldset>
	</form>

	<form>
	<fieldset><legend>output</legend>
	<table>
	<tbody>
		<tr>
			<td><label for="reverse_js">JS reverse: </label></td>
			<td><textarea name="reverse_js" id="reverse_js" rows="10" cols="80" disabled="disabled"></textarea></td>
		</tr>
<?php
//iconv_set_encoding("internal_encoding", "UTF-8");

if (isset($_POST['reverse_text'])) {
	echo '
<tr>
			<td><label for="reverse_php">PHP reverse: </label></td>
			<td>
				<textarea name="reverse_php" id="reverse_php" rows="10" cols="80" disabled="disabled">';

	$text = $_POST['reverse_text'];
	for ($i=strlen($text); $i>0; $i=$i-1) {
		//echo iconv_substr($text, $i-1, 1);
		echo substr($text, $i-1, 1);
	}

	echo '</textarea>
			</td>
		</tr>';
}
?>
	</tbody>
	</table>
	</fieldset>
	</form>
</body>
</html>