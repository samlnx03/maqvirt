#!/usr/bin/php
<?php
/* changelog
	v0.1.20160629 	primer version de prueba
	v0.1.1.21060630 ajuste en el log
*/
define("DEBUG", 0); // 0=nothing 1=minimum 2=all

require("/home/sperez/maqvirt/maqvirt.secret");

class MySocketServer
{
    protected $socket;
    protected $clients = [];
    protected $changed;
    protected $finish=false;  // bandera para terminar
   
    function __construct($host, $port)
    {
        set_time_limit(0);
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

        //bind socket to specified host
        socket_bind($socket, 0, $port);
        //listen to port
        socket_listen($socket);
        $this->socket = $socket;
	$this->log("START\n");
    }
   
    function __destruct()
    {
        foreach($this->clients as $client) {
            socket_close($client);
        }
        socket_close($this->socket);
	$this->log("FINISH\n");
    }
   
    function run()
    {
        while(true) {
		if(DEBUG==2) $this->log("run iniciando el while\n");
            $this->waitForChange();	// espera cambio en algun socket, 
					// $this->socket, client0, client1
            $this->checkNewClients();	// take care of new clients
            $this->checkMessageRecieved(); //recibe mensajes posibles
	    if($this->finish)
		break;
            $this->checkDisconnect();
		if(DEBUG==2) $this->log("run terminando el while\n");
        }
	return "STOPSOCKET";
    }
   
   
    function waitForChange()
    {
	if(DEBUG==2){
		$this->log("\twaitForChange entrando\n");
	}
        //reset changed, agrega el socket 
        $this->changed = array_merge([$this->socket], $this->clients);
        
	//variable call time pass by reference req of socket_select
        $null = null;

        //this next part is blocking so that we dont run away with cpu
        socket_select($this->changed, $null, $null, null);
	// socket_select() acepta matrices de sockets y las espera para cambiar el estado
	//  ( array &$read , array &$write , array &$except , int $tv_sec [, int $tv_usec = 0 ] )
	//  Si tv_sec es NULL (sin tiempo límite), socket_select() puede bloquear indefinidamente. 
	// la matriz changed es cambiada al salir para reflejar que socket cambio.
    }
   
    function checkNewClients()
    {
	if(DEBUG==2) $this->log("\n\tcheckNewClients entrando\n");
        if (!in_array($this->socket, $this->changed)) {
	    if(DEBUG==2) $this->log("\tcheckNewClients no new clients, returning\n");
            return; //no new clients
        }
        $socket_new = socket_accept($this->socket); //accept new socket
        $this->clients[] = $socket_new;		// agregalo a los clientes actuales
        socket_getpeername($socket_new, $ip);
        unset($this->changed[0]);	// quita this->socket del arreglo
	$this->log("New Client from $ip $socket_new\n");
    }
   
   
    function checkMessageRecieved()
    {
        foreach ($this->changed as $key => $socket) { // solo donde hay cambios
	    if(DEBUG==2) {$this->log("\tcheckMessageReceived in foreach: $key $socket\n");} 
            $buffer = null;
            while(socket_recv($socket, $buffer, 1024, 0) >= 1) {
		// recibe hasta 1024 bytes de información en buffer desde socket, 
	    	if(DEBUG==2) {$this->log("\tinfo recibida en socket $socket en el while socket_recv\t");} 
		$this->processMessage($socket, trim($buffer));
		if(DEBUG==2) $this->log("\tcheckMessageReceived message proccesed\n");
                unset($this->changed[$key]);
                break;
            }
	    if(DEBUG==2) {$this->log("\n\tcheckMessageReceived: while socket_recv done\n");} 
	    if($this->finish) break;
        }
    }
   

    function checkDisconnect()
    {
        foreach ($this->changed as $changed_socket) {
            $buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
            if ($buf !== false) { // check disconnected client
                continue;
            }
            // remove client for $clients array
            $found_socket = array_search($changed_socket, $this->clients);
            socket_getpeername($changed_socket, $ip);
            $response = 'client ' . $ip . " $changed_socket se ha desconectado\n";
            unset($this->clients[$found_socket]);
	    $this->log($response);
        }
    }

    function closeAll(){
	foreach($this->clients as $socket)
            socket_close($socket);
    }

    function sendMessage($msg)
    {
        foreach($this->clients as $client)
        {
            @socket_write($client,$msg,strlen($msg));
        }
        return true;
    }
    function log($msg){
	$dt=new DateTime("now");
        $msg=$dt->format("Y-m-d H:i")." $msg";
	// log en el archivo de log
	if(DEBUG>0) echo $msg;
	$LOGFILE=fopen(LOGFILE,"a");
	fwrite($LOGFILE, $msg);
	fclose($LOGFILE);
    }

