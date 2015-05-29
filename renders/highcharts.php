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
if(!defined('INCLUDE_OK'))
    die();

function render($ranks, $target, $keywords){
	global $nDay;  
    $height=450;
    $idDiveu = rand(1000,3000);
    
    echo '<div id="'.$idDiveu.'" style="height: '.$height.'px; margin: 0 auto"></div>';

    echo "
<script type='text/javascript'>

var chart;
$(document).ready(function() {
        chart = new Highcharts.Chart({
                exporting: {
                    url: 'http://export.highcharts.com/',
                    
                    buttons: {
                        printButton:{
                            enabled:false
                        }
                    }
                },
                chart: {
                        renderTo: '$idDiveu',
                        defaultSeriesType: 'line',
                        zoomType: 'x',
                },


                title: {
                        text: '',
                },
                plotOptions: {
                series: {
                cursor: 'pointer',
                point: {
                events: {
                    click: function () {window.open(this.url,'_blanc');}
                    }
                    }
                }
            },
                yAxis: {
                showFirstLabel: false,
                    allowDecimals: false,
//                    min : 0,
//                    max : 100,
                    endOnTick: false,
                    tickPixelInterval: 40,  
                    reversed : true,
                    title: {
                            text: 'Pozycja w wyszukiwarce'
                    },
                    subtitle: {
                        text: document.ontouchstart === undefined ?
                        'Click and drag in the plot area to zoom in' :
                        'Drag your finger over the plot to zoom in'
                    },
                },
                tooltip: {
          },
                legend: {
                    align: 'center',
                    verticalAlign: 'bottom',
                    borderWidth: 0
                },
                series: [
";

    foreach ($keywords as $keyword) {
    	$pass = 0;
      $maxpass = 0;
        echo "{\n";
        echo "\tname: ".json_encode_tag($keyword).",stack:0,\n";
        echo "\tdata: [";
        foreach ($ranks as $rank) {
        	$dateo = date('d M',strtotime($rank['date']));
        	$dateo = to_PL($dateo);
            if(isset($rank[$target]) && isset($rank[$target][$keyword])){
            	if($rank[$target][$keyword][0] == 1){$false = 1;} else {$false = 0;}
                echo "{y:".$rank[$target][$keyword][0].",name:'".$dateo."<br />".h8($rank[$target][$keyword][1])."',url:'".h8($rank[$target][$keyword][1])."'},";
                $pass++;
            }else{
                echo "null,"; 
            }
          $maxpass++;
        }
        if($pass == 0 || $maxpass > $nDay+1){$marker = 'false';} else {$marker = 'true';}
        if($maxpass > 30){$intrv = floor($maxpass / 15);}
        
        echo "],\n";
        echo "\tmarker: {symbol: 'circle',enabled: ".$marker."},\n";
        if($false == 1){$ready++;}
        if(($ready > 1 && $false == 1) || $pass == 0){echo "visible:false,";}
        echo "},\n";
    }
    echo "{tooltip: {pointFormat: '',},
    events: {click: function () { $('.group-btn-calendar').click(); return this.point.url; } },\n";
    echo "\tlineWidth: 0,\n";
    echo "\type: 'scatter',\n";
    echo "\tcolor: 'black',\n";
    echo "\tmarker: {symbol: 'triangle-down'},\n";
    echo "\tname: \"Events\",\n";
    echo "\tdata: [";
    foreach ($ranks as $check) {
        $hEvent = false;
        $event = date('d M',strtotime($check['date']))."<br/>";
        	$event = to_PL($event);
        if(isset($check['__event']) && isset($check['__event'][$target]) && is_array($check['__event'][$target])){
            foreach ($check['__event'][$target] as $evt) {
                $hEvent = true;
                //$event .= $evt;
            }
        }
        if($hEvent/*!empty($event)*/){
            echo "{y: 0.5, name:'".$event.$evt."',url:'".$evt."',},"; $show++;
        }else{
            echo "null,";
        }
    }
    echo "]\n";
    if(!$show){echo ",visible:false";}
    echo "},\n";
    if(!$intrv){$intrv = 2;}
    echo "
                ],
                    
                xAxis: {tickInterval: ".$intrv.",
                    categories: [
";
    foreach ($ranks as $rank) {
    	$datem = date('d M',strtotime($rank['date']));

        echo "'".to_mPL($datem)."',";
    }
echo "
                    ],
                }
        });
 });
</script>
";
        
    }
    
?>