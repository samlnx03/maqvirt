#!/usr/bin/php
<?php
define("DEBUG", 1); // 0=nothing 1=minimum 2=all
//define("LOGFILE", /home/sperez/virtInterface/log/phpserver.log);

class MySocketServer
{
    protected $socket;
    protected $clients = [];
    protected $changed;
   
    function __construct($host = 'localhost', $port = 5999)
    {
        set_time_limit(0);
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

        //bind socket to specified host
        socket_bind($socket, 0, $port);
        //listen to port
        socket_listen($socket);
        $this->socket = $socket;
	if(DEBUG>0) $this->log("START ". $socket);
    }
   
    function __destruct()
    {
        foreach($this->clients as $client) {
            socket_close($client);
        }
        socket_close($this->socket);
    }
   
    function run()
    {
        while(true) {
		if(DEBUG==2) echo "run iniciando el while\n";
            $this->waitForChange();	// espera cambio en algun socket, 
					// $this->socket, client0, client1
            $this->checkNewClients();	// take care of new clients
            $this->checkMessageRecieved(); //recibe mensajes posibles
            $this->checkDisconnect();
		if(DEBUG==2) echo "run terminando el while\n\n";
        }
    }
   
   
    function waitForChange()
    {
	if(DEBUG==2){
		echo "\twaitForChange entrando\n";
	}
        //reset changed, agrega el socket 
        $this->changed = array_merge([$this->socket], $this->clients);
	if(DEBUG==2) { echo "\tarray changed= "; print_r($this->changed);}
        
	//variable call time pass by reference req of socket_select
        $null = null;

        //this next part is blocking so that we dont run away with cpu
        socket_select($this->changed, $null, $null, null);
	// socket_select() acepta matrices de sockets y las espera para cambiar el estado
	//  ( array &$read , array &$write , array &$except , int $tv_sec [, int $tv_usec = 0 ] )
	//  Si tv_sec es NULL (sin tiempo límite), socket_select() puede bloquear indefinidamente. 
	// la matriz changed es cambiada al salir para reflejar que socket cambio.
	if(DEBUG==2) echo "\twaitForChange terminando socket_select\n\n";
    }
   
    function checkNewClients()
    {
	if(DEBUG==2) echo "\n\tcheckNewClients entrando\n";
        if (!in_array($this->socket, $this->changed)) {
	    if(DEBUG==2) echo "\tcheckNewClients no new clients, returning\n";
            return; //no new clients
        }
        $socket_new = socket_accept($this->socket); //accept new socket
        $this->clients[] = $socket_new;		// agregalo a los clientes actuales
        unset($this->changed[0]);	// quita this->socket del arreglo
	if(DEBUG>0) $this->log("New Client, socket_new= $socket_new agregado");
	/*
	if(DEBUG) {echo "\tcheckNewClients array clients="; print_r($this->clients);
		echo "\tcheckNewClients array changed="; print_r($this->changed); }
	if(DEBUG) { echo "\tcheckNewClients, socket_new= ".$socket_new."\n\tdoing socket_read\n";}
        $first_line = socket_read($socket_new, 1024);
        $this->sendMessage('a new client has connected' . PHP_EOL);
        $this->sendMessage('the new client says ' . trim($first_line) . PHP_EOL);
	if(DEBUG) echo "\tcheckNewClients nuevo cliente se ha conectado y leido done!. message enviado\n";
	*/
    }
   
   
    function checkMessageRecieved()
    {
        foreach ($this->changed as $key => $socket) { // solo donde hay cambios
	    if(DEBUG==2) {echo "\tcheckMessageReceived in foreach: $key $socket\n";} 
            $buffer = null;
            while(socket_recv($socket, $buffer, 1024, 0) >= 1) {
		// recibe hasta 1024 bytes de información en buffer desde socket, 
	    	if(DEBUG==2) {echo "\tinfo recibida en socket $socket en el while socket_recv\t";} 
                //$this->sendMessage(">>>".trim($buffer) . PHP_EOL);
		$this->processMessage($socket, trim($buffer));
		if(DEBUG==2) echo "\tcheckMessageReceived message sent\n";
                unset($this->changed[$key]);
                break;
            }
	    if(DEBUG==2) {echo "\n\tcheckMessageReceived: while socket_recv done\n";} 
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
            unset($this->clients[$found_socket]);
            $response = 'client ' . $ip . " $changed_socket has disconnected";
	    $this->log($response);
            //$this->sendMessage($response);
        }
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
        echo $dt->format("Y-m-d H:i")." $msg\n";
    }