    function createVM($info)
    {
	$vncport=50+$info['vnc'];
	$mac=$vncport;
	$BRIDGE="OvsBrNETLAB";
	$add=3; // adress pci

	$crearnics="";
	$quitarnics="";
	for($i=1; $i<=$info["nic"]; $i++){
		$j=$i-1;
		$intf="mv".$info["vnc"]."eth".$j;
		$crearnics=$crearnics.
			"ip tuntap add mode tap $intf\n".
			"ip link set $intf up\n".
			"ovs-vsctl add-port $BRIDGE $intf tag=1\n\n";
		$quitarnics=$quitarnics.
			"ovs-vsctl del-port $BRIDGE $intf\n".
			"ip link set $intf down\n".
			"ip tuntap del mode tap $intf\n\n";
	}


	$vm="#!/bin/bash\n\n".
		$crearnics."\n\n".
		"qemu-system-x86_64 \\\n".
		"-enable-kvm \\\n".
		"-m {$info['mem']} \\\n".
		"-k es \\\n".
		"-name {$info['name']} \\\n".
		"-M pc-1.0 \\\n".
		"-smp 1,sockets=1,cores=1,threads=1 \\\n";
	if(isset($info['cdrom'])){
		$vm=$vm.
		"-cdrom ".DIRISOS."/{$info['cdrom']} \\\n";
	}
	if(isset($info['hd0'])){
		$vm=$vm.
		"-drive file=/dev/fie_vg/{$info['hd0']},if=none,id=drive-virtio-disk0,format=raw \\\n".
		"-device virtio-blk-pci,bus=pci.0,addr=0x$add,drive=drive-virtio-disk0,id=virtio-disk0 \\\n";
		$add++;
	}
	$vm=$vm."-vnc 127.0.0.1:$vncport \\\n";
//$vmm->write("cmd:createVM+$name:$nameval+$nic:$numnics+$mem:$memSize+$cdrom:$cdromdata+$hd0:$h	
	if(isset($info['nic'])){
		for($i=1; $i<=$info["nic"]; $i++){
			$intf="mv".$info["vnc"]."eth".$i;
			$maci=sprintf("52:54:00:43:%'02X:%'02X",$i, $mac);
			$vm=$vm.
		"-netdev tap,ifname=$intf,script=no,downscript=no,id=hostnet$i \\\n".
		"-device virtio-net-pci,netdev=hostnet$i,mac=$maci,bus=pci.0,addr=0x$add \\\n";
		$add++;
		}
	}
	$vm=$vm.
		"-rtc base=utc \\\n".
		"-usb \\\n".
		"-vga cirrus \\\n".
		"-device virtio-balloon-pci,id=balloon0,bus=pci.0,addr=0x$add \\\n";
	$add++;
	$vm=$vm.
		"-nodefconfig \\\n".
		"-nodefaults \\\n".
		"-boot order=c,menu=on\n\n".

		"# eliminacion de la interfaz de red\n".
		$quitarnics;


		$BASHSCRIPT=DIRMV."/".$info["name"].".sh";
		$F=fopen($BASHSCRIPT, 'x');
		if($F){
			fwrite($F, $vm);
			fclose($F);
			chmod($BASHSCRIPT,0755);
		}
		else {
			$BASHSCRIPT="-1 Error al crear script de maq. virtual ".$info["name"].".sh";
			$this->log($BASHSCRIPT);
		}
		return $BASHSCRIPT;
    }

