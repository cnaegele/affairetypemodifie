<?php
require_once 'gdt/cldbgoeland.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
$idAffaire = 0;
if (isset($_GET['jsoncriteres'])) {
    $jsonCriteres = $_GET['jsoncriteres'];
    $oCriteres = json_decode($jsonCriteres, false);
    if (isset($oCriteres->id)) {
        $idAffaire = $oCriteres->id;
    }
    $bPrmIdAffaireOk = false;
    if (strlen($idAffaire) < 11) {
        $pattern = '/^\d+$/';
        if (preg_match($pattern, $idAffaire)) {
            $bPrmIdAffaireOk = true;
        }
    }
    if (!$bPrmIdAffaireOk) {
        $idAffaire = 0;
    }
}

$success = true;
$message = 'ok';
$dbgo = new DBGoeland();
$bret = $dbgo->queryRetJson2("CN_AffaireDataLight $idAffaire");
if ($bret === true) {
    $oAff = json_decode($dbgo->resString);
    if (count($oAff) === 1) {
        $idTypeAffaire = $oAff[0]->IdTypeAffaire;
        $nomAffaire = $oAff[0]->NomAffaire;
        $typeAffaire = $oAff[0]->TypeAffaire;
        $iTermine = $oAff[0]->BTermine;
        if ($iTermine === 1) {
            $bTermine = true;
        } else {
            $bTermine = false;
        }
        $bret = $dbgo->queryRetJson2("cn_typeaffaire_modification_dico_typefinal_liste $idAffaire");
        if ($bret === true) {
            $aTypesFinal = json_decode($dbgo->resString);
        } else {
            http_response_code(400);
            $success = false;
            $message = 'cn_typeaffaire_modification_dico_typefinal_liste:' . $dbgo->resErreur;
            $aTypesFinal = [];
        }
    } else {
        $success = false;
        $message = 'l\'affaire n\'existe pas';
        $idTypeAffaire = null;
        $nomAffaire = '';
        $typeAffaire = '';
        $bTermine = null;
    }
} else {
    http_response_code(400);
    $success = false;
    $message = 'CN_AffaireDataLight:' . $dbgo->resErreur;
    $idTypeAffaire = null;
    $nom = '';
    $type = '';
    $bTermine = null;
    $aTypesFinal = [];
}
unset($dbgo);
$affaireTypesChange = [
    'success' => $success,
    'message' => $message,
    'id' => $idAffaire,
    'idtype' => $idTypeAffaire,
    'nom' => $nomAffaire,
    'type' => $typeAffaire,
    'btermine' => $bTermine,
    'typesfinal' => $aTypesFinal
];
echo json_encode($affaireTypesChange);
