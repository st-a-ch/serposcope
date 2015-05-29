<?php
/**
 * Serposcope - An open source rank checker for SEO
 * http://serphacker.com/serposcope/
 * 
 * @link http://serphacker.com/serposcope Serposcope
 * @author SERP Hacker <pierre@serphacker.com>
 * @license http://creativecommons.org/licenses/by-nc-sa/3.0/legalcode CC-BY-NC-SA
 * 
 * Redistributions of files must retain the above notice.
 */
if(!file_exists('inc/config.php')){
    header("Location: install/",TRUE,302);
    die();
}
require('inc/config.php');
include('inc/define.php');
include('inc/common.php');
include('inc/user.php');

if(isset($_GET['id'])){
    $res = null;
    if(is_numeric($_GET['id'])){
        $res=$db->query("SELECT * FROM  `".SQL_PREFIX."run` WHERE idRun = ".intval($_GET['id']));
    }else if( $_GET['id'] == "last" ){
        $res=$db->query("SELECT * FROM  `".SQL_PREFIX."run` ORDER BY idRun DESC LIMIT 1");
    }
    if($res && $run=  mysql_fetch_assoc($res)){
        if($run['dateStop'] == null){
            if(!is_pid_alive($run['pid'])){
                // in this case the pid have been killed externally 
                // from command line or max execution time reached
                $db->query(
                    "UPDATE `".SQL_PREFIX."run` SET haveError=1, dateStop=now(), ".
                    "logs=CONCAT(logs,'ERROR ABNORMAL TERMINATION : process may have been killed or reached max execution time\n') ".
                    "WHERE idRun = ".$run['idRun']
                );
            }
        }
    }
    
    if(is_numeric($_GET['id'])){
        $res=$db->query("SELECT idRun,dateStart,dateStop,pid,haveError,timediff(dateStop,dateStart) diff,logs FROM `".SQL_PREFIX."run` WHERE idRun = ".intval($_GET['id']));
    }else if( $_GET['id'] == "last" ){
        $res=$db->query("SELECT idRun,dateStart,dateStop,pid,haveError,timediff(dateStop,dateStart) diff,logs FROM `".SQL_PREFIX."run` ORDER BY idRun DESC LIMIT 1");
    }    
    if($res && $run=  mysql_fetch_assoc($res)){
        header('Content-Type: text/plain');
        if($run['dateStop'] == null){
            echo "Cron is still running (PID: ".$run['pid']." started: ".$run['dateStart']."), press F5 to Refresh the log\n";
        }else{
            if($run['haveError']){
                echo "Run done in ".$run['diff']." with error (PID: ".$run['pid']." started: ".$run['dateStart'].")\n";
            }else{
                echo "Run successfully done in ".$run['diff']." (PID: ".$run['pid']." started: ".$run['dateStart'].")\n";
            }
        }
        echo "-----\n";
        echo $run['logs'];
        echo "-----\n";
        if($run['dateStop'] == null){
            echo "Press F5 to Refresh the log\n";
        }else{
            echo "Process terminated, end of log\n";
        }        
        
        die();
    }    
}

$error_msg="";
$info_msg="";
if(isset($_GET['did'])){
    if($db->query("DELETE from `".SQL_PREFIX."run`  WHERE idRun = ".intval($_GET['did'])." AND dateStop is not null") && mysql_affected_rows() > 0){
        $info_msg = "Run ".intval($_GET['did'])." deleted";
    }else{
        $error_msg = "Can't delete run ".intval($_GET['did']).". It doesn't exists or job is still running.";
    }
}

include("inc/header.php");
if (!empty($error_msg)) {
    echo "<div class='CloseScanInfo alert alert-error'>$error_msg<span style='float:right;'>[<a id='CloseScanInfo' href='#'>Zamknij</a>]</span></div>\n";
}
if (!empty($info_msg)) {
    echo "<div class='CloseScanInfo alert alert-info'>$info_msg<span style='float:right;'>[<a id='CloseScanInfo' href='#'>Zamknij</a>]</span></div>\n";
}
?>
<script>
    function warningDeleteRun(){
        return confirm("It will delete all the positions checked during this run, continue ?");
    }
