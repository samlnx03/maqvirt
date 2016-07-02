#!/bin/bash

#todas los hdds volumenes logicos creados

HDDS=$(grep "^[a-zA-Z0-9]*$" /home/sperez/maqvirt/lista-hdds.txt)
if [ -z "$HDDS" ]; then
	echo "-1 No existen ningun disco hdd"
else
	for h in $HDDS; do echo $h; done
fi

