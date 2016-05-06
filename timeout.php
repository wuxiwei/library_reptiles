<?php
include "reptile.php";

$mysql=new PDO('mysql:host=localhost;dbname=cityuit','root','q123456');
$res = $mysql->query("select no from timeout_no where remark = 'timeout'");
foreach($res->fetchAll() as $row){
    print "$row[no]";
    print "\n";
}
