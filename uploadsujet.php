<?php
require("./IDmanager.php");
session_start();
$datasend = $_GET["datasend"];
$fileimport = $_FILES['file'];
$fType = $fileimport['type'];

$name = $fileimport['name'];
$size = $fileimport['size'];

move_uploaded_file($fileimport["tmp_name"],"/var/netlab/sujettp/".$fileimport["name"]);
if ($fType <> "application/pdf"){$fileok = false; $log = 'Mauvais type de fichier';}
else {
	$fileok = true;
	$log ='fichier ok';

}



if ($fileok){
	
	
// connect mysql
			$conn = new mysqli('localhost', $admmysql, $admmysqlpass, $bdgecko);
			// 
			if ($conn->connect_error) {
				echo("pas bon");
			    die("Connection failed: " . $conn->connect_error);
			} 
			
//--------Maj de la table tp_list 
					
		$sql = "UPDATE tp_list 
		 SET sujetTP = '".$fileimport["name"]."'
		 WHERE name = '".$datasend."' ";
		$result = $conn->query($sql);
		if ($conn->query($sql) === TRUE) {
							    $message .= "mdification successfully";
								$ok = 1;
							} else {
							    $message .= "Error: " . $sql . "<br>" . $conn->error;
								$ok= 0;
							}
						
			
			
// retour requete ajax---	
			$arr = array(
		   'log' => $log,
		   'type' => $fType,
		   'size' => $size,
		   'name' => $name,
		   'fileimport' => $fileimport,
		   'fileok' => $fileok,
		   'datasend' => $datasend
		   
		  
			 );
			
			echo json_encode($arr);	
			

}
else{
// retour requete ajax---	
			$arr = array( 'fileok' => $fileok);
			
			echo json_encode($arr);	
				
}

?>