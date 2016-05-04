<?php
$mysql=new PDO('mysql:host=localhost;dbname=cityuit','root','q123456');
$res = $mysql->query('select * from library_books');
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

$content = http_get("http://210.30.108.79/opac/item.php?marc_no=0000091755");

$contents = preg_replace("/([\r\n|\n|\t| ]+)/",'',$content);  //为更好地避开换行符和空格等不定因素的阻碍，有必要先清除采集到的源码中的换行符、空格符和制表符
$preg = '/题名\/责任者:<\/dt>.*<a.*>(.*)<\/a>\/(.*)<\/dd>.*<dd>.*:(.*),(.*)<\/dd>.*<dt>ISBN及定价:/U';
if(preg_match($preg, $contents, $out)){
    echo "书名：$out[1]<br />作者：$out[2]<br />出版社：$out[3]<br />出版时间：$out[4]<br />";
}

$bookarr = array();
$preg2 = '/<table.*索书号.*<\/table>/U';
if(preg_match($preg2, $contents, $out)){
    $preg2 = '/<tr.*>(.*)<\/tr>/U';
    if(preg_match_all($preg2, $out[0], $out)){
        $preg2 = '/<td.*>(.*)<\/td>/U';
        for($i=1;$i<count($out[1]);$i++){
            if(preg_match_all($preg2,$out[1][$i],$res)){
                $bookarr[] = $res[1];
            }
        }
    }
}
if(!empty($bookarr)){
    for($i=0;$i<count($bookarr);$i++){
        $search = $bookarr[$i][0];

        $preg3 = '/>基本书库—基本书库(.*)</U';
        preg_match($preg3, $bookarr[$i][3], $out);
        $place = $out[1];

        $preg3 = '/>(.*)</U';
        preg_match($preg3, $bookarr[$i][4], $out);
        $state = $out[4] == '可借' ? '可借' : '已借出';
    }
echo "索书号：$search<br />馆藏地：$place<br />状态：$state<br />";
}
/* var_dump($bookarr); */
        /* $contents = preg_replace("/([\r\n|\n|\t| ]+)/",'',$allHtml);  //为更好地避开换行符和空格等不定因素的阻碍，有必要先清除采集到的源码中的换行符、空格符和制表符 */
        /* $preg = '/<th>姓名.*table/U'; */
        /* if(preg_match($preg, $contents, $out)){    //如果存在匹配，证明查询成功，并且将table内容存入$out中 */
        /*     $preg = '/<td.*>(.*)<\/td>/U'; */
        /*     preg_match_all($preg, $out[0], $out1);   //$out1保存每项值,惟独成绩一项需要特殊处理 */
        /*     $name = $out1[1][0]; */
        /*     $school = $out1[1][1]; */
        /*     $type = $out1[1][2]; */
        /*     $num = $out1[1][3]; */
        /*     $time = $out1[1][4]; */

        /*     $preg = '/：<\/span>(.*)</U'; */
        /*     preg_match_all($preg, $out1[0][5], $out2);   //从$out[0][5]获得个部分成绩，存入$out[2]中 */
        /*     $tlScore = $out2[1][0]; */
        /*     $ydScore = $out2[1][1]; */
        /*     $xzpyScore = $out2[1][2]; */
/**
 * GET 请求
 * @param string $url
 */
function http_get($url){
    $oCurl = curl_init();
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
    $sContent = curl_exec($oCurl);
    $aStatus = curl_getinfo($oCurl);
    curl_close($oCurl);
    if(intval($aStatus["http_code"])==200){
        return $sContent;
    }else{
        return false;
    }
}
