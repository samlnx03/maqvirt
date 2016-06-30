#!/bin/bash

# mv-change-cd.sh  cambia el cdrom

NAME=$1
NEWCD=$2
if [ -z "$NAME" ]; then
	echo "-1 No se paso nombre de VM"
	exit
fi
if [ -z "$NEWCD" ]; then 
	echo "-1 No se paso el nuevo cd"
	exit
fi

sed -i 's/cdrom.*/cdrom $NEWCD \\/g' prueba.sh
echo "CD cambiado, restart requerido"

