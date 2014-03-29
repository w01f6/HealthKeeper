<?php
include dirname(__FILE__) . '/../library/phputil/phpqrcode/qrlib.php';
include dirname(__FILE__) . '/../library/phputil/db_connect.php';

// Fetch info
$info = array('medicineName'    => $_REQUEST['medicineName'], 
              'activeSubstance' => $_REQUEST['activeSubstance'],
              'hStart'          => $_REQUEST['hStart'],
              'period'          => $_REQUEST['period'],
              'span'            => $_REQUEST['span'],
              'importance'      => $_REQUEST['importance']);

$info = json_encode($info);

// Set it to writable location, a place for temp generated PNG files
$PNG_TEMP_DIR = dirname(__FILE__) . DIRECTORY_SEPARATOR . '../qrcodes' . DIRECTORY_SEPARATOR;
// HTML PNG location prefix
$PNG_WEB_DIR = dirname(__FILE__) . '/../qrcodes/';
$errorCorrectionLevel = 'M';
$matrixPointSize = 6;

// Genereaza md5-ul unic pentru fiecare QR
$md5 = md5($info . '|' . $errorCorrectionLevel . '|' . $matrixPointSize);

// Verific daca exista deja in baza de date
$QRCode = ORM::for_table('QRCodes')->where('md5', $md5)->find_one();

// Daca exista, il folosesc direct
if($QRCode) {
    $filename = $md5 . '.png';

// Altfel in creez si il adaug in baza de date pentru o urmatoare refolosire
} else {
    $QRCode = ORM::for_table('QRCodes')->create();
    $QRCode->md5 = $md5;
    $QRCode->save();

    $QRCode = ORM::for_table('QRCodes')->where('md5', $md5)->find_one();

    $filename = $PNG_TEMP_DIR . $md5 . '.png';
    QRcode::png($info, $filename, $errorCorrectionLevel, 
        $matrixPointSize, 2);

    $filename = basename($filename);
}

$QRMap = ORM::for_table('QRMap')->where('qr_id', $QRCode->qr_id)
                                ->where('user_id', $_REQUEST['userId'])
                                ->find_one();

if(!$QRMap) {                    
    $QRMap = ORM::for_table('QRMap')->create();
    $QRMap->qr_id = $QRCode->qr_id;
    $QRMap->user_id = $_REQUEST['userId'];
    $QRMap->save();
}

$response = array('qrPath' => $filename);

echo json_encode($response);
?>
