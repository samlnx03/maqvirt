#Server socket para maquinas virtuales

Se hace una liga simbolica del archivo maqvirt-socket-php.conf
esta liga va en /etc/init/ para que haga el respawn en caso de detenerse

el servicio se arranca y detiene con:
sudo service maqvirt-socket-php start
sudo service maqvirt-socket-php stop


