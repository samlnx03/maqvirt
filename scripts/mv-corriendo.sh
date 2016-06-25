#!/bin/bash

#maquinas virtuales en ejecusion
TFILE="$(mktemp)"
ps ax | grep qemu-system-x86_64 | grep -i -e 'name [a-z0-9_-]* ' -o | awk '{print $2}' > $TFILE

# quitar las de la lista negra
for f in $(/home/sperez/pamvifie/mv-lista-negra.sh); do
	sed -i "/$f/d" $TFILE
done
cat $TFILE
rm $TFILE

