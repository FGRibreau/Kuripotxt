<?php
include	"kuripotxt.class.php";

$mySender = new Kuripotxt();
//International number format without "+" 
$phoneNumber = 33612345678;
$mySender->sendSms($phoneNumber,'Hello world');//one method
?>