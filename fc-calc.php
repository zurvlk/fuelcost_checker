<?php

date_default_timezone_set("Asia/Tokyo");

$mysqli = new mysqli('localhost', 'root', 'root', 'fc_calc');
if ($mysqli->connect_error) {
    echo $mysqli->connect_error;
    exit();
} else {
    $mysqli->set_charset("utf-8");
}

$uid = 1;
$dist = isset($argv[1])? (double)$argv[1]: null;
$fuel = isset($argv[2])? (double)$argv[2]: null;

if(($dist <= 0) or ($fuel <= 0)){
  errmsg("入力された値が不正です");
  exit;
}

$sql = "SELECT * FROM fuelcost_save WHERE userid = $uid";
$result = $mysqli->query($sql);
$data = $result->fetch_assoc();


  if($dist <= $data['dist_total']){
    $dist_total = $dist + $data['dist_total'];
    $mode = "TRIP";
  }else{
    $dist_total = $dist;
    $dist = $dist - $data['dist_total'];
    $mode = "ODO";
  }

$fuelcost = $dist / $fuel;
$fuel_total = $fuel + $data['fuel_total'];
$fuelcost_total = $dist_total / $fuel_total;

$body = sprintf("[給油記録]：%.1fkm走行し、%.1fL給油しました。燃費%.2fkm/L (前回比:%+.2fkm/L)\n[総走行距離:%.1fkm (前回比:%+.2fkm) 累計燃費:%.2fkm/L (前回比:%+.2fkm/L)]\n[System：%sモードで記録しました。]\n",
                  $dist,
                  $fuel,
                  $fuelcost,
                  $fuelcost - $data['fuelcost_last'],
                  $dist_total + $data['dist_add'],
                  $dist,
                  $fuelcost_total,
                  $fuelcost_total-$data['fuelcost_total'],
                  $mode
                 );

$SQL = sprintf("UPDATE fuelcost_save SET dist_total = $dist_total,
                                           fuel_total = $fuel_total,
                                           fuelcost_total= $fuelcost_total,
                                           fuelcost_last = $fuelcost
                                           WHERE userid = $uid"
                );

$mysqli->query($SQL);

echo $body;

$mysqli->close();

function errmsg($text){
  echo "[Error]{$text}",PHP_EOL;
}
