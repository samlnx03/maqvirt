#!/bin/bash

#maquinas virtuales definidas por virsh

/usr/bin/virsh list --name | grep  -v "^$"

