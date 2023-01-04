<html lang="Polish">
<head>
<meta http-equiv="Content-Language" content="pl" >
<meta charset="UTF-8" >
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-A3rJD856KowSb7dwlZdYEkO39Gagi7vIsF0jrRAoQmDKKtQBHUuLZ9AsSv4jD4Xa" crossorigin="anonymous"></script>
<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Kirchhoff-Auomotive: Czas pracy pras</title>
    <link rel="stylesheet" href="style.css">
    <meta http-equiv="refresh" content="60">
	<script type="text/javascript" src="js\fusioncharts.js"></script>
	<script type="text/javascript" src="js\themes\fusioncharts.theme.fusion.js"></script>
    <script type="text/javascript" src="js\fusioncharts.gantt.js"></script>
	</head>
<body>
<?php
    $serverName = "miesrv915.mes.kautomotive.local\\Hydms8";
    $connectionInfo = array( "Database"=>"hydra1", "UID"=>"plan_user", "PWD"=>"GyFAZn5m");
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    
	
	
	$sql="
	select 
    h.subkey1 as machine
    ,ab.artikel as KA
    ,h.gut_pri
    ,h.anmeld_dat+DATEADD(s, h.anmeld_zeit, 0) as planned_start_time
    ,h.anmeld_dat+DATEADD(s, h.anmeld_zeit+7200, 0) as planned_end_time 
    
	,h.anmeld_dat+DATEADD(s, h.anmeld_zeit, 0) as logon_time
    ,getdate() as actual_time
	,dateadd(s,(h.dauer-h.bmk_11-h.bmk_12),getdate()) as breakdowns_timestamp
	,dateadd(s,
		(case when h.dauer-(h.dauer-h.bmk_11-h.bmk_12)<1200 or h.gut_pri<5 then 
			round((ab.user_f_24/60.0),2)*6000
			else
			round(h.gut_pri/(h.dauer-h.bmk_12),2)*6000 - (h.dauer-h.bmk_11) end )
			,getdate())
	as predicted_production_duration
	,((h.dauer-h.bmk_11-h.bmk_12)*100.0)/( case when ISNULL(h.dauer,0)=0 then 1 else h.dauer end) as breakdown_ratio
	,6000 as target_quantity
	,0 as target_time
	from 
    hydadm.hybuch h
    left join hydadm.maschinen m on h.subkey1=m.masch_nr
    left join hydadm.auftrags_bestand ab on h.subkey2=ab.auftrag_nr
    where 
    key_type='A'
    and ab.auftrag_art='0'
    and m.mgruppe='PAUT'
	and isnull(ab.user_c_65,'')<>ab.auftrag_nr

	
	";
    $sql2 = "select 
    h.subkey1 as machine
    ,ab.artikel as KA
    ,h.gut_pri
    ,h.anmeld_dat+DATEADD(s, h.anmeld_zeit, 0) as planned_start_time
    ,h.anmeld_dat+DATEADD(s, h.anmeld_zeit+7200, 0) as planned_end_time 
    
	,h.anmeld_dat+DATEADD(s, h.anmeld_zeit, 0) as logon_time
    ,getdate() as actual_time
	,dateadd(s,(h.dauer-h.bmk_11-h.bmk_12),getdate()) as breakdowns_timestamp
	,dateadd(s,
		(case when h.dauer-(h.dauer-h.bmk_11-h.bmk_12)<1200 or h.gut_pri<5 then 
			round((ab.user_f_24/60.0),2)*6000
			else
			round(h.gut_pri/(h.dauer-h.bmk_12),2)*6000 - (h.dauer-h.bmk_11) end )
			,getdate())
	as predicted_production_duration
	,((h.dauer-h.bmk_11-h.bmk_12)*100.0)/( case when ISNULL(h.dauer,0)=0 then 1 else h.dauer end) as breakdown_ratio
	,6000 as target_quantity
	,0 as target_time
	from 
    hydadm.hybuch h
    left join hydadm.maschinen m on h.subkey1=m.masch_nr
    left join hydadm.auftrags_bestand ab on h.subkey2=ab.auftrag_nr
    where 
    key_type='A'
    and ab.auftrag_art='0'
    and m.mgruppe='PAUT'
	and isnull(ab.user_c_65,'')<>ab.auftrag_nr
	
	union 
	select 
	pp.masch_nr,
	pp.KA,
	0 as gut_pri,
	'' as planned_start_time,
	'' as planned_end_time,
	'' as logon_time,
	'' as actual_time,
	''  as breakdowns_timestamp,
	0 as predicted_production_duration,
	0 as breakdown_ratio,
	target_quantity ,
	target_time
	from prod_plan pp

	order by machine,planned_start_time desc ";
	
	
    $params = array();
    $options = array("Scrollable" =>SQLSRV_CURSOR_KEYSET);
    $stmt = sqlsrv_query($conn, $sql, $params, $options );
    $stmt1 = sqlsrv_query($conn, $sql, $params, $options);
    $stmt2 = sqlsrv_query($conn, $sql, $params, $options);
    $stmt3 = sqlsrv_query($conn, $sql, $params, $options);
    $current_date = date(('Y-m-d'));
    $end_date = date(('Y-m-d'), strtotime("6 days"));
    $stmt4 = sqlsrv_query($conn, $sql2, $params, $options);
    $stmt5 = sqlsrv_query($conn, $sql, $params, $options);
    $stmt6 = sqlsrv_query($conn, $sql, $params, $options);
    $stmt7 = sqlsrv_query($conn, $sql, $params, $options);
    
