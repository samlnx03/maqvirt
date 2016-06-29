#!/bin/bash

# mv-start.sh  inicia (boot) una VM

#demoniza la maq. virtual
NAME=$1

start-stop-daemon --start --quiet --background --exec $NAME
