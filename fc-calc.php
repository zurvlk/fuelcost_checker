<?php

date_default_timezone_set("Asia/Tokyo");

$mysqli = new mysqli('localhost', 'root', 'root', 'fc_calc');
if ($mysqli->connect_error) {
    echo $mysqli->connect_error;
    exit();
} else {
    $mysqli->set_charset("utf8");
}

$userid = 1;
$dist = isset($argv[1])? (double)$argv[1]: null;
$fuel = isset($argv[2])? (double)$argv[2]: null;

if(($dist <= 0) or ($fuel <= 0)){
  errmsg("入力された値が不正です");
  exit;
}

$recordSet = mysqli_query("SELECT * FROM fuelcost_save WHERE id = $userid");
$data = mysqli_fetch_assoc($recordSet);


  if($dist <= $data['dist_total']){
    $totaldist = $dist + $data['dist_total'];
    $mode = "TRIP";
  }else{
    $totaldist = $dist;
    $dist = $dist - $data['dist_total'];
    $mode = "ODO";
  }

  $fuelcost = $dist / $fuel;
  $totalfuel = $fuel + $data['fuel_total'];
  $fuelcost_total = $totaldist / $totalfuel;

  $body = sprintf("[給油記録]：%.1fkm走行し、%.1fL給油しました。燃費%.2fkm/L (前回比:%+.2fkm/L)\n[総走行距離:%.1fkm (前回比:%+.2fkm) 累計燃費:%.2fkm/L (前回比:%+.2fkm/L)]\n[System：%sモードで記録しました。]\n",
                  $dist,
                  $fuel,
                  $fuelcost,
                  $fuelcost - $data['fuelcost_last'],
                  $dist,
                  $totaldist + $data['dist_add'],
                  $fuelcost_total,
                  $data['fuelcost_total']-$fuelcost_total,
                  $mode
                 );

  $SQL = sprintf("UPDATE fuelcost_save SET dist = $totaldist,
                                           fuel = $totalfuel,
                                           fuelcost_total= $fuelcost_total,
                                           fuelcost_last = $fuelcost,
                                           WHERE id = $userid"
                );

  echo $body;
  mysqli_query($SQL);

  return $body;

function errmsg($text){
  echo "[Error]{$text}",PHP_EOL;
}
