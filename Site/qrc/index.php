<?php    
include "qrlib.php";    
QRcode::png($_REQUEST['data'], false, 'Q', 6);