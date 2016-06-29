#!/bin/bash

# mv-isRunning.sh no dice si una maquina virtual dada esta corriendo
# regresa SI o NO

NAME=$1
if [ -z "$1" ]; then
	PS="-1 No se paso nombre de VM"
else
	PS=$(ps ax | grep qemu-system-x86_64 | grep "$NAME " | awk '{print $1}')
	if [ -z "$PS" ]; then
		PS="NO"
	else
		PS="SI"
	fi
fi
echo $PS
