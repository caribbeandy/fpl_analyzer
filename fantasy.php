<?php

// get picks
// https://fantasy.premierleague.com/drf/entry/5341/event/5/picks
//https://fantasy.premierleague.com/drf/bootstrap-static

/*

Return format: 
	Array
	(
		[749922] => Array
			(
				[421] => 421
				[267] => 267
				[77] => 77
				...
			)
*/
function getAllTeams($playerIds, $gameweekNum) {

    $picks = [];

    foreach($playerIds as $playerId) {
        $picks[$playerId] = getUserTeam($playerId, $gameweekNum);
    }

    return $picks;
}

/*
	Return format:
		playerId => count
		Array
		(
			[286] => 26
			[367] => 26
			[394] => 23
*/
function countPlayersById($playerIds, $gameweekNum) {

	$teams = getAllTeams($playerIds, $gameweekNum);

	$playerCounts = [];

	foreach($teams as $team) {
		foreach($team as $playerId) {
			if ( isset($playerCounts[$playerId]) ) {
				$playerCounts[$playerId]++;
			} else {
				$playerCounts[$playerId] = 1;
			}
		}
	}

    arsort($playerCounts);
	return $playerCounts;
}

/*
	Return format:
		web_name => count
*/
function countPlayersByName($playerIds, $gameweekNum) {

	$countPlayersById = countPlayersById($playerIds, $gameweekNum);
	$playerData = getAllPlayers();

    $playerCountsByName = [];
    
    foreach($countPlayersById as $id => $count) {
        $name = $playerData[$id]['first_name'] . " " . $playerData[$id]['second_name'];
        $playerCountsByName[$name] = number_format ($count/count($playerIds)*100,4) . '%';
    }

    return $playerCountsByName;
}

/*
 Return format indexed by playerIds:
	Array
	(
		[306] => 306
		[100] => 100
		[13] => 13
		...
	)
*/
function getUserTeam($playerId, $gameweekNum) {

    echo "Getting team for player id[{$playerId}]" . PHP_EOL;

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

    return $ids;
}

/*
Return format:
	Array(
		0 => (
			[id] => 1
			[photo] => 48844.jpg
			[web_name] => Ospina
		)
	)
*/
function getAllPlayers() {

    echo "Fetching all players.." . PHP_EOL;

    $url = 'https://fantasy.premierleague.com/drf/bootstrap-static';
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($response, true);
    $response = $response['elements'];

    $allPlayers = [];

    foreach ($response as $k => $v) {
        $newKey = $v['id'];
        $allPlayers[$newKey] = $response[$k];
        $allPlayers[$newKey]['now_cost'] /= 10;
    }

    // CSV setup
    $fp = fopen('fpl_2017_2018_data_raw.csv', 'w');
    $keys = array_keys($response[key($response)]);
    fputcsv($fp, $keys);

    foreach ($response as $fields) {
        fputcsv($fp, $fields);
    }

    fclose($fp);
    return $allPlayers;
}

$topPlayers = [
    749922,
    46178,
    38273,
    485465,
    37,
    218,
    112597,
    345,
    370,
    888793,
    767113,
    725389,
    175574,
    36298,
    85929,
    44937,
    11421,
    97282,
    388177,
    263,
    208,
    68,
    69,
    28412,
    24494,
    965,
    905,
    2238
    ];

print_r(countPlayersByName($topPlayers, 9));
echo count($topPlayers);
