#!/bin/bash

#todas los scripts de maquinas virtuales definidas

LISTA=$(ls /home/sperez/maqvirt/machines/)
if [ -z "$LISTA" ]; then
	echo "-1 No existen ninguna VM"
else
	for m in $LISTA; do echo $m; done
fi

