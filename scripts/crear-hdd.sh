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
# checa por si existe previamente
INFO=$(lvs fie_vg/$NAME | tail -n+2 | awk '{print $4,$3}') 
if [ -n "$INFO" ]; then
	echo "-1 ya existe ese disco o volumen logico $NAME, exiting"
	exit
fi

#no, exite. Crearlo
S=$(lvcreate -L $SIZE -n $NAME fie_vg)

# verifica que si se creo
INFO=$(lvs fie_vg/$NAME | tail -n+2 | awk '{print $4,$3}') 
if [ -n "$INFO" ]; then
	# notifica y habilitar su posible borrado
	echo "$S"
	echo "$NAME" >> /home/sperez/maqvirt/lista-hdds.txt
	exit
fi
echo "-1 No se pudo crear hdd!"
exit

