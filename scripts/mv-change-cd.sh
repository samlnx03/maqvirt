#!/bin/bash

# mv-change-cd.sh  cambia el cdrom

NAME=$1
NEWCD=$2
F="/home/sperez/maqvirt/machines/$NAME.sh"
#NAME="/home/sperez/maqvirt/machines/prueba.sh"
#NEWCD="/var/lib/libvir/images/noexiste.iso"
if [ -z "$NAME" ]; then
	echo "-1 No se paso nombre de VM"
	exit
fi
if [ -z "$NEWCD" ]; then 
	echo "-1 No se paso el nuevo cd"
	exit
fi
if [ ! -f "$NAME" ]; then
	echo "-1 No existe la mv"
	exit
fi
if [ ! -f "$NEWCD" ]; then
	echo "-1 No existe el cd"
	exit
fi

# se uso : en lugar de / porque la cadena NEWCD los contiene
sed  -i "s:cdrom.*:cdrom ${NEWCD} \\\:g" $NAME
echo "CD cambiado"

