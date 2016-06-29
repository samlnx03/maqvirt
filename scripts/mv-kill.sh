#!/bin/bash

# mv-kill.sh  kill al process de la mv

NAME=$1
SIGNAL=$2
if [ -z "$SIGNAL" ]; then 
	echo "-1 No se especifico SIGNAL"
	exit
fi
if [ -z "$NAME" ]; then
	echo "-1 No se paso nombre de VM"
	exit
fi

PS=$(ps ax | grep qemu-system-x86_64 | grep "$NAME " | awk '{print $1}')
if [ -z "$PS" ]; then
	echo "-1 NO esta corriendo"
	exit
fi
TFILE="$(mktemp)"
	
kill -s $SIGNAL $PS 2>$TFILE
wait $PS

cat $TFILE
rm $TFILE

