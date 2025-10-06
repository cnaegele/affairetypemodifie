<?php
require 'gdt/gautentificationf5.php';
require_once '/data/dataweb/GoelandWeb/webservice/employe/clCNWSEmployeSecurite.php';
require_once 'gdt/cldbgoeland.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers:  *");
header("Access-Control-Allow-Methods:  POST");
$idCaller = 0;
if (array_key_exists('empid', $_SESSION)) {
    $idCaller = $_SESSION['empid'];
}
$nbrChange = 0;
if ($idCaller > 0) {
    $pseudoWSEmployeSecurite = new CNWSEmployeSecurite();
    if ($pseudoWSEmployeSecurite->isInGroupe($idCaller, 'GoelandManager')) {
        $jsonData = file_get_contents('php://input');
        $oData = json_decode($jsonData);
        $idAffaire = $oData->idaffaire;
        $idTypeFinal = $oData->idtypefinal;
        $idEmpCaller = $oData->idempcaller;

        $dbgo = new DBGoeland();
        $sSql = "cn_affaire_type_modification $idAffaire, $idTypeFinal, $idEmpCaller";
        //echo '{"message":"OK","sSql":"' . $sSql . '"}';
        $dbgo->queryRetInt($sSql, 'W');
        $nbrChange = $dbgo->resInt;
        unset($dbgo);
        if ($nbrChange == 1) {
            $success = true;
            $message = 'ok';
        } else {
            $success = false;
            $message = 'pas de changement effectué';
        }
    } else {
        $success = false;
        $message = 'pas d\'autorisation GoelandManager';
    }
} else {
    $success = false;
    $message = 'pas d\'identification F5';
}

$affaireTypesChange = [
    'success' => $success,
    'message' => $message,
];
echo json_encode($affaireTypesChange);