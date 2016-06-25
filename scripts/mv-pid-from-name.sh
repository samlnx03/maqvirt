#!/bin/bash

#pid de una maquinas virtuales en ejecusion dado el nombre
# -1 si no esta corriendo
NAME="PROXYNETLAB"
NAME=$1
if [ -z "$1" ]; then
	PS="-1"
else
	PS=$(ps ax | grep qemu-system-x86_64 | grep "$NAME " | awk '{print $1}')
	if [ -z "$PS" ]; then
		PS="-1 No se encontro $NAME"
	fi
fi
echo $PS