</script>
<script>
        $( "#btn_1" ).css("border","solid 2px #D64B46");
        $( "#btn_1" ).css("border-radius","5px");
        $(document).ready(function() { setTimeout(function(){ hMsg(); }, 5000); });
        $( "#CloseScanInfo" ).click(function() { hMsg(); });
        function hMsg() {$( ".CloseScanInfo" ).slideUp(900);}
<?php
if($adminAcces) {echo '

	    $(function() {
	    $(".btn-force-run").mouseover(function() {
	    	 $(".id_" + $(this).attr("data-id") ).addClass("selected");
	    });
	    $(".btn-force-run").mouseout(function() {
	    	 $(".id_" + $(this).attr("data-id") ).removeClass("selected");
	    });
	    $(".btn-force-run").click(function(){
         idtest =($(this).attr("data-id"));
         nrtest =($(this).attr("data-nr"));
            var canRun=false;
            $.ajax({
                type: "POST",
                url: "ajax.php",
                data: {
                action: "is_running"
                }
            }).done(function(rawdata){
                data = JSON.parse(rawdata);
                if(data !== null){
                    if(data.running !== undefined){
                        if(!data.running){
                            canRun=true;
                        }
                    }else{
                        alert("unknow error [2]");   
                    }
                }else{
                    alert("unknow error [1]");
                }
                if(!canRun){
                    alert("A job is already running");
                    document.location.href = "index.php";
                    return;
                }
                if(!confirm("Zostanie uruchomiony skrypt skanowania " + nrtest + "\r\n\r\nCzy napewno chcesz kontynuowaæ?")){
                    return;
                }
                var imgRun = new Image();
                imgRun.src = "cron.php?idGroup=" + idtest;

                // lock everything for 3 sec
                $.blockUI({ message: "<h1>Skanowanie...</h1>" });
                setTimeout(function(){
                    document.location.href = "/";
                }, 2000);
            });
        });
    });';
}
?>
</script>
<div>
    <table class='table table-condensed table-bordered table-log' >
        <thead>
        	   <?php
        	       if(!$ID && !$adminAcces) {echo 'ustaw domyœlny projekt!';}
        	       if(($set->log || !$adminAcces) || ($adminAcces && $ID)) {$filtr ='WHERE `logs` LIKE \'%group['.$ID.']%\'';} 
        	       if(!$adminAcces && !$ID){$filtr ='WHERE `logs` LIKE \'%'.$groupd['name'].' with module%\'';}
        	       if($groupd['name']) {if($ID){$usunf =" <a href='logs.php'>Usuñ filtr</a>";}else {$groupd['module'] = 'tescie';} echo "<tr class='centered'><th colspan='7'>Filtrowanie wyników dla ".$groupd['name']." w ".$groupd['module'].$usunf." </th></tr>\n";}
        	   ?>
            <tr><th>#</th><?php if($adminAcces || (!$adminAcces && !$ID)){echo "<th>Test</th>";} ?><th class="ctr">ID</th><th>Start</th><th>Stop</th><th>Run Time</th><th class="<?php echo $hName; ?>">Delete</th><th>Logs</th></tr>
        </thead>
        <tbody>
