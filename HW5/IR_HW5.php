
<?php
require_once('simple_html_dom.php');
error_reporting(-1);
ini_set('auto_detect_line_endings', TRUE);

header('Content-Type: text/html; charset=utf-8');
$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$query = strtoLower($query);
$hid = isset($_REQUEST['hid']) ? $_REQUEST['hid'] : false;
$results = false;
$row = 1;
$data1 =array();
ini_set('auto_detect_line_endings', TRUE);
function getLinkfromMap($input) {
    $csv = array_map('str_getcsv', file('map.csv'));
    foreach($csv as $value) {
        if ($value[0] == $input) {
            return $value[1];
        }
    }
}
if ($query)
{
    require_once('solr-php-client/Apache/Solr/Service.php');

    ini_set('memory_limit', '-1');
    $solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample/');
    $correct = true;
    if($hid == "no") {
        include 'SpellCorrector.php';
        $queries = explode(" ", $query);
        $spelled = "";
        foreach($queries as $q) {
            $spell = strtoLower(SpellCorrector::correct($q));
            if (strcmp($q, $spell) != 0) {
                $correct = false;
                $spelled = $spelled . ' ' . $spell;
            }else {
                $spelled = $spelled . ' ' . $q;
            }
        }
        $temp_query = $query;
        $query = trim($spelled);
    }
    if ($hid == "yes") {
        $query = trim($query);
    }

    if (get_magic_quotes_gpc() == 1) {
        $query = stripslashes($query); }

    try
    {
        $algorithm = isset($_GET['algo']) ? $_GET['algo'] : false;
        if ($algorithm == "Default Algorithm") {
            $results = $solr->search($query, 0, $limit);
        }
        else if($algorithm ==="PageRank Algorithm"){
            $results = $solr->search($query, 0, $limit,$arrayName = array('sort' => 'pageRankFile desc'));
        }
    }
    catch (Exception $e) {

        die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
    }
}
?> <html>
<head>
    <title>Solr Search </title>
    <link href="http://code.jquery.com/ui/1.10.4/themes/ui-lightness/jquery-ui.css" rel="stylesheet"></link>
</head> <body >
<form accept-charset="utf-8" method="get" id ="form">
    <label for="q">Search:</label><table>
        <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
        <input id="hid" type="hidden" name="hid" value="no">
        <input type="submit"/></table><table></table>
    <input type="radio" name="algo" value = "PageRank Algorithm" <?php if(!isset($_GET['algo']) || (isset($_GET['algo']) && $_GET['algo'] =="PageRank Algorithm")) echo 'checked="checked"';?>  id="pagerank"> PageRank 
    <input type="radio" name="algo" value ="Default Algorithm" <?php if(!isset($_GET['algo']) || (isset($_GET['algo']) && $_GET['algo'] =="Default Algorithm")) echo 'checked="checked"';?>  id="default"> Lucene
    </table></form> <?php
