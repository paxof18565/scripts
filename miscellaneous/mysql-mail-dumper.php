<?php
/*
    Application:	mysql-mail-dumper
    Version:		1.0
    Author:			René Kliment <rene@renekliment.cz>
    Website:		https://github.com/renekliment/scripts
    License:		DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
*/

define('CFG_SLEEPTIME', 1);
define('CFG_PASSWORD', 'password');

// run like http://www.example.com/mysql-mail-dumper.php?passs=password

if (isset($_GET['passs']) AND $_GET['passs']==CFG_PASSWORD) {

	/* Configuration */
	define('CFG_MYSQL_HOST', 'localhost:3305');
	define('CFG_MYSQL_USER', 'root');
	define('CFG_MYSQL_PASS', '');
	define('CFG_MYSQL_DB',   'database');

	define('CFG_MAIL_ADDRESS', 'john.smith@example.com');
	define('CFG_MAIL_SUBJECT', 'Dump of MySQL DB for http://'.CFG_MYSQL_HOST.' - '.date("j.n.Y G:i:s"));

	// This code is actually stolen from somewhere (phpMyAdmin I guess)

	/* Functions */
	function getTableDef($table)
	{
		$result = mysql_query("SHOW CREATE TABLE $table");
		$data = mysql_fetch_array($result);
		mysql_free_result($result);

		return $data[1] . ";\n\n";
	}

	function getTableContent($table)
	{
		global $use_backquotes;
		global $rows_cnt;
		global $current_row;

		$schema_insert = "";
		$local_query = "SELECT * FROM $table";
		$result = mysql_query($local_query);
		if ($result != FALSE) {
			$fields_cnt = mysql_num_fields($result);
			$rows_cnt = mysql_num_rows($result);
			// Checks whether the field is an integer or not
			for ($j = 0; $j < $fields_cnt; $j++) {
				$field_set[$j] = mysql_field_name($result, $j);
				$type          = mysql_field_type($result, $j);
				if ($type == 'tinyint' ||
					$type == 'smallint' ||
					$type == 'mediumint' ||
					$type == 'int' ||
					$type == 'bigint' ||
					$type == 'timestamp'
				) {
					$field_num[$j] = TRUE;
				} else {
					$field_num[$j] = FALSE;
				}
			}

			// Sets the scheme
			$search       = array("\x00", "\x0a", "\x0d", "\x1a"); //\x08\\x09, not required
			$replace      = array('\0', '\n', '\r', '\Z');
			$current_row  = 0;
			while ($row = mysql_fetch_row($result)) {
				$schema_insert .= "INSERT INTO $table VALUES (";
				$current_row++;

				for ($j = 0; $j < $fields_cnt; $j++) {
					if (!isset($row[$j])) {
						$values[] = 'NULL';
					} elseif ($row[$j] == '0' || $row[$j] != '') {
						// a number
						if ($field_num[$j]) {
							$values[] = $row[$j];
						}
						// a string
						else {
							$values[] = "'" . str_replace("'","\'",str_replace($search, $replace, $row[$j])) . "'";
						}
					} else {
						$values[]     = "''";
					}
				}

				$max=SizeOf($values);

				for ($i=0;$i<$max;$i++) {
					if ($i!=0) $schema_insert .= ", ";
					$schema_insert .= $values[$i] ;
				}

				$schema_insert .= ");\n";
				unset($values);
			}
		}
		mysql_free_result($result);

		return $schema_insert;
	}

	/* MySQL connection, selection of the database and encoding setting */
	@$connection = mysql_connect(CFG_MYSQL_HOST,CFG_MYSQL_USER,CFG_MYSQL_PASS);
	if (!$connection) {
		echo "Error: Can't connect to MySQL server.";
		die();
	}

	mysql_select_db(CFG_MYSQL_DB);
	mysql_query("SET NAMES 'utf8'");
	mysql_query("SET CHARACTER SET 'utf8'");

	/* Listing of tables and getting everythng we need from MySQL */
	$tables = mysql_list_tables(CFG_MYSQL_DB);
	$num_tables = mysql_numrows($tables);

	$dump_buffer = '';
	for ($i=0; $i<$num_tables; $i++) {
		$table = mysql_tablename($tables, $i);

		$dump_buffer .= "\n# Table name: `$table`\n\n";
		$dump_buffer .= getTableDef($table);

		$dump_buffer .= "\n# Data of table `$table`:\n\n";
		$dump_buffer .= getTableContent($table);
	}
	mysql_close();

	/* We've got dump, so let's mail it! */
	mail(CFG_MAIL_ADDRESS, CFG_MAIL_SUBJECT, $dump_buffer);

} else {
	sleep(CFG_SLEEPTIME);
}
?>