?>
<script type="text/javascript">       
		FusionCharts.ready(function(){
			var chartObj = new FusionCharts({
    type: 'gantt',
    renderAt: 'chart-container',
    width: '100%',
    height: '800',
    dataFormat: 'json',
    dataSource: {
    "chart": {
        "caption": "Prasy mechaniczne w firmie Kirchhoff-Automotive",
        "subcaption": "Zaplanowany, a właściwy czas pracy",
        "dateformat": "dd/mm/yyyy",
        "outputdateformat": "hh:mn",
        "ganttwidthpercent": "40",
        "ganttPaneDuration": "3",
        "ganttPaneDurationUnit": "d",
        "plottooltext": "$processName{br} $label starting date $start{br}$label ending date $end",
        "legendBorderAlpha": "0",
        "flatScrollBars": "1",
        "gridbordercolor": "#333333",
        "gridborderalpha": "20",
        "slackFillColor": "#e44a00",
        "taskBarFillMix": "light+0",
        "theme": "fusion"
    },
    "categories": [
        {
            "bgcolor": "#999999",
            "category": [
                <?php
                    echo '{"start": "'.$current_date.'00:00:00",
                    "end": "'.$end_date.' 23:59:59",
                    "label": "Dni",
                    "align": "middle",
                    "fontcolor": "#ffffff",
                    "fontsize": "12"}';
                ?>
            ]
        },
        {
            "bgcolor": "#999999",
            "align": "middle",
            "fontcolor": "#ffffff",
            "fontsize": "12",
            "category": [
                <?php
                header('Content-type: text/html; charset=utf-8');
    for($a=0;$a<=6;++$a){
        setlocale(LC_ALL, 'Polish');
        $increasing_date = date(("Y-m-d"), strtotime("+$a day"));
        $day_name = strftime('%A',strtotime($increasing_date));
        $da_name = iconv("iso-8859-2", "utf-8", $day_name);
        echo '{"start": "'.$increasing_date.' 00:00:00", "end": "'.$increasing_date.' 23:59:59", "label": "'.$da_name.'"},';
    }
                ?>
            ]
        },
        {
            "bgcolor": "#ffffff",
            "fontcolor": "#333333",
            "fontsize": "11",
            "align": "center",
            "category": [
                <?php
               $c = 1;
               echo '{"start": "'.$current_date.' 00:00:00" , "end": "'.$current_date.' 6:45:00", "label": "Zmiana 3"},';
                    for($l = 0; $l<=6; $l++){
                        $day = date(("Y-m-d"), strtotime("+$l day"));
                        $next_day = date(('Y-m-d'), strtotime("+$c day"));
                        echo '{"start": "'.$day.' 6:45:00" , "end": "'.$day.' 14:45:00", "label": "Zmiana 1"},';
                        
                        echo '{"start": "'.$day.' 14:45:00" , "end": "'.$day.' 22:45:00", "label": "Zmiana 2"},';
            
                        echo '{"start": "'.$day.' 22:45:00" , "end": "'.$next_day.' 6:45:00", "label": "Zmiana 3"},';
                        $c++;
                }
                ?>
            ]
        }
    ],
    "processes": {
        "headertext": "Maszyna",
        "fontcolor": "#000000",
        "fontsize": "11",
        "isanimated": "1",
        "bgcolor": "#6baa01",
        "headervalign": "bottom",
        "headeralign": "left",
        "headerbgcolor": "#999999",
        "headerfontcolor": "#ffffff",
        "headerfontsize": "12",
        "align": "left",
        "isbold": "1",
        "bgalpha": "25",
        "process": [

          
          <?php
          
          $i=0;
          while ($row = sqlsrv_fetch_array($stmt)){
              $machine = json_encode($row[machine]);
              $machine_json = '{"label": '.$machine.', "id": "'.$i++.'"},';
              echo $machine_json;
              }
          
          ?>
        ]
    },
    "tasks": {
        "task": [
            <?php

$p=0;
while($row = sqlsrv_fetch_array($stmt1)){
    $help = json_encode($row['planned_start_time']);//planowany 
    $help2 = str_replace('{"date"','{"start"',$help);
    $planned_start_time = str_replace('}','',$help2);
    $help4 = json_encode($row['planned_end_time']);//planowany 
    $help5 = str_replace('{"date"','"end"',$help4);
    $planned_end_time = str_replace('}','',$help5);
	
    $help7 = json_encode($row['logon_time']);//aktualny
    $help8 = str_replace('{"date"','{"start"',$help7);
    $logon_time = str_replace('}','',$help8);
    $help10 = json_encode($row['actual_time']);//aktualy
    $help11 = str_replace('{"date"','"end"',$help10);
    $durrnet_time = str_replace('}','',$help11);

	$helpp = json_encode($row['actual_time']);//przerwa
    $helpp2 = str_replace('{"date"','"start"',$helpp);
    $planned_start_breakdown = str_replace('}','',$helpp2);
    $helpp3 = json_encode($row['breakdowns_timestamp']);
    $helpp4 = str_replace('{"date"','"end"',$helpp3);//przerwa
    $planned_end_breakdown = str_replace('}','',$helpp4);

    $help13 = json_encode($row['breakdowns_timestamp']);//przewidywany
    $help14 = str_replace('{"date"','{"start"',$help13);
    $durrent_time_start = str_replace('}','',$help14);
    $help16 = json_encode($row['predicted_production_duration']);//przewidywany
    $help17 = str_replace('{"date"','"end"',$help16);
    $predicted_end_time = str_replace('}','',$help17);

    $label = json_encode($row['KA']);
    
    $planned_time = $planned_start_time.', "processid": "'.$p.'", '.$planned_end_time.', "id": "1-1",
    "color": "#008ee4",
    "height": "32%",
    "label": '.$label.',
    "toppadding": "56%"},';
    echo $planned_time;
    $actual_time = $logon_time.', "processid": "'.$p.'", '.$durrnet_time.', "id": "1",
    "color": "#6baa01",
    "label": '.$label.',
    "height": "32%","id": "1"},';
    echo $actual_time;   
	
    echo '{'.$planned_start_breakdown.', '.$planned_end_breakdown.', "processid":"'.$p.'","label": '.$label.', "color": "#cc33ff", "height": "32%"},';
	
	$predicted_time = $durrent_time_start.', "processid": "'.$p.'", '.$predicted_end_time.',"label": '.$label.', "id": "2",
    "color": "#2A9370",
        "height": "32%","id": "1", "label": '.$label.',},';
    echo $predicted_time;
    
    $p++;    
}
$current_date_and_hour = date('Y-m-d H:i:s');
    $czas_akt = time();
    $color = 1;
    while($row = sqlsrv_fetch_array($stmt4)) {
        $label = json_encode($row['KA']);
        $zmienna = json_encode($row['target_time']);
        $h = $czas_akt + $zmienna;
        $end_date = date(("Y-m-d H:i:s"), $h);
        if($color % 2 == 1){
        echo '{"start": "'.$current_date_and_hour.'", "end":"'.$end_date.'", "color": "#008ee4",
            "height": "32%",
            "toppadding": "56%", "label": '.$label.',},  ';
        }
        else{
        echo '{"start": "'.$current_date_and_hour.'", "end":"'.$end_date.'", "color": "##9494b8",
            "height": "32%",
            "toppadding": "56%", "label": '.$label.',},  ';}
            $color++;
       $current_date_and_hour = $end_date;
       $czas_akt = $h;
    }



?>            
        ]
    },
    "legend": {
        "item": [
            {
                "label": "Zaplanowany czas pracy maszyny",
                "color": "#008ee4"
            },
            {
                "label": "Czas pracy maszyny",
                "color": "#6baa01"
            },
            {
                "label": "Przerwa w pracy maszyny ",
                "color": "#cc33ff"
            },
            {
                "label": "Przewidywany czas pracy",
                "color":"#2A9370",
            }
            
        ]
    }
    
}   
});
			chartObj.render();
});
	</script>
    <div class="chart" id="chart-container">FusionCharts XT will load here!</div>
<p>

</p>
</body>
</html>