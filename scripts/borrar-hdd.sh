#!/bin/bash
NAME=$1
if [ -z "$NAME" ]; then
	echo "-1 no se paso argumento 1 nombre"
	exit
fi
exec 2>/dev/null
INFO=$(lvs fie_vg/$NAME | tail -n+2 | awk '{print $4,$3}') 
if [ -z "$INFO" ]; then
	echo "-1 no se encontro el disco $NAME, exiting"
	exit
fi

S=$(lvremove -f fie_vg/$NAME)
echo "$S"
