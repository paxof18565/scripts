#!/bin/bash

function temp1 {
	temp=`curl -s http://www.skivodnik.cz/index.php?page=teplomer | grep "<p id = \"aktuálniTeplota\">"`
	temp=${temp#*>}
	temp=${temp%°*}
	temp=`echo ${temp} | tr -d ' '`
	echo $temp
}

function temp2 {
	temp=`curl -s http://db1.isenzor.cz/export/B827EBC108EE/text/`
	echo $temp
}

if [ -n "$1" ]; then
	temp${1}
else
	echo "skivodnik.cz:         $(temp1) °C"
	echo "restaurace-vodnik.cz: $(temp2) °C"
fi
