#!/bin/bash

# mv-kill.sh  kill al process de la mv

NAME=$1
SIGNAL=$2
if [ -z "$SIGNAL" ]; then 
	echo "-1 No se especifico SIGNAL"
	exit
fi
if [ -z "$NAME" ]; then
	echo "-1 No se paso nombre de VM"
	exit
fi

NUM=$(/home/sperez/maqvirt/scripts/is-mv-inListaNegra.sh $NAME)
if [[ "$NUM" == [1-9]* ]]; then
	echo "-1 Esta en la lista negra"
	exit
fi

#echo "buscando pid de $NAME"

PS=$(ps ax | grep qemu-system-x86_64 | grep "$NAME " | awk '{print $1}')
if [ -z "$PS" ]; then
	echo "-1 NO esta corriendo"
	exit
fi
#TFILE="$(mktemp)"
	
#kill -s $SIGNAL $PS 2>$TFILE
kill -s $SIGNAL $PS 2>/dev/null
#wait $PS  #wait: pid 17655 is not a child of this shell
# verificar con isRunning

#cat $TFILE
#rm $TFILE
echo "Se envio signal kill -s $SIGNAL"
