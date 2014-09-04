#!/bin/bash

function tempSkiVodnik {
	temp=`curl -s http://www.skivodnik.cz/index.php?page=teplomer | grep "<p id = \"aktuálniTeplota\">"`
	temp=${temp#*>}
	temp=${temp%°*}
	temp=`echo ${temp} | tr -d ' '`
	echo $temp
}

function tempRestauraceVodnik {
	echo `curl -s http://db1.isenzor.cz/export/B827EBC108EE/text/`
}

if [ -n "$1" ]; then
	temp${1}
else
	echo "skivodnik.cz:         $(tempSkiVodnik) °C"
	echo "restaurace-vodnik.cz: $(tempRestauraceVodnik) °C"
fi
