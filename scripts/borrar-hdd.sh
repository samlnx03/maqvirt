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
BORRABLE=$(grep "^$NAME$"  /home/sperez/maqvirt/lista-hdds.txt)
if [ -z "$BORRABLE" ]; then
	echo "-1 no es borrable!"
	exit
else
	S=$(lvremove -f fie_vg/$NAME)
	# eliminar de la lista y notificar
	sed -i "/^$NAME$/d" /home/sperez/maqvirt/lista-hdds.txt
	echo "$S"
fi

