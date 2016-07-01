#Server socket para maquinas virtuales

Se hace una liga hard (simbolica no funciona) del archivo maqvirt-socket-php.conf
esta liga va en /etc/init/ para que haga el respawn en caso de detenerse el servicio
si se modifica el archivo, hay que actualizar con:
$initctl reload-configuration

el servicio se arranca y detiene con:
$sudo service maqvirt-socket-php start
$sudo service maqvirt-socket-php stop
$sudo service maqvirt-socket-php status


