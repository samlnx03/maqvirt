#!/bin/bash
# clonar-hdd.sh   clona un disco duro
#
# esto puede tardar demasiado dependiendo del tamaño del disco
# dejarlo en el background informando el ps-id y checar desde el cliente web periodicamente
#
# my-app &
# echo $!
#
NAME=$1
NEW=$2
if [ -z "$NAME" ]; then
	echo "-1 no se paso argumento 1 nombre de disco origen"
	exit
fi
if [ -z "$NEW" ]; then
	echo "-1 no se paso argumento 2 nombre destino"
	exit
fi

exec 2>/dev/null

#checar que no es disco libvirt o manual
CLONABLE=$(grep "^$NAME$"  /home/sperez/maqvirt/lista-hdds.txt)
if [ -z "$CLONABLE" ]; then
	echo "-1 no permitido clonar!"
	exit
fi


SIZE=$(lvs fie_vg/$NAME | tail -n+2 | awk '{print $4}') 
if [ -z "$SIZE" ]; then
	# eliminar de la lista y notificar
	sed -i "/^$NAME$/d" /home/sperez/maqvirt/lista-hdds.txt
	echo "-1 no existe disco $NAME, (borrado manual tal vez) cheque que".
		" ninguna maquina lo incluya o corrija";
	exit
fi

#verif que no este el destino
INFO=$(lvs fie_vg/$NEW | tail -n+2 | awk '{print $4,$3}')
if [ -n "$INFO" ]; then
        echo "-1 ya existe el disco o volumen logico $NEW, exiting"
        exit
fi

S=$(lvcreate -L $SIZE -n $NEW fie_vg)

# verifica que si se creo
INFO=$(lvs fie_vg/$NEW | tail -n+2 | awk '{print $4,$3}') 
if [ -z "$INFO" ]; then
	echo "-1 No se pudro crear nuevo disco"
	exit
fi

# habilitar su posible borrado posterior
echo "$NEW" >> /home/sperez/maqvirt/lista-hdds.txt

#S1=$(dd if=/dev/fie_vg/$NAME  of=/dev/fie_vg/$NEW bs=64M)
dd if=/dev/fie_vg/$NAME  of=/dev/fie_vg/$NEW bs=64M &> /tmp/kk.txt &
DDPID=$!
echo "$S"
echo "clonando..."
echo "$DDPID"

