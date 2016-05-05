<?php
error_reporting(E_ALL ^ E_NOTICE);
/* $mysql=new PDO('mysql:host=localhost;dbname=cityuit','root','q123456'); */
/* $res = $mysql->query('select * from library_books'); */
/* foreach($res->fetchAll() as $row){ */
/*     print "$row[title] is $row[auther] write"; */
/*     print "\n"; */
/* } */

/* $max = 170000;         //最大数据估计值170000 */
/* $workers = 17; */
 
/* $pids = array(); */
/* for($i = 0; $i < $workers; $i++){ */
/*     $pids[$i] = pcntl_fork(); */
/*     switch ($pids[$i]) { */
/*     case -1: */
/*         print "fork error\n"; */
/*         exit; */
/*     case 0: */
/*         $minno = $max / $workers * $i; */
/*         $maxno = $max / $workers * ($i+1); */
/*         main_action($minno, $maxno); */
/*         exit; */
/*     default: */
/*         break; */
/*     } */
/* } */

/* foreach ($pids as $i => $pid) {     //主进程等待所有子进程都结束了才退出 */
/*     if($pid) { */
/*         pcntl_waitpid($pid, $status); */
/*     } */
/* } */

function main_action($minno, $maxno){
    print "$minno===========$maxno\n";
    for($no=$minno; $no<$maxno; $no++){
        $noStr = str_pad($no, 10, "0", STR_PAD_LEFT);
        $bookmes = check_books($noStr);    //固定10字节长度，用0填充
        if($bookmes['res'] == 201){
            print "no=> $no ,mes=> no book for $bookmes[mes]\n";
        }else if($bookmes['res'] == 501){
            save_no($no);            
            print "no=> $no ,mes=> no book for $bookmes[mes]\n";
        }else{ 
            $insert = save_book($bookmes);
            if($insert['result'] === FALSE || $insert['result'] == 0){    //如果存储失败，和超时同样处理
                save_no($no);            
                print "no=> $no ,mes=> no book for mysql error\n";
            }else{
                print "no=> $no ,book for line $insert[lineId]\n";
            }
        }
    }
}

main_test();

function main_test(){
    $no = "0000090946";
    $res = check_books($_GET['id']);
    /* save_book($res); */
    print_r($res);
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
    }

    $contents = preg_replace("/([\r\n|\n|\t| ]+)/",'',$content);  //为更好地避开换行符和空格等不定因素的阻碍，有必要先清除采集到的源码中的换行符、空格符和制表符
    $contents = html_entity_decode($contents);     //将&#x0020;字符转中文
    $contents = preg_replace('/<\/a>/','',$contents);   //先提前将</a>给删了，免去判断
        echo $contents;

    //先确定有没有这本书，然后去解析书的信息

    $preg1 = '/此书刊可能正在订购中或者处理中/';
    if(preg_match($preg, $contents)){
        return array("res"=>201,"mes"=>"under");   //表示没有这本，并且是因为下面匹配为空导致，返回就直接终止
    }

    $bookarr = array();        
    $preg2 = '/<table.*索书号.*<\/table>/U';
    if(preg_match($preg2, $contents, $out2)){
        $preg3 = '/<tr.*>(.*)<\/tr>/U';
        if(preg_match_all($preg3, $out2[0], $out3)){
            $preg4 = '/<td.*>(.*)<\/td>/U';
            for($i=1;$i<count($out3[1]);$i++){
                if(preg_match_all($preg4,$out3[1][$i],$out4)){
                    $bookarr[] = $out4[1];    //将每项最后结果放入数组
                }
            }
        }
    }
    if(!empty($bookarr)){
        for($i=0;$i<count($bookarr);$i++){
            $search = $bookarr[$i][0];                     //变量表示索书号

            $preg5 = '/>.*库.*库(.*)</U';
            preg_match($preg5, $bookarr[$i][3], $out5);
            $place = $out5[1];                        //变量表示馆藏位置

            $preg6 = '/>(.*)</U';
            preg_match($preg6, $bookarr[$i][4], $out6);
            $state = $out6[1] == '可借' ? '可借' : '已借出';           //变量表示是否可借
        }
    }else{
        return array("res"=>201,"mes"=>"under");   //表示没有这本，并且是因为下面匹配为空导致，返回就直接终止
    }

    $preg7 = '/题名\/责任者:<\/dt><dd><a.*>(.*)<\/dd>.*出版发行项:<\/dt><dd>.*:(.*),(.*)<\/dd>.*<dt>ISBN/U';
    $preg8 = '/题名\/责任者:<\/dt><dd><a.*>(.*)\/(.*)<\/dd>.*出版发行项:<\/dt><dd>.*:(.*),(.*)<\/dd>.*<dt>ISBN/U';
    if(preg_match($preg7, $contents, $out7)){
        if(preg_match('/\//',$out7[1])){             //某些存在多个/字符的问题解决
            $temp = explode('/', $out7[1], substr_count($out7[1],'/')+1);
            $title = $temp[0];
            $auther = $temp[1];
        }else{
            $title = $out7[1];
        }
        $press = $out7[2];              //变量表示出版社
        $time = $out7[3];           //变量表示出版时间
        /* print_r($out7); */
        return array("res"=>200, "title"=>$title, "auther"=>$auther, "press"=>$press, "time"=>$time, "search"=>$search, "place"=>$place, "state"=>$state);
    }else if(preg_match($preg8, $contents, $out8)){
        $title = $out8[1];   
        $auther = preg_replace('/=.*/','',$out8[2]);   //某些情况存在=解释，变量表示著作
        if(preg_match('/\//',$auther)){             //某些存在多个/字符的问题解决
            $temp = explode('/', $auther, substr_count($auther,'/')+1);
            $title .= '/'.$temp[0];
            $auther = $temp[1];
        }
        $press = $out8[3];              //变量表示出版社
        $time = $out8[4];           //变量表示出版时间
        /* echo "书名：$title<br />作者：$auther<br />出版社：$press<br />出版时间：$time<br /><br />索书号：$search<br />馆藏地：$place<br />状态：$state<br />"; */
        return array("res"=>200, "title"=>$title, "auther"=>$auther, "press"=>$press, "time"=>$time, "search"=>$search, "place"=>$place, "state"=>$state);
    }else{
        return array("res"=>201,"mes"=>"top");   //表示没有这本，并且是因为上面匹配为空导致，返回就直接终止
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
    curl_setopt($oCurl, CURLOPT_TIMEOUT,30);   //只需要设置一个秒的数量就可以  
    $sContent = curl_exec($oCurl);
    $aStatus = curl_getinfo($oCurl);
    curl_close($oCurl);
    if(intval($aStatus["http_code"])==200){
        return $sContent;
    }else{
        return false;
    }
}