// display results
if ($results) {
    $total = (int) $results->response->numFound;
    $start = min(1, $total);
    $end = min($limit, $total);
    if (!$correct) {
        ?>
        <div> Showing results for <?php echo $spelled; ?> </div>
        <br />
        <div>
            Show results for <button onclick ="myfunc('<?php echo $temp_query; ?>');" style ="background:none;outline:none;border:none;font-size:14px;color:blue;border-bottom:1px solid"> <?php echo $temp_query?></button>
        </div>
        <br />
        <?php
    }
    ?>
    <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
    <ol>
        <?php
        // iterate result documents
        foreach ($results->response->docs as $doc)
        { ?>
            <?php
// iterate document fields / values
            $id = $doc -> og_url;
            $indexMap = substr($id, strpos($id,"/")+1);
            $title = $doc -> title;
            $fileName = $doc->resourcename;
            $MapFileIndex = substr($fileName, strrpos($fileName, '/') + 1);
            $size = ((int)$doc -> stream_size)/ 1000 ;
            ?>
            <table class ="jsonTable">
                <li>

                    <p><a href = "<?php if($id != '') {echo $id;} else {echo getLinkfromMap($MapFileIndex);}?>"> Link </a> </br>
                        Title : <?php if($title != ''){echo $title;} else{ echo "N/A";} ?> </br>
                        ID : <?php echo $fileName;?></br>
                        Link : <?php if($id != '') {echo $id;} else { echo getLinkfromMap($MapFileIndex); }?></br>

                        Snippet :<?php
                        $fileContent = file_get_html($fileName, $use_include_path = false, $context=null, $offset = -1, $maxLen=-1, $lowercase = true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT)->plaintext;
                        $counter =0;
                        $sentenceArray = explode(".", $fileContent);
                        $flag = "false";
                        $length = count($sentenceArray);
                        for ($i = 0; $i < $length; $i++) {
                            //echo $length;
                            $value = $sentenceArray[$i];
                            
                            if(stripos($value,$query)!== false) {
                                $stopWordArray = array("a,able,about,above,abst,accordance,according,accordingly,across,act,actually,added,adj,\
        affected,affecting,affects,after,afterwards,again,against,ah,all,almost,alone,along,already,also,although,\
        always,am,among,amongst,an,and,announce,another,any,anybody,anyhow,anymore,anyone,anything,anyway,anyways,\
        anywhere,apparently,approximately,are,aren,arent,arise,around,as,aside,ask,asking,at,auth,available,away,awfully,\
        b,back,be,became,because,become,becomes,becoming,been,before,beforehand,begin,beginning,beginnings,begins,behind,\
        being,believe,below,beside,besides,between,beyond,biol,both,brief,briefly,but,by,c,ca,came,can,cannot,can't,cause,causes,\
        certain,certainly,co,com,come,comes,contain,containing,contains,could,couldnt,d,date,did,didn't,different,do,does,doesn't,\
        doing,done,don't,down,downwards,due,during,e,each,ed,edu,effect,eg,eight,eighty,either,else,elsewhere,end,ending,enough,\
        especially,et,et-al,etc,even,ever,every,everybody,everyone,everything,everywhere,ex,except,f,far,few,ff,fifth,first,five,fix,\
        followed,following,follows,for,former,formerly,forth,found,four,from,further,furthermore,g,gave,get,gets,getting,give,given,gives,\
        giving,go,goes,gone,got,gotten,h,had,happens,hardly,has,hasn't,have,haven't,having,he,hed,hence,her,here,hereafter,hereby,herein,\
        heres,hereupon,hers,herself,hes,hi,hid,him,himself,his,hither,home,how,howbeit,however,hundred,i,id,ie,if,i'll,im,immediate,\
        immediately,importance,important,in,inc,indeed,index,information,instead,into,invention,inward,is,isn't,it,itd,it'll,its,itself,\
        i've,j,just,k,keep,keeps,kept,kg,km,know,known,knows,l,largely,last,lately,later,latter,latterly,least,less,lest,let,lets,like,\
        liked,likely,line,little,'ll,look,looking,looks,ltd,m,made,mainly,make,makes,many,may,maybe,me,mean,means,meantime,meanwhile,\
        merely,mg,might,million,miss,ml,more,moreover,most,mostly,mr,mrs,much,mug,must,my,myself,n,na,name,namely,nay,nd,near,nearly,\
        necessarily,necessary,need,needs,neither,never,nevertheless,new,next,nine,ninety,no,nobody,non,none,nonetheless,noone,nor,\
        normally,nos,not,noted,nothing,now,nowhere,o,obtain,obtained,obviously,of,off,often,oh,ok,okay,old,omitted,on,once,one,ones,\
        only,onto,or,ord,other,others,otherwise,ought,our,ours,ourselves,out,outside,over,overall,owing,own,p,page,pages,part,\
        particular,particularly,past,per,perhaps,placed,please,plus,poorly,possible,possibly,potentially,pp,predominantly,present,\
        previously,primarily,probably,promptly,proud,provides,put,q,que,quickly,quite,qv,r,ran,rather,rd,re,readily,really,recent,\
        recently,ref,refs,regarding,regardless,regards,related,relatively,research,respectively,resulted,resulting,results,right,run,s,\
        said,same,saw,say,saying,says,sec,section,see,seeing,seem,seemed,seeming,seems,seen,self,selves,sent,seven,several,shall,she,shed,\
        she'll,shes,should,shouldn't,show,showed,shown,showns,shows,significant,significantly,similar,similarly,since,six,slightly,so,\
        some,somebody,somehow,someone,somethan,something,sometime,sometimes,somewhat,somewhere,soon,sorry,specifically,specified,specify,\
        specifying,still,stop,strongly,sub,substantially,successfully,such,sufficiently,suggest,sup,sure,t,take,taken,taking,tell,tends,\
        th,than,thank,thanks,thanx,that,that'll,thats,that've,the,their,theirs,them,themselves,then,thence,there,thereafter,thereby,\
        thered,therefore,therein,there'll,thereof,therere,theres,thereto,thereupon,there've,these,they,theyd,they'll,theyre,they've,\
        think,this,those,thou,though,thoughh,thousand,throug,through,throughout,thru,thus,til,tip,to,together,too,took,toward,towards,\
        tried,tries,truly,try,trying,ts,twice,two,u,un,under,unfortunately,unless,unlike,unlikely,until,unto,up,upon,ups,us,use,used,\
        useful,usefully,usefulness,uses,using,usually,v,value,various,'ve,very,via,viz,vol,vols,vs,w,want,wants,was,wasn't,way,we,wed,\
        welcome,we'll,went,were,weren't,we've,what,whatever,what'll,whats,when,whence,whenever,where,whereafter,whereas,whereby,wherein,\
        wheres,whereupon,wherever,whether,which,while,whim,whither,who,whod,whoever,whole,who'll,whom,whomever,whos,whose,why,widely,\
        willing,wish,with,within,without,won't,words,world,would,wouldn't,www,x,y,yes,yet,you,youd,you'll,your,youre,yours,yourself,\
        yourselves,you've,z,zero"
                                );
                                $stopWordLength = count($stopWordArray);
                                for($i=0; $i <$stopWordLength;$i++){
                                    if (stripos($value,$stopWordArray[$i]) !== false ) {
                                        //echo $stopWordArray[$i];
                                        $value = str_ireplace($stopWordArray[$i]," ",$value);
                                      //echo $stopWordLength;
                                    }
                                }				
	$look = explode(' ',$value);

	foreach($look as $find){
    	if(strpos($find, $query) !== false) {
        if(!isset($highlight)){ 
            $highlight[] = $find;
        } else { 
            if(!in_array($find,$highlight)){ 
                $highlight[] = $find;
            } 
        }
    	}   
		} 

	if(isset($highlight)){ 
    	foreach($highlight as $replace){
        $value = str_replace($replace,'<b>'.$replace.'</b>',$value);
    	} 
	} 

	echo $value. "...";
                                $flag ="true";
                                break;
                            }
                            
                        }
                        if ( $flag == "false")  {
                            
                                echo "No snippet for given query";
                            
                        }
                        ?>
                        <!-- Result : --><?php /*print_result(); */?>
                    </p>


                </li> </table> <?php
        } ?>
    </ol>
<?php }
?>
<script src="http://code.jquery.com/jquery-1.10.2.js"></script>
<script src="http://code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
<script src="index.js"></script>
<script src="stemmer.js"></script>
</body> </html>
