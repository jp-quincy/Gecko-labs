<?php
require("./PVE_PHP_class.php");
require("./IDmanager.php");

session_start();
// recuperation données get-------------


$action = $_GET["action"]; 
$userlogin = $_GET["userlogin"]; 
$pubtemp = $_GET["pubtemp"];
$templateid = $_GET["templateid"];
$tpname = $_GET["tpname"];
$datasend = $_GET["datasend"];
/*----------------tvariable session-----*/

$pve1 = $_SESSION['pve1'];
$name = $_SESSION['user'];
$pve2 = $_SESSION['pve2'];

$node_name = $servproxmox;

//--------------------------------------------

//-------test si utilisateur est enseignant ou labuser ou tpuser

	//-----connection base mysql----------------------------------------
				 $conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
				// 
				if ($conn->connect_error) {
					
				    die("Connection failed: " . $conn->connect_error);
				} 
				
				//--------recupere les role des utilisateurs - ds la BD utilisateur à 1
				
				$sql = "SELECT  Role FROM utilisateurs WHERE Login ='".$name." '";
				$result = $conn->query($sql);
				
				while($row = $result->fetch_assoc()) {
				$userrole = $row['Role'];	
					
				}
				
				
	//--------------------------------------------
			$conn->close();		

if (($userrole <> "enseignant") and ($userrole <> "labuser" ) and ($userrole <> "tpuser")) {exit(005);}// sortie du script







