<?php
require("./PVE_PHP_class.php");
require("./IDmanager.php");

session_start();
// recuperation données get-------------

$action = $_GET["action"]; 
$nameuser = $_GET["name"];
$modset = $_GET["modset"];
$userinfo = $_GET["userinfo"];
$fileimport = $_FILES['file'];




/*----------------tvariable session-----*/

$pve1 = $_SESSION['pve1'];
$name = $_SESSION['user'];
$pve2 = $_SESSION['pve2'];





//-------test si utilisateur est enseignant ou admin

	//-----connection base mysql----------------------------------------
				 $conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
				// 
				if ($conn->connect_error) {
					
				    die("Connection failed: " . $conn->connect_error);
				} 
				
				//--------recupere les role des utilisateurs qui ne sont pas admin ou enseignats-- ds la BD utilisateur à 1
				
				$sql = "SELECT  Role FROM utilisateurs WHERE Login ='".$name." '";
				$result = $conn->query($sql);
				
				while($row = $result->fetch_assoc()) {
				$userrole = $row['Role'];	
					
				}
				
				
	//--------------------------------------------
			$conn->close();		

if ($userrole <> "enseignant") {exit(005);}// sortie du script







/*----------------def variable environement--------*/
$node_name = $servproxmox ;
 
if (isset($action)){
	
	switch ($action){
		//-----------get liste users-------------------------
		case "get":
			
			//-----connection base mysql----------------------------------------
			 $conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
			// 
			if ($conn->connect_error) {
				
			    die("Connection failed: " . $conn->connect_error);
			} 
			
			//--------recupere les role des utilisateurs qui ne sont pas admin ou enseignats-- ds la BD utilisateur à 1
			
			$sql = "SELECT  * FROM roles WHERE utilisateurs = 1 ";
			$result = $conn->query($sql);
			$i =0;
			while($row = $result->fetch_assoc()) {
			$userroles[$i] = $row['type'];	
			$i++;	
			}
			
						
			
			//---------recupere parametres utilisateur-------------
			
			$sql = "SELECT  * FROM utilisateurs ";
			$result = $conn->query($sql);
			$i = 0;
			while($row = $result->fetch_assoc()) {
				if (in_array($row['Role'], $userroles)){//ne renvois que les utilisateurs qui ne sont pas admin ou enseignants
					$usersinfo[$i]['login'] =  $row['Login']; 
					$usersinfo[$i]['role'] =  $row['Role']; 
					$usersinfo[$i]['status'] = $row['status']; 
					$usersinfo[$i]['groupe'] = $row['groupe'];
				    $usersinfo[$i]['messagenoaccess'] = $row["messagenoaccess"];
					$usersinfo[$i]['lab'] = $row['lab'];
					$usersinfo[$i]['quota'] = $row['quota'];
					$i++;
				}
				
			}
			//--------------------------------------------
			$conn->close();
				
		
		
		
			// retour requete ajax---	
			$arr = array(
		   'action' => $action,
		   'usersinfo' => $usersinfo
			
			 );
			
			echo json_encode($arr);	
			
			
			
		break;	
		
		//----------------------get user------------------------------------
		case "getuser":
			
			//-----connection base mysql----------------------------------------
			 $conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
			// 
			if ($conn->connect_error) {
				
			    die("Connection failed: " . $conn->connect_error);
			} 
			//--------recupere les role des utilisateurs qui ne sont pas admin ou enseignats-- ds la BD utilisateur à 1
			
			$sql = "SELECT  * FROM roles WHERE utilisateurs = 1 ";
			$result = $conn->query($sql);
			$i =0;
			while($row = $result->fetch_assoc()) {
			$userroles[$i] = $row['type'];	
			$i++;	
			}
			
			//--------recupere parametres utilisateur-------------
			
			$sql = "SELECT  * FROM utilisateurs WHERE Login ='".$nameuser."' ";
			$result = $conn->query($sql);
			$i = 0;
			while($row = $result->fetch_assoc()) {
				
				//if ($row['Role'] == "labuser" || $row['Role']== "tpuser"){
					if (in_array($row['Role'], $userroles)){//ne renvois que les utilisateurs qui ne sont pas admin ou enseignats
					$usersinfo['login'] =  $row['Login']; 
					$usersinfo['role'] =  $row['Role']; 
					$usersinfo['status'] = $row['status']; 
					$usersinfo['groupe'] = $row['groupe'];
				    $usersinfo['messagenoaccess'] = $row["messagenoaccess"];
					$usersinfo['lab'] = $row['lab'];
					$usersinfo['quota'] = $row['quota'];
					
					
				}
				
			}
			//--------------------------------------------
			$conn->close();
				
			$labino = lablibre($admmysql,$admmysqlpass,$bdgecko);
			$groupelist = getgroupe($admmysql,$admmysqlpass,$bdgecko);
			
			// retour requete ajax---	
			$arr = array(
		   'action' => $action,
		   'usersinfo' => $usersinfo,
		   'lablist' => $labino[0],
		   'labaffect' => $labino[1],
		   'labino'=> $labino[2],
			'userrole' => $userroles,
			'groupelist' => $groupelist
			 );
			
			echo json_encode($arr);	
			
			
			
		
		break;
		
		
		
	//----------------------------------modif user------------------------------------------------------------------------------------------------------------
		case "moduser":	
			
			$usersprox = $pve2->get("/access/users/".$userinfo['login']."@".$realm);
			$groups = $pve2->get("/access/groups/");
			$domain = $pve2->get("/access/domains");
			if (!(isset($modset) and isset($userinfo)))	{//test initialisation variables
					
					
				$log = "variables modset  ou userinfo non defini";
				// retour requete ajax---	
				$arr = array( 'log' => $log);
				
				echo json_encode($arr);	
							
				
			break;	
			
			}
		
		
		
		
			if ($modset['role'] == "labuser"){// cas ou role = labuser
			
					$log = "role labuser ";
					
					//---verif si changement de role
						if ($userinfo['role'] !== "labuser"){// changement de role on traite plus tard
						
						
						}
						else{// pas de changement de role
								$log .= "status".$modset['status'];
								//-------------------gestion status----------------------------------------------------------
								if ($modset['status']== 0){//  status désactivé
									if ($userinfo['status'] == 0) {// pas de changement
									$log .="deja désactivé";
									}
									else {// modification du status
										
										
										//-----connection base mysql----------------------------------------
										 $conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
										// 
										if ($conn->connect_error) {
											
										    die("Connection failed: " . $conn->connect_error);
										} 
										//--------Maj de la table utilisateur avec le status désactivé
										
										$sql = "UPDATE  utilisateurs SET status = 0 WHERE Login = '".$userinfo['login']."' ";
										$result = $conn->query($sql);
										$log .="requete desactivation passe";
										$log .= $result;
										//-------
										$conn->close();						
										//-----	
									
										//------désactivation dans proxmox-------------------------------------------------------------------------------
									  	  $param = array();
											$param['enable'] = '0';
								       	
										
										
											
											$temp = $pve2->put("/access/users/".$userinfo['login']."@".$realm,$param);
											if (!$temp){
											$log .= "problème de désactivation !";
											
												}
											else {$log .= $temp."désactivation réalisée avec succès!";
											
											}
										//------------------------------------------
									
									}
										
								}
								else {// status activé
									if ($userinfo['status'] == 1) {// pas de changement
									}
									else {// modification du status
										//-----connection base mysql----------------------------------------
										 $conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
										// 
										if ($conn->connect_error) {
											
										    die("Connection failed: " . $conn->connect_error);
										} 
										//--------Maj de la table utilisateur avec le status activé
										
										$sql = "UPDATE  utilisateurs SET status = 1 WHERE Login = '".$userinfo['login']."' ";
										$result = $conn->query($sql);
										$log .="activation passe";
										
										//-------
										$conn->close();		
											//------activation dans proxmox-------------------------------------------------------------------------------
								  			$param = array();
											$param['enable'] = '1';
									       	
											
											
											//$temp = $pve2->post("/nodes/".$node_name."/qemu/".$clonesel."/clone/",$new_clone);
											$temp = $pve2->put("/access/users/".$userinfo['login']."@".$realm,$param);
											if (!$temp){
											$log .= "problème de d'activation !";
											
												}
											else {$log .= $temp."activation réalisée avec succès!";
											
											}		
										
									}
																		
								}	
								//----------fin de gestion status----------------------------------------
								
								//---------gestion changement de lab--------------
								if ($userinfo['lab'] <> $modset['lab']){//test si changement de lab
									
										//--test si lab est bien inoccupé
										 $log .=("changement de lab");
										 $labstatus = lablibre($admmysql,$admmysqlpass,$bdgecko);
											if (in_array($modset['lab'], $labstatus[2], TRUE)){
												
											//$newlab = $modset['lab'];
												
												
												
												$log .= "lab changement  ok new: " + $modset['lab'];
												
												
												//---------------gangement groupe proxmox grlab pour accès au pool
												$groupe = "grlab".$modset['lab'];
												$param = array();
												$param['groups'] = $groupe;
								       	
										
										
											
												$temp = $pve2->put("/access/users/".$userinfo['login']."@".$realm,$param);
												if (!$temp){
												$log .= "chgement groupe pool ok !";
											
												}
												else {$log .= $temp."chgement groupe pool pas ok !";
											
												}
												//----------------chgement dan BD du lab----------------------------------------------
												
												//-----connection base mysql----------------------------------------
												 $conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
												// 
												if ($conn->connect_error) {
													
												    die("Connection failed: " . $conn->connect_error);
												} 
												//--------Maj de la table utilisateur avec le status activé
												
												$sql = "UPDATE  utilisateurs SET lab = '".$modset['lab']."' WHERE Login = '".$userinfo['login']."' ";
												$result = $conn->query($sql);
												$log .="lab changé";
												
												//-------
												$conn->close();		
														
												
												
												
												
												
												
												
													
											
											
											
											
										}
										else {$log .= "lab changement pas ok";}
								
								
									
									
									
								}
								else {$log .="pas de changement de lab";}
								//-----------fin changement de lab-------------
								
								
								
							
							
						}//fin pas de chgement role
				
				}// fin role labuser
			
			else { $log = "role ?";}
			
			//----------------------------------enregistrement dans BD message no access et groupe et quota----------------------------
			
										//-----connection base mysql----------------------------------------
										 $conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
										// 
										if ($conn->connect_error) {
											
										    die("Connection failed: " . $conn->connect_error);
										} 
										//--------Maj de la table utilisateur avec le status désactivé
										
										$sql = "UPDATE  utilisateurs SET messagenoaccess = '".$modset['messagenoaccess']."' , quota ='".$modset['quota']."' WHERE Login = '".$userinfo['login']."' ";
										$result = $conn->query($sql);
										$log .="modif texte no access ok";
										$log .= $result;
										//-------
										if ($modset['groupe'] <> $userinfo['groupe']){
										$sql = "UPDATE  utilisateurs SET groupe = '".$modset['groupe']."' WHERE Login = '".$userinfo['login']."' ";
										$result = $conn->query($sql);
										$log .="modif texte no access ok";
										$log .= $result;
										}
										
										
										
										$conn->close();						
										//-----	
			
			
			
			
			
			
			// retour requete ajax---	
			$arr = array(
		   
		   'log' => $log,
		   'usersprox' => $usersprox,
		   'groups' => $groups,
		   'domain' => $domain
		   
			
			 );
			
			echo json_encode($arr);	
			
			
			
			
		break;	
		
		
		//-------------------------newuser-------------------------------
		case "newuser":	
			
		
		
		$roleusers = roleuser($admmysql,$admmysqlpass,$bdgecko);		
		$labino = lablibre($admmysql,$admmysqlpass,$bdgecko);
		$groupelist = getgroupe($admmysql,$admmysqlpass,$bdgecko);	
			
		// retour requete ajax---	
			$arr = array(
		   
		   'log' => $log,
		   'userrole' => $roleusers,
		   'labino'=> $labino,
		   'groupelist' =>$groupelist
		   
			
			 );
			
			echo json_encode($arr);	
				
			
			
			
		break;	
		
		//-----------create user---------------------------------
		case "createuser":
		$users = $pve2->get("/access/users");
		//test les variables modset/
		
		//création de l'utilsateur dans proxmox
			$groupe = "grlab".$modset['lab'];
			$userid = $modset['login']."@afpa";
			$login = $modset['login'];
			$param = array();
			$param['userid'] = $userid;
			$param['groups'] = $groupe;				       	
			$param['enable'] = $modset['status'];							
										
			$users = $pve2->get("/access/users");
			$i =0;
			$user_exist = FALSE;
			foreach ($users as $userkey){
				$userlist[$i] = $userkey['userid'];
				if ($userkey['userid']== $userid) { $user_exist = TRUE;} 
				$i++;
			}
											
			if (!($user_exist)) { $temp = $pve2->post("/access/users",$param);}
			
			$users = $pve2->get("/access/users");
			$i =0;
			$user_exist = FALSE;
			foreach ($users as $userkey){
				$userlist[$i] = $userkey['userid'];
				if ($userkey['userid']== $userid) { $user_exist = TRUE;} 
				$i++;
			}
		
			//--------------------Maj utilisateur dans la BD---------------
			//-----connection base mysql----------------------------------------
										 $conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
										// 
										if ($conn->connect_error) {
											
										    die("Connection failed: " . $conn->connect_error);
										} 
										//--------Maj de la table utilisateur avec le status désactivé
									
										$sql = "INSERT INTO  utilisateurs (Login,lab,Role,groupe,status,messagenoaccess)
										VALUES ('".$login."','".$modset['lab']."','labuser','".$modset['groupe']."','".$modset['status']."','".$modset['messagenoaccess']."')";
										$result = $conn->query($sql);
										$log .="ajout user ds BD ok";
										$log .= $result;
										//-------
										
										
										
										
										$conn->close();						
										//-----	
			
		
		// retour requete ajax---	
			$arr = array(
		   
		   'log' => $log,
		   'user_exist' => $user_exist
		   
		   
			
			 );
			
			echo json_encode($arr);	
			
			
		break;
		
		//----------------------DELETE USER-------------------------------------------
		case "deleteuser":
			
		//----suppression ds Proxmox----------------------
			$userid = $modset."@".$realm;
			
			$param['userid'] = $userid;
			//$param['userid'] = $modset;
			$temp = $pve2->delete("/access/users/".$userid,$param);
		
		//-----suppression ds BD---------------------------
		
		
										//-----connection base mysql----------------------------------------
										 $conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
										// 
										if ($conn->connect_error) {
											
										    die("Connection failed: " . $conn->connect_error);
										} 
		
										//------------------------------
										
										$sql = "DELETE FROM utilisateurs WHERE Login= '".$modset."'";
		
										$result = $conn->query($sql);
										
										if ($result) {$log = 'suppression OK!';}
										else {$log = 'Probleme suppression';}
		
		
		
		
										$conn->close();						
										//-----	
			
		
		
		// retour requete ajax---	
			$arr = array(
		   
		   'log' => $log,
		   'login' => $modset
		   
		   
			
			 );
			
			echo json_encode($arr);	
			
		
		
		
		break;
		
	//----------------------------------import users----------------------------------
		case "importfile":	
			
			
			$fType = $fileimport['type'];
			$fsize = $fileimport['size'];
			
			
			
			
			
			
			
		// retour requete ajax---	
			$arr = array(
		   
		   'type' => $ftype,
		   'size' => $fsuze
		   
		   
			
			 );
			
			echo json_encode($arr);	
			
		
		
		
		break;
		
		
		
//-------begin netupdate-------tempo---------------------------------------------------------------------------------------------
		case "netupdate":
			
			
			
			
			
		$idprox =	createnetwork($admmysql, $admmysqlpass, $bdgecko);
			
		// retour requete ajax---	
			$arr = array(
		   
		   'log' => $log,
		   'idprox' => $idprox
		   
			
			 );
			
			echo json_encode($arr);	
				
			
						
		break;
		//-------fin netupdate-----------
	
	}
	
	
}

