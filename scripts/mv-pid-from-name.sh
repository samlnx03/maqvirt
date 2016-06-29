#!/bin/bash

#pid de una maquinas virtuales en ejecusion dado el nombre
# -1 si no esta corriendo

#esta informacion no debe ir nunca a la aplicacion web
#se usa solo por otras acciones solicitadas para la maquina virtual
# p.e. enviar señales kill

NAME=$1
if [ -z "$1" ]; then
	PS="-1 No se paso nombre de VM"
else
	PS=$(ps ax | grep qemu-system-x86_64 | grep "$NAME " | awk '{print $1}')
	if [ -z "$PS" ]; then
		PS="-1 No se encontro $NAME"
	fi
fi
echo $PS