if (isset($action)) {
	$param = array();
//--------------------------------------------------------------DEBUT ACTION----------------------------------------------------------------------

switch ($action){
	#--------------------GET-TP LIST-------------------
	case "get-tplist":
		

			
			
			
			// connect mysql
			$conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
			// 
			if ($conn->connect_error) {
				echo("pas bon");
			    die("Connection failed: " . $conn->connect_error);
			} 
			//------------------verification si l'utilisateur à un TP en cours----
			
			$sql = "SELECT  * FROM tp_pool WHERE user='".$name."'";
			$result = $conn->query($sql);
			if ($result->num_rows > 0) {
						$tp = array();
					 while($row = $result->fetch_assoc()) {
				    	$tp['pool'] = $row['pool'];
						$tp['id_pool'] =$row['id_pool'];
						$tp['startdate'] = $row['startdate'];
						$tp['enddate'] = $row['enddate'];
						$tp['user'] = $row['user'];
						$tp['tp']= $row['tp'];
					 }	
			$tpinuse = "yes";
			//-----recuperre le sujet du TP-----
			$sql = "SELECT  sujetTP FROM tp_list WHERE name='".$tp['tp']."'";
			$result = $conn->query($sql);
			while($row = $result->fetch_assoc()) {
				    	$sujetTP = $row['sujetTP'];
						
					 }	
			//-----recuprere les VM du pool-----------------
			$vmtp = array();
			$i=0;
			$POOLconf = $pve2->get("/pools/".$tp['pool']);	
						foreach ($POOLconf['members'] as $POOLconfu){
							$vmtp[$i]['vmid'] = $POOLconfu['vmid'];
							$vmtp[$i]['vmname'] = $POOLconfu['name'];
							$i++ ;
						}
			// retour requete ajax TP deja en cours--------------------	
			$arr = array(
		   'TPpool' => $tp,
		   'vmtp' => $vmtp,
		   'sujetTP' => $sujetTP,
		   'tpinuse' => $tpinuse
		   );

			
			echo json_encode($arr);	
			
			
			
			break;		
			
			
				
			}
			$tpinuse= "no";
			
			//--------recupere liste des TP------------
			
			$sql = "SELECT  * FROM tp_list ";
			$result = $conn->query($sql);
			//$aff= $result->num_rows;
		if ($result->num_rows > 0) {
			    // output data of each row
			    $i = 0;
				$list= array();
				
			    while($row = $result->fetch_assoc()) {
			    	$name = $row['name'];
					$description = $row['description'];
					$type = $row['type'];
					$duration = $row['duration'];
					$sujetTP = $row['sujetTP'];
					$publier = $row['publier'];
					if (($publier == 1) or ($userrole == "enseignant")){
						$sql2 = "SELECT * FROM tp_template WHERE tp_name = '".$name."'"; //recupere le templates attchés au TP
						$result2 = $conn->query($sql2);
						$j=0;
						$listtemp = array();
						while($row2 = $result2->fetch_assoc()){
						$listtemp[$j]= $row2['templates_ID'];
						$j++;
						}
						$enr = array('name' => $name,'description' => $description,'type' => $type,'publier'  => $publier, 'duration' => $duration,'sujetTP' => $sujetTP ,'list_template' => $listtemp);
					
						
						$list[$i]= $enr; 
						$i++;
					}	
			    }
				$conn->close();
			// retour requete ajax-------------------------	
			$arr = array(
		   'list' => $list,
		   'tpinuse' => $tpinuse
		   );

			
			echo json_encode($arr);	
			
			
			
		break;		
			}
			else {
				// retour requete ajax-------------------------	
			$arr = array(
		   'list' => 'pas de tp'
		   );

			
			echo json_encode($arr);	
			
				break;
				
				 }
			
			
			
			
			
			
			
			
			
			
			
//-----------------------------------Deploiement TP----------------------
case "depltp":

			// ---connect mysql
			$conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
			// 
			if ($conn->connect_error) {
				echo("pas bon");
			    die("Connection failed: " . $conn->connect_error);
			} 
			
			//--------------
			
			//---------recherche d'un pool TP libre--------------------------------
			
			$sql = "SELECT  * FROM tp_pool WHERE inuse ='0' LIMIT 1";
			
			$result = $conn->query($sql);
			if ($result->num_rows > 0) {
				
				while($row = $result->fetch_assoc()) {
				$id_pool = $row['id_pool'];
				$pool = $row['pool'];
				$TPpoolFree = array ('id_pool' => $id_pool, 'pool' => $pool);	
				}
				
				
				
				
			}
			
			else{
				//----------------pas de pool libre retour-------
				$ok = 0;
				$log = "Pas de TP libre";
				
				
				//--fermeture base SQL-----------
				$conn->close();
				
				// retour requete ajax-------------------------	
				$arr = array(
			   'ok' => $ok,
			   'log'=> $log
			   );
	
				
				echo json_encode($arr);	
				break;		
								
			
			}
			//--------------------------------------------------------------------------
			
			
			//--------------------recupere info TP------------------------------------
			
			
			//----------a faire suppression des VM qui sont sur le reseau du pool------
			
			
			//----------------------depl des VM ds le LAB-----------------------------
			
			$list_template = $datasend['list_template'];
			$idnet = 200 + $TPpoolFree['id_pool'];
			$idnet = (string) $idnet; //preparation base reseau en fonction de l'ID TP tp05 -> 205
			$j = 0; //initialisation du rang du tableau des VM du TP créé
				foreach ($list_template as $templateid){
				
					//--------determination du 1er ID libre pour les VM du TP--
					//--------plage ID des VM TP >30000
				
					$VM = $pve2->get("/nodes/".$node_name."/qemu");
					$i = 0;
					#----chaque premier chiffre d'un vmid correspond au lab
					$vmidrange = 30000;
					# print_r($vmidrange);
					$posid = $vmidrange;
					foreach ($VM as $VMu){
						if ($VMu['vmid'] >= $vmidrange){$tabid[] = $VMu['vmid'];}	
						#print_r("--".$VMu['vmid']);
						}
						if ($tabid <> null){
							while (in_array($posid,$tabid)){$posid ++;}
						}
						
						$idlibre = $posid;
					
					
					//----modification des interfaces réseau du template--------------------------
					
					//recupere la conf réseau en cours
					$VMconf = $pve2->get("/nodes/".$node_name."/qemu/".$templateid."/config");
					$nameclone = $VMconf['name'];
					//---recupere les param réseau 5 cartes max!---	 
					$mac = null;
					$maclist = null;
					$vmnet = null;
				    for ($nb = 0; $nb < 5; $nb ++){
				   		$net="net".$nb;
				 	    if (array_key_exists($net,$VMconf)){
							
						$VMnetconf = explode (",",$VMconf[$net]);	
						$mac = explode("=",$VMnetconf[0]);
						$maclist[$nb] = $mac[1];
						$netsimple = explode("=",$VMnetconf[1]);
						$vmnet[$nb]= $netsimple[1];
						//adaptation du du réseau 
							if ($vmnet[$nb] <> $netext){// si le reseau est le resau externe ne rien faire
								$netnum = substr($vmnet[$nb],strlen($vmnet[$nb])-1)	;//recupere le dernier  num du réseau comme wmbr301 donne 1
								if ($netnum <3){$vmnet[$nb] = "vmbr".$idnet.$netnum;} else {$vmnet[$nb] = "vmbr".$idnet."0";}// il y a 3 switch virtuel par lab don si sup on  met  le premier
											//modification du reseau
													$param = array();
																			
													$param[$net]="e1000=".$mac[1].",bridge=".$vmnet[$nb];
																					
													$temp = $pve2->put("/nodes/".$node_name."/qemu/".$templateid."/config",$param);
											
													#------tempo-------
													
													While (!(array_key_exists($net,$VMconf))){
													$VMconf = $pve2->get("/nodes/".$node_name."/qemu/".$templateid."/config");
													}
														
							}
								
						}
					}		
								
					//-------------------------------------------------------------------------------------				
			
			
					//----------------clone des templates-----------------------
						//modifier pour les envoyer avec les ID--------!!!!!
						//-------recupere le nom du template
						
						// ---connect mysql
						$conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
						// 
						if ($conn->connect_error) {
							echo("pas bon");
						    die("Connection failed: " . $conn->connect_error);
						} 
						//------------------------------------		
						$sql = "SELECT  nom FROM templates WHERE id ='".$templateid."' ";
						
						$result = $conn->query($sql);
					
							
							while($row = $result->fetch_assoc()) {
							$nameclone = $row['nom'];
							
							}
							
						//--fermeture base SQL-----------
						$conn->close();	
							
							
						
			
						
						
						
					
					
					# Create config nouveau clone.
			        $new_clone = array();
					$new_clone['newid'] = $idlibre;
			       	$new_clone['name'] = $nameclone;
					$new_clone['pool'] = $pool;
					
					
					$tempclone = $pve2->post("/nodes/".$node_name."/qemu/".$templateid."/clone/",$new_clone);			
						
						
				$vmtp[$j] = array(
				'vmid' => $idlibre,
				'vmname' => $nameclone
				
				);
			
				$j++;
			    // --fin foreach
				}
			
			//------- donne les droits à l'utilisateur sur le pool--------------------
			$param = array();
			$param['users'] =$name."@afpa";
			$param['path'] = "/pool/".$TPpoolFree['pool'];
			$param['roles'] = "PVEVMUser";
			$param['propagate'] = "1";
			$temp = $pve2->put("/access/acl/",$param);		
			
			//------------------ecriture des infos du TP dans la table tp_pool--------------
			
				
				// ---connect mysql
						$conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
						// 
						if ($conn->connect_error) {
							echo("pas bon");
						    die("Connection failed: " . $conn->connect_error);
						} 
				//--------Maj de la table tp_pool 			
				$date = date("Y-m-d H:i:s");	
				$date2 = date("Y-m-d H:i:s", strtotime("+".$datasend['duration']." minutes"));					
				$sql = "UPDATE tp_pool 
				 SET inuse = '1', user ='".$name."', tp = '".$datasend['name']."', startdate = '".$date."' , enddate = '".$date2."'
				 WHERE id_pool = '".$TPpoolFree['id_pool']."' ";
				$result = $conn->query($sql);
				if ($conn->query($sql) === TRUE) {
									    $message .= "mdification successfully";
										$ok = 1;
									} else {
									    $message .= "Error: " . $sql . "<br>" . $conn->error;
										$ok= 0;
									}	
				
				
			
			
			
			
			
			
			
			
			
			
			
			


		
			//--fermeture base SQL------------------------
			$conn->close();
			
			//-------------demarage des VM-----
			foreach ($vmtp as $vm){
			$vmid = $vm['vmid'];
			$tempclone = $pve2->POST("/nodes/".$node_name."/qemu/".$vmid."/status/start");	
				
				
				
				
				
				
				
				
				
			}
			
			
			
			
			
			// retour requete ajax-------------------------	
			$ok = 1;
			$arr = array(
			'temp' => $temp,
			'date2' => $date2, 
			'date' => $date,
			'vmtp' => $vmtp,
			'ok' => $ok,
			'sujetTP' => $datasend['sujetTP'],
			'TPpool' => $TPpoolFree,
		   );
		
			
			echo json_encode($arr);	
					
					
					
			break;		
//---------------------------fin deploement TP----------------------------------





#--------------------GET-timeleft---------------
case "get-timeleft":
		
				//-----connection base mysql----------------------------------------
				 $conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
				// 
				if ($conn->connect_error) {
					
				    die("Connection failed: " . $conn->connect_error);
				} 
				//-------recupere le TP de l'utilisateur-----------
				$sql = "SELECT  enddate FROM  tp_pool WHERE user ='".$name." '";
				$result = $conn->query($sql);
				if ($result->num_rows > 0) {
				
				while($row = $result->fetch_assoc()) {
				$enddate = $row['enddate'];
				$date = date("Y-m-d H:i:s");
				$timeleftsec = strtotime($enddate) - strtotime($date);
				if ($timeleftsec < 0){$fintp = 1;}
				else {$fintp = 0;}
				$timeleftmin = round($timeleftsec / 60);
				$heure = (int)($timeleftmin /60);
				$minute = round(($timeleftmin/60 - (int)($timeleftmin/60))*60);
				if ($minute < 10){$minute = "0". (string) $minute;}
				else {$minute = (string) $minute;}
				$timeleft = "  ".(string) $heure."H".  $minute ;
				}
				
				
				}
				
				
				
				
				
		// retour requete ajax---	
			$arr = array(
			'fintp' => $fintp,
		   'timeleft' => $timeleft
		   );

			
			echo json_encode($arr);	
			
			
			
			
			
			
			
			
			
			
			
			
			
			
			
		break;		
	
	










//----------------------suppression TP------------------------------------------
case "deletetp":




	//-----suppression des VM------
	$vmlist= $datasend['vmtp'];
	
	foreach ($vmlist as $vm){
	$vmid = $vm['vmid'];
	$status = $pve2->get("/nodes/".$node_name."/qemu/".$vmid."/status/current/");
			if (isset($status)){
			if ($status['status'] <> 'stopped'){
				$tempdel = $pve2->POST("/nodes/".$node_name."/qemu/".$vmid."/status/stop",$param);
				while($status['status'] <> 'stopped'){$status = $pve2->get("/nodes/".$node_name."/qemu/".$vmid."/status/current/");}
				}
			
			}	
	$result = $pve2->DELETE("/nodes/".$node_name."/qemu/".$vmid);
	
	}

	//------mise à jour de la table 
		// ---connect mysql
						$conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
						// 
						if ($conn->connect_error) {
							echo("pas bon");
						    die("Connection failed: " . $conn->connect_error);
						} 

		
		//----------update---
		$TPpool = $datasend['TPpool'];
		$sql = "UPDATE tp_pool 
				 SET inuse = '0', user = NULL , tp = NULL, startdate = NULL , enddate = NULL
				 WHERE id_pool = '".$TPpool['id_pool']."' ";
				$result = $conn->query($sql);
				if ($conn->query($sql) === TRUE) {
									    $message .= "mdification successfully";
										$ok = 1;
									} else {
										
									    $message .= "Error: " . $sql . "<br>" . $conn->error;
										$ok= 0;
									}	
				

	//----suppression des acl pour l'utilisateur----------
	
	$param = array();
			$param['delete'] = "1";
			$param['users'] =$name."@afpa";
			$param['path'] = "/pool/".$TPpool['pool'];
			$param['roles'] = "PVEVMUser";
			
			$temp = $pve2->put("/access/acl/",$param);		








//--fermeture base SQL------------------------
			$conn->close();
			
			// retour requete ajax-------------------------	
			$ok = 1;
			$arr = array(
			'temp' => $temp,
			'message' => $message,
			'userrole' => $userrole,
			'param' => $param,
			
			'ok' => $ok,
		
		  
		   );
		
			
			echo json_encode($arr);	
					
					
					
			break;		

//----------------------------------------------------------------FIN ACTION------------------------------------------------------------------------
}
}



			
				

//------fin PHP


?>