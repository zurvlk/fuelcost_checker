<?php
date_default_timezone_set("Asia/Tokyo");

//デバッグ関連
ini_set('display_errors',true);
error_reporting(E_ALL);

//TwitterOAuth読み込み
require 'twitteroauth/autoload.php';
require __DIR__ . '/twitteroauth/src/TwitterOAuth.php';

//個人設定ファイル読み込み
include 'conf.php';

use Abraham\TwitterOAuth\TwitterOAuth;

//オブジェクト生成
$toa = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);
//ユーザー指定　TL取得
$user = 'username';

$timeline = $toa->get('statuses/user_timeline', array('screen_name' => $user));


//表示
foreach ($timeline as $i => $tweet) {
    //echo "$i: $tweet->text" . PHP_EOL;
    if($fcc = strstr($tweet->text,'fcc')){
      $arg = explode(" ",$fcc);

      //SQL接続関連
      $mysqli = new mysqli('localhost', 'root', 'root', 'fc_calc');
      if ($mysqli->connect_error) {
          echo $mysqli->connect_error;
          exit();
      } else {
          $mysqli->set_charset("utf-8");
      }

      $uid = 1;
      $dist = $arg[1];
      $fuel = $arg[2];



      if(($dist <= 0) or ($fuel <= 0)){
        $req = $toa -> post("statuses/update", array("status"=> "[fcc.Error]入力された値が不正です" ));
        exit;
      }


      //SQL実行
      $sql = "SELECT * FROM fuelcost_save WHERE userid = $uid";
      $result = $mysqli->query($sql);
      $data = $result->fetch_assoc();


      //ODO,TRIPモード判定
        if($dist <= $data['dist_total']){
          $dist_total = $dist + $data['dist_total'];
          $mode = "TRIP";
        }else{
          $dist_total = $dist;
          $dist = $dist - $data['dist_total'];
          $mode = "ODO";
        }

      //前回と距離給油量が同じのとき、同一ツイートの再読み込みと判定、処理せず終了
      if($dist == $data['dist_last'] and $fuel == $data['fuel_last']) exit;

      //燃費計算
      $fuelcost = $dist / $fuel;
      $fuel_total = $fuel + $data['fuel_total'];
      $fuelcost_total = $dist_total / $fuel_total;

      //出力
      $body = sprintf("[給油記録]：%.1fkm走行し、%.1fL給油しました。燃費%.2fkm/L (前回比:%+.2fkm/L)\n[総走行距離:%.1fkm (前回比:%+.2fkm) 累計燃費:%.2fkm/L (前回比:%+.2fkm/L)]\n[Mode：%s]\n",
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

      //保存
      $SQL = sprintf("UPDATE fuelcost_save SET dist_total = $dist_total,
                                                 dist_last = $dist,
                                                 fuel_total = $fuel_total,
                                                 fuel_last = $fuel,
                                                 fuelcost_total= $fuelcost_total,
                                                 fuelcost_last = $fuelcost
                                                 WHERE userid = $uid"
                      );

      $mysqli->query($SQL);
      $mysqli->close();
      echo $body . PHP_EOL;

      $req = $toa -> post("statuses/update", array("status"=> $body ));
      exit;

    }
}

?>
