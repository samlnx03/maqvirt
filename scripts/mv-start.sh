#!/bin/bash

# mv-start.sh  inicia (boot) una VM

NAME=$1
if [ -z "$NAME" ]; then
	#echo "-1 No se paso nombre de VM"  #no tiene sentido ha sido enviado a /dev/null
	exit
fi

nohup $NAME &>/dev/null &
#screen -d -m -S my_php_session $NAME 
