<?php

date_default_timezone_set("Asia/Tokyo");

mysql_connect('localhost','root','root') or die(mysql_error());
mysql_select_db('fc_calc') or die(mysql_error());
mysql_query('SET NAMES UTF8');


$userid = 1;
$dist = isset($argv[1])? (double)$argv[1]: null;
$fuel = isset($argv[2])? (double)$argv[2]: null;


$recordSet = mysql_query("SELECT * FROM fuelcost_save WHERE id = $userid");
$data = mysql_fetch_assoc($recordSet);

if(($dist <= 0) or ($fuel <= 0)){
  msg("入力された値が不正です");
  exit;
}

$fuelcost = $dist / $fuel;

$totaldist = $dist + $data['dist'];
$totalfuel = $fuel + $data['fuel'];
$totalfuelcost = $totaldist / $totalfuel;

$body = sprintf("[給油記録]：%.1fkm走行し、%.1fL給油しました。燃費%.2fkm/L (前回比:%+.2fkm/L)\n[総走行距離:%.1fkm 累計燃費:%.2fkm/L]\n",
                $dist,
                $fuel,
                $fuelcost,
                $fuelcost - $data['fuelcost_last'],
                $totaldist + $data['dist_add'],
                $totalfuelcost

               );

$SQL = sprintf("UPDATE fuelcost_save SET dist = $totaldist,
                                         fuel = $totalfuel,
                                         fuelcost_total=$totalfuelcost,
                                         fuelcost_last = $fuelcost
                                         WHERE id = $userid"
              );

echo $body;
mysql_query($SQL);

exit;

function msg($text){
  echo "[Error]{$text}",PHP_EOL;
}
