#!/bin/bash

#maquinas virtuales definidas por virsh
VIRSH=$(/usr/bin/virsh list --name | grep  -v "^$")

# mas la de la lista negra
LISTA=$(cat /home/sperez/maqvirt/lista-negra-vm.txt)

echo "$VIRSH"
echo "$LISTA"


