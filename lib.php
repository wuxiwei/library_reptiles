<?php
error_reporting(E_ALL ^ E_NOTICE);
/* $mysql=new PDO('mysql:host=localhost;dbname=cityuit','root','q123456'); */
/* $res = $mysql->query('select * from library_books'); */
/* foreach($res->fetchAll() as $row){ */
/*     print "$row[title] is $row[auther] write"; */
/*     print "\n"; */
/* } */

/* for ($i=0; $i<3; ++$i){ */
/*       $pid = pcntl_fork(); */
/*        if ($pid == -1){ */
/*              die ("cannot fork" ); */
/*       } else if ($pid > 0){ */
/*              echo "parent continue \n"; */
/*              /1* pcntl_wait($status); *1/ */  
/*              for ($k=0; $k<2; ++$k){ */
/*                   beep(); */
/*             } */
/*       } else if ($pid == 0){ */
/*              echo "child start, pid ", getmypid(), "\n" ; */
/*              for ($j=0; $j<5; ++$j){ */
/*                   beep(); */
/*             } */
/*              exit ; */
/*       } */
/* } */

main_action();

function main_action(){
    $no = "0000128831";
    $bookmes = check_books($no);
    if($bookarr['res'] == 201){
        print "no=> $no ,mes=> no book for $bookmes[mes]";
    }else if($bookmes['res'] == 501){
        save_no($no);            
        print "no=> $no ,mes=> no book for $bookmes[mes]";
    }else{ 
        $insert = save_book($bookmes);
        if($insert['result'] === FALSE || $insert['result'] == 0){    //如果存储失败，和超时同样处理
            save_no($no);            
            print "no=> $no ,mes=> no book for mysql error";
        }else{
            print "no=> $no ,book for line $insert[lineId]";
        }
    }
}

function save_book($bookmes){
    $pdo=new PDO('mysql:host=localhost;dbname=cityuit','root','q123456');
    $pdo->query('set names utf8');//设置字符集
    $strSql = "INSERT INTO `library_books`(`title`, `auther`, `press`, `time`, `search`, `place`, `state`) VALUES ('$bookmes[title]','$bookmes[auther]','$bookmes[press]','$bookmes[time]','$bookmes[search]','$bookmes[place]','$bookmes[state]')";
    $result = $pdo->exec($strSql);//返回影响了多少行数据
    $lineId = $pdo->lastInsertId();//返回刚插入的id(的自增id)
    return array("result"=>$result, "lineId"=>$lineId);
}

function save_no($no){
    $pdo=new PDO('mysql:host=localhost;dbname=cityuit','root','q123456');
    $pdo->query('set names utf8');//设置字符集
    $strSql = "INSERT INTO `timeout_no`(`no`) VALUES ('$no')";
    $pdo->exec($strSql);//返回影响了多少行数据
    /* $lineId = $pdo->lastInsertId();//返回刚插入的id(的自增id) */
    /* return $lineId; */
}

function check_books($no){
    $content = http_get("http://210.30.108.79/opac/item.php?marc_no=$no");

    if(!$content){
        return array("res"=>501,"mes"=>"timeout");   //表示没有这本，并且是因为下面匹配为空导致，返回就直接终止
        exit;
    }

    $contents = preg_replace("/([\r\n|\n|\t| ]+)/",'',$content);  //为更好地避开换行符和空格等不定因素的阻碍，有必要先清除采集到的源码中的换行符、空格符和制表符
    $contents = html_entity_decode($contents);     //将&#x0020;字符转中文

    //先确定有没有这本书，然后去解析书的信息
    $bookarr = array();        
    $preg2 = '/<table.*索书号.*<\/table>/U';
    if(preg_match($preg2, $contents, $out)){
        $preg2 = '/<tr.*>(.*)<\/tr>/U';
        if(preg_match_all($preg2, $out[0], $out)){
            $preg2 = '/<td.*>(.*)<\/td>/U';
            for($i=1;$i<count($out[1]);$i++){
                if(preg_match_all($preg2,$out[1][$i],$res)){
                    $bookarr[] = $res[1];    //将每项最后结果放入数组
                }
            }
        }
    }
    if(!empty($bookarr)){
        for($i=0;$i<count($bookarr);$i++){
            $search = $bookarr[$i][0];                     //变量表示索书号

            $preg3 = '/>.*库.*库(.*)</U';
            preg_match($preg3, $bookarr[$i][3], $out);
            $place = $out[1];                        //变量表示馆藏位置

            $preg3 = '/>(.*)</U';
            preg_match($preg3, $bookarr[$i][4], $out);
            $state = $out[1] == '可借' ? '可借' : '已借出';           //变量表示是否可借
        }
    }else{
        return array("res"=>201,"mes"=>"under");   //表示没有这本，并且是因为下面匹配为空导致，返回就直接终止
        exit;
    }

    $preg = '/题名\/责任者:<\/dt><dd><a.*>(.*)[^<]\/(.*)<\/dd>.*出版发行项:<\/dt><dd>.*:(.*),(.*)<\/dd>.*<dt>ISBN/U';
    if(preg_match($preg, $contents, $out)){
        $title = preg_replace('/<\/a>?/','',$out[1]);   //某些情况不存在>符号，变量表示书名
        $auther = preg_replace('/ =.*/','',$out[2]);   //变量表示著作
        $press = $out[3];              //变量表示出版社
        $time = $out[4];           //变量表示出版时间
        /* echo "书名：$title<br />作者：$auther<br />出版社：$press<br />出版时间：$time<br /><br />索书号：$search<br />馆藏地：$place<br />状态：$state<br />"; */
        return array("res"=>200, "title"=>$title, "auther"=>$auther, "press"=>$press, "time"=>$time, "search"=>$search, "place"=>$place, "state"=>$state);
    }else{
        return array("res"=>201,"mes"=>"top");   //表示没有这本，并且是因为上面匹配为空导致，返回就直接终止
        exit;
    }
}

/**
 * GET 请求
 * @param string $url
 */
function http_get($url){
    $oCurl = curl_init();
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($oCurl, CURLOPT_TIMEOUT,20);   //只需要设置一个秒的数量就可以  
    $sContent = curl_exec($oCurl);
    $aStatus = curl_getinfo($oCurl);
    curl_close($oCurl);
    if(intval($aStatus["http_code"])==200){
        return $sContent;
    }else{
        return false;
    }
}