    function processMessage($socket, $msg)
    {
        if (!$msg = trim($msg)) {
            return;	// solo espacios
        }
	$datos=explode("+", $msg);
	// luego datos$[0] sera la autenticacion

	$cmd=$datos[0];
	switch($cmd) {
		case "disconnect":
            		socket_close($socket);
			$this->log("$sock se desconecto amablemente");
			break;
		case "listaIsos":
			$talkback=shell_exec("/home/sperez/pamvifie/lista-isos.sh");
	                $talkback=substr($talkback,0,-1); // quitar el cr
        	        socket_write($socket, $talkback, strlen($talkback));
			echo "$talkback\n";
			break;
		case "infoHdd":
			list($name, $nameval)=explode(":",$datos[1]);
			//echo "datos1: ".$datos[1]." $name: $val\n";
			$talkback=shell_exec("/home/sperez/pamvifie/info-hdd.sh $nameval");
	                $talkback=substr($talkback,0,-1); // quitar el cr
        	        socket_write($socket, $talkback, strlen($talkback));
			echo "$talkback\n";
			break;
		case "createVM":
			$talkback=$this->createVM($datos);
	                //$talkback=substr($talkback,0,-1); // quitar el cr
        	        socket_write($socket, $talkback, strlen($talkback));
			//echo "$talkback\n";
			break;
	}
    }
    function createVM($datos)
    {
	//echo "datos: ".print_r($datos);
	for($i=1; $i<sizeof($datos);$i++){
		list($name, $val)=explode(":",$datos[$i]);
		$pares[$name]=$val;
	}
	$vncport=5950+$pares['vnc'];
	$mac=50+$pares["vnc"];
	$vm="qemu-system-x86_64 \\\n".
		"-enable-kvm \\\n".
		"-m {$pares['mem']} \\\n".
		"-k es \\\n".
		"-name {$pares['name']} \\\n".
		"-M pc-1.0 \\\n".
		"-smp 1,sockets=1,cores=1,threads=1 \\\n".
		"-cdrom /var/lib/libvirt/images/{$pares['cdrom']} \\\n".
		"-drive file=/dev/fie_vg/{$pares['hd0']},if=none,id=drive-virtio-disk0,format=raw \\\n".
		"-device virtio-blk-pci,bus=pci.0,addr=0x4,drive=drive-virtio-disk0,id=virtio-disk0 \\\n".
		"-vnc 127.0.0.1:$vncport \\\n".
		"-netdev tap,ifname=proxy148,script=no,downscript=no,id=hostnet0 \\\n".
		"-device virtio-net-pci,netdev=hostnet0,mac=52:54:00:43:6d:$mac,bus=pci.0,addr=0x3 \\\n".
		"-rtc base=utc \\\n".
		"-netdev tap,ifname=proxy192,script=no,downscript=no,id=hostnet1 \\\n".
		"-device virtio-net-pci,netdev=hostnet1,mac=52:54:00:43:6f:$mac,bus=pci.0,addr=0x6 \\\n".
		"-usb \\\n".
		"-vga cirrus \\\n".
		"-device virtio-balloon-pci,id=balloon0,bus=pci.0,addr=0x5 \\\n".
		"-nodefconfig \\\n".
		"-nodefaults \\\n".
		"-boot order=c,menu=on";
		return $vm;
    }
}

(new MySocketServer())->run();
?>