//*************************************************************Function***************************************************************
function lablibre($admmysql,$admmysqlpass,$bdgecko){
	
	
		//-----connection base mysql----------------------------------------
			 $conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
			// 
			if ($conn->connect_error) {
				
			    die("Connection failed: " . $conn->connect_error);
			} 
			
			//--------recupere list des lab utilisateurs -------------
			
			$sql = "SELECT  * FROM labo ";
			$result = $conn->query($sql);
			$i = 0;
			while($row = $result->fetch_assoc()) {
				$lab = intval($row['lab']);
				
				if ( $lab >0 and $lab <100){
					$lablist[$i] =  $row['lab']; 
					$i++;
					
					
				}
				
			}
			//----------------recupere list lab affectés aux utilisateurs----------------------------
			$i = 0;
			$sql = "SELECT  lab FROM utilisateurs";
			$result = $conn->query($sql);
			while($row = $result->fetch_assoc()) {
				$lab = intval($row['lab']);
				
				if ( $lab >0 and $lab <100){
					$labaffecte[$i] =  $row['lab']; 
					$i++;
					
					
				}
				
			}
			
			
			$conn->close();
				
	
		
		$i =0;
		foreach ($lablist as $lab) {
			
				if (in_array($lab, $labaffecte, TRUE)){ }
				else{
				$labino[$i] = $lab;	
				$i++;
				}    	
		
		}
		
	return array ($lablist,$labaffecte,$labino);
	
	
	
	
	
	
	
	
	
	
	
	
	
}


