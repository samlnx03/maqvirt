#!/bin/bash
# info-hdd.sh   verifica que exista un volumen logico
NAME=$1
if [ -z "$NAME" ]; then
	echo "-1 no se paso argumento 1 nombre de disco"
	exit
fi

exec 2>/dev/null
INFO=$(lvs fie_vg/$NAME | tail -n+2 | awk '{print $4,$3}') 
if [ -z "$INFO" ]; then
	echo "-1 no se encontro el disco $NAME, exiting"
	exit
fi
echo "$INFO"
