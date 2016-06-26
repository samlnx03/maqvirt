#!/bin/bash
#		crear-hdd.sh
NAME=$1
SIZE=$2
if [ -z "$NAME" ]; then
	echo "-1 no se paso argumento 1 nombre"
	exit
fi
if [ -z "$SIZE" ]; then
	echo "-1 no se paso argumento 2 tamaño"
	exit
fi

exec 2>/dev/null
INFO=$(lvs fie_vg/$NAME | tail -n+2 | awk '{print $4,$3}') 
if [ -n "$INFO" ]; then
	echo "-1 ya existe ese disco o volumen logico $NAME, exiting"
	exit
fi

S=$(lvcreate -L $SIZE -n $NAME fie_vg)
echo "$S"