function getgroupe ($admmysql,$admmysqlpass,$bdgecko){
	
	//-----connection base mysql----------------------------------------
			 $conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
			// 
			if ($conn->connect_error) {
				
			    die("Connection failed: " . $conn->connect_error);
			} 
			//--------recupere info template selon ID-------------
			
			$sql = "SELECT  * FROM groupe ";
			$result = $conn->query($sql);
			$i = 0;
			while($row = $result->fetch_assoc()) {
				
				$groupeinfo[$i]['nom'] =  $row['nom']; 
			//	$groupeinfo[$i]['membres'] =  $row['membres']; 
				$groupeinfo[$i]['id'] = $row['id']; 
				$i++;
				
				
			}
			//--------------------------------------------
			$conn->close();
				
		
		return $groupeinfo ;
	
	
	
	
}
//------------------------------------------------------------------------

function roleuser ($admmysql,$admmysqlpass,$bdgecko){

			//-----connection base mysql----------------------------------------
			 $conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
			// 
			if ($conn->connect_error) {
				
			    die("Connection failed: " . $conn->connect_error);
			} 
			//--------recupere les role des utilisateurs qui ne sont pas admin ou enseignats-- ds la BD utilisateur à 1
			$sql = "SELECT  * FROM roles WHERE utilisateurs = 1 ";
			$result = $conn->query($sql);
			$i =0;
			while($row = $result->fetch_assoc()) {
			$userroles[$i] = $row['type'];	
			$i++;	
			}
			//--------------------------------------------
			$conn->close();
			
	return $userroles	;
 
}






