#!/bin/bash

#is-mv-inListaNegra.sh

NAME=$1
if [ -z "$NAME" ];then
	echo "-1 No se paso el nombre"
	exit
fi

TF=$(mktemp)
#maquinas virtuales definidas por virsh
/usr/bin/virsh list --name | grep  -v "^$" > $TF

# mas la de la lista negra
cat /home/sperez/maqvirt/lista-negra-vm.txt >> $TF

NUM=$(grep "^$NAME$" $TF | wc -l)
rm $TF
echo $NUM

