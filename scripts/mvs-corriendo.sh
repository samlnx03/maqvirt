#!/bin/bash

#maquinas virtuales en ejecusion
TFILE="$(mktemp)"
ps ax | grep qemu-system-x86_64 | grep -i -e 'name [a-z0-9_-]* ' -o | awk '{print $2}' > $TFILE

# quitar las de la lista negra (incluye virsh y ../lista-negra-vm.txt)
for f in $(/home/sperez/maqvirt/scripts/mv-lista-negra.sh); do
	sed -i "/$f/d" $TFILE
done

if [ -s $TFILE ]; then
	cat $TFILE | while read line; do echo "$line"; done
else
	echo "-1 Ninguna MV corriendo"
fi
rm $TFILE

