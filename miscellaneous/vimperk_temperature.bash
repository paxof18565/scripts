#!/bin/bash

temp[1]=`curl -s http://www.skivodnik.cz/index.php?page=teplomer | grep "<p id = \"aktuálniTeplota\">"`
temp[1]=${temp[1]#*>}
temp[1]=${temp[1]%°*}
temp[1]=`echo ${temp[1]} | tr -d ' '`

temp[2]=`curl -s http://db1.isenzor.cz/export/B827EBC108EE/text/`

if [ -n "$1" ]; then
	echo ${temp[$1]}
else
	echo "skivodnik.cz:         ${temp[1]} °C"
	echo "restaurace-vodnik.cz: ${temp[2]} °C"
fi