//-----fonction temporaire création des resau dans BD table vswitchlab
function createnetwork($admmysql, $admmysqlpass, $bdgecko) {
	
	//-----connection base mysql----------------------------------------
			 $conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
			// 
			if ($conn->connect_error) {
				
			    die("Connection failed: " . $conn->connect_error);
			} 
	
	
	
	//----------------------------------------------
			$sql = "SELECT  * FROM vswitchlab  ";
			$result = $conn->query($sql);
			$i =0;
			while($row = $result->fetch_assoc()) {
			$netbd[$i]['IDprox'] = $row['IDprox'];	
			$netbd[$i]['Nom'] = $row['Nom'];	
			$netbd[$i]['lab'] = $row['lab'];	
			$netbd[$i]['IDlab'] = $row['IDlab'];
			$i++;	
			}
	//--------------------------------------------
			for ($i = 16;$i <22;$i++){
				if ($i <10 ){$id = "vmbr0". $i;
				$nom = "switch0".$i.".";
				$lab = "lab0".$i;
				$IDlab = $i;
				
				}
				else{$id = "vmbr".$i;
				$nom = "switch".$i.".";
				$lab = "lab".$i;
				$IDlab = $i;
				
				
				}	
				for ($j = 0;$j<3;$j++){
					$idkey = $id.$j;
					$nomkey = $nom.$j;
					if	(in_array($idkey,$netbd[$i]['IDprox'],TRUE)){
						
						
					}
					else{//creation entré réseau
					//-----connection base mysql----------------------------------------
							 $conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
							// 
							if ($conn->connect_error) {
								
							    die("Connection failed: " . $conn->connect_error);
							} 
							
							//--------update template-----------------------------------------
								
								
							$sql = "INSERT INTO vswitchlab (IDprox,Nom,lab,IDlab)
											VALUES ('".$idkey."','".$nomkey."','".$lab."','".$IDlab."')";
											
											if ($conn->query($sql) === TRUE) {
											    $message .= "New record created successfully";
											} else {
											    $message .= "Error: " . $sql . "<br>" . $conn->error;
											}
							//--------------------------------------------
							
				
						
						
						
						
					}
				
				}
			//if (in_array($modset['lab'], $labstatus[2], TRUE)){}
													
				
			}
			
	
	
			$conn->close();
return $netbd;
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
} 
 ?>