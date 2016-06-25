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
SIZE=$(lvs fie_vg/$NAME | tail -n+2 | awk '{print $4}') 
if [ -z "$SIZE" ]; then
	echo "-1 no se encontro el disco $NAME, exiting"
	exit
fi
S=$(lvcreate -L $SIZE -n $NEW fie_vg)
#S1=$(dd if=/dev/fie_vg/$NAME  of=/dev/fie_vg/$NEW bs=64M)
dd if=/dev/fie_vg/$NAME  of=/dev/fie_vg/$NEW bs=64M &> /tmp/kk.txt &
DDPID=$!
echo "$S"
echo "clonando..."
echo "$DDPID"