    function processMessage($socket, $msg)
    {
        if (!$msg = trim($msg)) {
            return;	// solo espacios
        }
	$datos=explode("+", $msg);
	foreach($datos as $elemento){
		list($name, $val)=explode(":",$elemento);
		$info[$name]=$val;
	}

	$cmd=$info["cmd"];
	switch($cmd) {
		case "stopService":
			$this->log("$socket DETIENE el servidor\n");
			$this->closeAll(); // cierra todos los sockets y termina
			$this->finish=true;
			break;
		case "disconnect":
			$this->log("$socket se desconecto amablemente\n");
            		socket_close($socket);
			break;
		case "listaIsos": // checado!
			$talkback=shell_exec(DIRBS."/lista-isos.sh");
	                $talkback=substr($talkback,0,-1); // quitar el cr
        	        socket_write($socket, $talkback, strlen($talkback));
			$this->log("$socket got listaIsos\n");
			break;
		case "infoHdd":	// checado!
			$talkback=shell_exec(DIRBS."/info-hdd.sh ".$info["name"]);
	                $talkback=substr($talkback,0,-1); // quitar el cr
        	        socket_write($socket, $talkback, strlen($talkback));
			$this->log("$socket got infoHdd of {$info['name']}\n");
			break;
		case "crearHdd":  // checado!
			$talkback=shell_exec(DIRBS."/crear-hdd.sh ".$info["name"]." ".$info["size"]);
	                $talkback=substr($talkback,0,-1); // quitar el cr
        	        socket_write($socket, $talkback, strlen($talkback));
			$this->log("$socket do crearHdd of {$info['name']} {$info['size']}\n");
			break;
		case "borrarHdd":	// checado!
			$talkback=shell_exec(DIRBS."/borrar-hdd.sh ".$info["name"]);
	                $talkback=substr($talkback,0,-1); // quitar el cr
        	        socket_write($socket, $talkback, strlen($talkback));
			$this->log("$socket do borrarHdd of {$info['name']}\n");
			break;
		case "clonarHdd":	// checado!
			$talkback=shell_exec(DIRBS."/clonar-hdd.sh ".
				$info["name"]." ". $info["new"]);
	                $talkback=substr($talkback,0,-1); // quitar el cr
        	        socket_write($socket, $talkback, strlen($talkback));
			$this->log("$socket do clonarHdd of {$info['name']} as {$info['new']}\n");
			break;
		case "createVM":  // checado
			$talkback=$this->createVM($info)." creada";
	    		$this->log($talkback);
        	        socket_write($socket, $talkback, strlen($talkback));
			//echo "$talkback\n";
			$this->log("$socket do createVM {$info['name']}\n");
			break;
		case "removeVM":  // checado!
			$BASHSCRIPT=DIRMV."/".$info["name"].".sh";
			$this->log("$socket Borrando script de MV ".$BASHSCRIPT."\n");
			if(file_exists($BASHSCRIPT)){
				if(unlink($BASHSCRIPT)) $BASHSCRIPT="MV Eliminada ".$BASHSCRIPT;
				else $BASHSCRIPT="-1 No se pudo eliminar ".$BASHSCRIPT;
			}
			else {
				$BASHSCRIPT="-1   !!! No existe MV ".$BASHSCRIPT;
			}
        	        socket_write($socket, $BASHSCRIPT, strlen($BASHSCRIPT));
			$this->log("$socket $BASHSCRIPT\n");
			break;
		case "listaVMs": // checado!
			$talkback=shell_exec(DIRBS."/lista-vms.sh");
	                $talkback=substr($talkback,0,-1); // quitar el cr
        	        socket_write($socket, $talkback, strlen($talkback));
			$this->log("$socket got listaVMs\n");
			break;
		case "listaNegraVMs": // checado!
			$talkback=shell_exec(DIRBS."/mv-lista-negra.sh");
	                $talkback=substr($talkback,0,-1); // quitar el cr
        	        socket_write($socket, $talkback, strlen($talkback));
			$this->log("$socket got listaNegraVMs\n");
			break;
		case "vmsRunning": // checado!  
			$talkback=shell_exec(DIRBS."/mvs-corriendo.sh");
	                $talkback=substr($talkback,0,-1); // quitar el cr
        	        socket_write($socket, $talkback, strlen($talkback));
			$this->log("$socket got vmsRunning\n");
			break;
		case "vmIsRunning": // checado!
			$talkback=shell_exec(DIRBS."/mv-isRunning.sh {$info['name']}");
	                //$talkback=substr($talkback,0,-1); // quitar el cr
        	        socket_write($socket, $talkback, strlen($talkback));
			$this->log("$socket knew if {$info['name']} vmIsRunning\n");
			break;
		case "vmStart": // 
			$mv=DIRMV."/{$info['name']}.sh"; 
			exec(DIRBS."/mv-start.sh $mv"); // daemonize vm
			$talkback="se intento dejar la MV en el background. Cheque con isRunning en 1 segundo aprox\n";
        	        socket_write($socket, $talkback, strlen($talkback));
			$this->log("$socket do vmStart of {$info['name']}\n");
			break;
		case "vmChangeCD":
			//$newcdrom="/var/lib/libvirt/images/{$info['newcdrom']}";
			$newcdrom=DIRISOS."/{$info['newcdrom']}";
			$mv=DIRMV."/{$info['name']}.sh"; 
			$talkback=exec(DIRBS."/mv-change-cd.sh $mv $newcdrom");
        	        socket_write($socket, $talkback, strlen($talkback));
			$this->log("$socket change CD of {$info['name']} to {$info['newcdrom']}\n");
			break;
		case "vmKill": //
			$signal="SIGTERM";
			if(isset($info["signal"])) 
				$signal=$info["signal"];
			//$mv=DIRMV."/{$info['name']}.sh"; 
			$mv=$info['name']; 
			$talkback=shell_exec(DIRBS."/mv-kill.sh $mv $signal");
	                //$talkback=substr($talkback,0,-1); // quitar el cr
        	        socket_write($socket, $talkback, strlen($talkback));
			$this->log("$socket change do vmKill to {$info['name']} with signal {$info['signal']}\n");
			//echo "$talkback\n";
			break;
	}
    }
}
// define los parametros del constructor el archivo requerido al inicio
$server=new MySocketServer(IP, PORT);
$code=$server->run();
echo $code;
return $code;
?>