<?php
    
    $perPage=23;
    $qCount = "SELECT count(*) FROM `".SQL_PREFIX."run`";
    $resultCount = $db->query($qCount);
    $rowCount = mysql_fetch_row($resultCount);
    $count = $rowCount[0];
    $totalPages = ceil($count/$perPage);
    $page=isset($_GET['page']) && intval($_GET['page']) > 0 ? intval($_GET['page']) : 1;
    $start = ($page-1)*$perPage;
    $q="SELECT idRun,dateStart,id,dateStop,pid,haveError,timediff(dateStop,dateStart) diff FROM `".SQL_PREFIX."run` ".$filtr." ORDER BY dateStart DESC LIMIT $start,$perPage";
    $qsd="SELECT `idGroup` FROM `".SQL_PREFIX."group`";
    $result = $db->query($q);
    $resultsd = $db->query($qsd);
    $listy = array();
    while($runds=mysql_fetch_assoc($resultsd)) {array_push($listy, $runds['idGroup']);}		//dodawanie do listy
    while($result && ($run=mysql_fetch_assoc($result))){
        echo "<tr class='";
    	if($done != '0' AND $run['dateStart']){$ntest = substr($run['dateStart'],8,2); $done = '0';}
    	if($ntest != substr($run['dateStart'],8,2) AND $done != 'ok' AND !$ID){echo " older"; $done = 'ok'; $stop = '1';}
                if($run['dateStop'] == null){
            echo "id_".$run['id']." warning ";
            	echo "error";
        }else{
            if($run['haveError']){
                echo "id_".$run['id']." error ";
            }else{
                echo " success ";
                if(!$stop) {unset($listy[array_search($run['id'],$listy)]);}		//usuwanie z listy
            }
        }
        
        $infor = mysql_fetch_assoc($db->query("SELECT * FROM `".SQL_PREFIX."group` WHERE idGroup = " . intval($run['id'])));
        echo "' >\n";
        echo "	<td>".$run['idRun']."</td>\n";
if($adminAcces || (!$adminAcces && !$ID)){echo "	<td class='test' style='background-image:url(modules/".$infor['module']."/icon.png);'><a href='/view.php?wiev=chart&idGroup=".$run['id']."'>".$infor['name']."</a></td>\n";}
        echo "	<td class='ctr'>".$run['id']."</td>\n";
        echo "	<td>".$run['dateStart']."</td>\n";
        echo "	<td>".$run['dateStop']."</td>\n";
        echo "	<td>".$run['diff']."</td>\n";
if($adminAcces){        echo "	<td class='del'><a href='logs.php?did=".$run['idRun']."' onclick='return warningDeleteRun()' ><img data-original-title='Usuñ ten wpis' class='del-event' src='img/trash.png'></a></td>\n";}
        echo "	<td class='log'><a href='logs.php?id=".$run['idRun']."' target='log' ><img data-original-title='Zobacz log' data-placement='right' rel='tooltip' src='img/edit.gif'></a></td>\n";

        echo "</tr>\n";
    }
?>
        </tbody>
    </table>
    <ul class="pager">
<?php
foreach ($listy as $val) {
	  $infos = mysql_fetch_assoc($db->query("SELECT * FROM `".SQL_PREFIX."group` WHERE idGroup = " . $val));
    $todo .= "<a class='btn btn-run btn-force-run' data-placement='top' data-id='$val' rel='tooltip' data-original-title='<img src=\"modules/".$infos['module']."/icon.png\" /> <b style=\"color:black;\" >".$infos['name']."</b>' data-nr='".$infos['name']." [".$infos['module']."]'>$val</a> \n";
}
    if($page===1){
        echo "<li class='disabled previous' ><a href='#' >&larr; Nowsze</a></li>";
    }else{
        echo "<li class='previous' ><a href='?idGroup=$ID&page=".($page-1)."' >&larr; Nowsze</a></li>";
    }
    if($adminAcces & $start == 0){echo "<li class='middle' >".$todo."</li>";}
    if($page >= $totalPages){
        echo "<li class='disabled next' ><a href='#' >Starsze &rarr;</a></li>";
    }else{
        echo "<li class='next' ><a href='?idGroup=$ID&page=".($page+1)."' >Starsze &rarr;</a></li>";
    }

?>
    </ul>
</div>
<?php
include('inc/footer.php');
?>