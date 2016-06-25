#!/bin/bash

#todas las imagenes iso para instalar y otras cosas

virsh vol-list default | grep iso | awk '{print $1}'
