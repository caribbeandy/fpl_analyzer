<?php

// get picks
// https://fantasy.premierleague.com/drf/entry/5341/event/5/picks

//https://fantasy.premierleague.com/drf/bootstrap-static
$topPlayers = [
    925256,
    1242453,
    858934,
    157325,
    6759,
    155448,
    4043,
    502140,
    303074,
    43889,
    750614,
    4001,
    439,
    68971,
    53,
    5784,
    73902,
    3260,
    1200806,
    3911,
    59321,
    249,
    953411,
    1916,
    2433,
    25613,
    6316,
    458,
    30327,
    110900,
    23131,
    45755,
    20677,
    153785,
    6746,
    577124,
    60081,
    148530,
    676,
    566,
    481,
    1723,
    3637,
    17239,
    112,
    175456,
    195,
    72304,
    100001,
    47307,

/*
    // curr champs
	634235,
	5341,
	1772712,
	960454,
	2538602,
	287475,
	699183,
	1351181,
	127253,
	1041507,
	1829195,
	58945,
	581518,
	1433437,
	925614,
	254784,
	2670403,
	1094862,
	2642765,
	2918276,
	133048,
	1189694,
	305813,
	440169,
	491942,
	171968,
	1852753,
	1785188,
	1935826,
	2866649,
	63984,
	100694,
	2621208,
	102708,
	107504,
	2405036,
	684663,
	2115629,
	431876,
	1341954,
	1713881,
	37332,
	624576,
	2102142,
	2777474,
	1571029,
	1302397,
	329407,
	755824,
	2458887,
	853235,
    */
];
/*
$leaguePlayers = [
    2211410,
    794368,
    176173,
    2614012,
    853235,
    102411,
    2398802,
    2370071,
    436989,
    830747,
    2908593,
    1193077,
    2547505,
];
*/

function getPicks($playerId, $gameweekNum) {

    echo "Getting picks for player id[{$playerId}]" . PHP_EOL;

    $url = "https://fantasy.premierleague.com/drf/entry/$playerId/event/$gameweekNum/picks";

    $curl = curl_init($url); 
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($response, true);
    $response = $response['picks'];

    // Unique player ids!!
    $ids = [];

    foreach($response as $arr) {
        $id = $arr['element'];
        $ids[$id] = $id;
    }

    //print_r($ids);

    $names = [];

    global $allPlayers;
    global $counts;

    foreach($allPlayers as $a) {

        $id = $a['id'];

        if ( isset($ids[$id]) ) {
            
            if ( isset($counts[$a['first_name'] . ' ' . $a['second_name']]) ) {
                $counts[$a['first_name'] . ' ' . $a['second_name']]++;
            } else {
                $counts[$a['first_name'] . ' ' . $a['second_name']] = 1;
            }
        }
    }
}

function getAllPlayers() {
    
    echo "Fetching all players.." . PHP_EOL;

    $url = 'https://fantasy.premierleague.com/drf/bootstrap-static';
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($response, true);

    //print_r($response['events']);
    $response = $response['elements'];

    $newArr = [];

    foreach($response as &$a) {

        if ($a['value_season']==0) continue;

 //       $value = $a['total_points']/$a['value_season'];

        //if ($a['element_type'] != 4 || $value>6.6) continue;

        $name = $a['first_name'] . " " . $a['second_name'];

        $newValue = /*$a['value_season'] */
            //$a['points_per_game']
             $a['form']
//            * $a['total_points']
            * ($a['total_points']/$a['minutes'])
        ;

        $newArr[$name]['name'] = $name;
        $newArr[$name]['type'] = $a['element_type'];
        $newArr[$name]['id'] = $a['id'];
        $newArr[$name]['total_points'] = $a['total_points'];
        $newArr[$name]['now_cost'] = $a['now_cost'];
        $newArr[$name]['team_code'] = $a['team_code'];
        $newArr[$name]['val'] = $newValue;
    }

    usort($newArr, function($a, $b) {
        return $a['val'] < $b['val'];
    });
    
    $newArr = array_slice($newArr, 0, 60, true);

    $playerUrl = 'https://fantasy.premierleague.com/drf/element-summary/';

    // Fixture difficulty. Fetch for each player
    foreach($newArr as $key => $val) {

        $curl = curl_init($playerUrl . $val['id']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $playerResponse = curl_exec($curl);
        curl_close($curl);

        $playerResponse = json_decode($playerResponse, true);
        print_r($playerResponse); exit;

        $fixtures = $playerResponse['fixtures'];
        $top10 = array_slice($fixtures, 0, 6);

        $nextDifficulty = 0;
 
        foreach($top10 as $v) {
            $nextDifficulty+=$v['difficulty'];
        }

        $newArr[$key]['difficulty'] = $nextDifficulty;

        $nextDifficulty*=$nextDifficulty;

        if ( in_array($val['team_code'], [80,11,35,21]) ) {
            $val['val'] = $val['val'] * 6/5;
        }

        $newScore = $val['val']/$nextDifficulty;

        $newArr[$key]['val_difficulty'] = $newScore;
    }

    usort($newArr, function($a, $b) {
        return $a['val_difficulty'] < $b['val_difficulty'];
    });

    print_r($newArr); exit;

	$fp = fopen('file2.csv', 'w');

	foreach ($newArr as $fields) {
		fputcsv($fp, $fields);
	}

	fclose($fp);

    krsort($newArr);

    //print_r($newArr);
    //exit;

    return $response;
}

// use as global
$allPlayers = getAllPlayers();

// use as global
$counts = [];

//$topPlayers
//$leaguePlayers
foreach($topPlayers as $playerId) {
    getPicks($playerId, 22);
}

arsort($counts);
print_r($counts);
//getPicks(5341,5);
