<?php

$fp   = fopen('./data.csv', "r");
while (($data = fgetcsv($fp, 0, ",")) !== FALSE) {
    $scoresByRankAndAd[] = $data;
}
fclose($fp);

$rankNum = count($scoresByRankAndAd);
$adNum = count($scoresByRankAndAd[0]);
$sortedScoredAds = sortAds($scoresByRankAndAd, $rankNum, $adNum);
var_dump($sortedScoredAds);

function sortAds($scoresByRankAndAd, $rankNum, $adNum)
{
    $graph = [];

    for ($i = 0; $i < $rankNum; ++$i) {
        for ($j = 0; $j < $adNum; ++$j) {
            addEdge($graph, $i, $j + $rankNum, $scoresByRankAndAd[$i][$j]);
        }
    }

    // 擬似始点ノードを作成して、rankノードに向けてエッジをつける
    $startNode = $rankNum + $adNum;
    for ($i = 0; $i < $rankNum; ++$i) {
        addEdge($graph, $startNode, $i, 0);
    }

    // 擬似終点ノードを作成してidentityHashノードからエッジをつける
    $endNode = $startNode + 1;
    for ($j = 0; $j < $adNum; ++$j) {
        addEdge($graph, $j + $rankNum, $endNode, 0);
    }

    $maxReveneue = resolveMaxRevenueFlow($graph, $startNode, $endNode, $rankNum);
    print("Max Gain:" . $maxReveneue);

    $sortedScoredAds = [];
    for ($i = 0; $i < $rankNum; ++$i) {
        foreach ($graph[$i] as $edge) {
            if ($edge['initCapacity'] == 1 && $edge['capacity'] == 0) {
                $sortedScoredAds[] = $scoresByRankAndAd[$i][$edge['to'] - $rankNum];
            }
        }
    }
    return $sortedScoredAds;
}

function addEdge(&$graph, $from, $to, $cost)
{
    /**
     * from ... エッジの起点ノード
     * to ... エッジの終点ノード
     * capacity ... 最大エッジ数
     * cost ... コスト
     * revEdge ... 起点ノードを指しているエッジのノード
     */
    $edge = ['from' => $from, 'to' => $to, 'capacity' => 1, 'initCapacity' => 1, 'cost' => $cost, 'revEdge' => !isset($graph[$to]) ? 0 : count($graph[$to])];
    $reEdge = ['from' => $to, 'to' => $from, 'capacity' => 0, 'initCapacity' => 0, 'cost' => -$cost, 'revEdge' => !isset($graph[$from]) ? 0 : count($graph[$from])];
    $graph[$from][] = $edge;
    $graph[$to][] = $reEdge;
}

function resolveMaxRevenueFlow(&$graph, $start, $end, $flowNum)
{
    $revenue = 0;
    $flowCount = $flowNum;
    while ($flowCount > 0) {
        $dist = [];
        $dist[$start] = 0;
        $prevNode = [];
        $prevEdge = [];

        // 深さ優先探索で総スコアが最大になる経路を1本決める
        while (true) {
            $updated = false;
            for ($node = 0; $node < count($graph); ++$node) {
                if (!isset($dist[$node])) {
                    continue;
                }
                for ($i = 0; $i < count($graph[$node]); ++$i) {
                    $edge = $graph[$node][$i];
                    // 循環参照を避けるために通過済みのノードに向先を変更しない
                    $passed = false;
                    for ($k = $node; (isset($k) && $k != $start); $k = $prevNode[$k]) {
                        if ($edge['to'] == $k) {
                            $passed = true;
                            break;
                        }
                    }

                    // エッジが使用可能 && 次ノードのスコアを超えるなら経路を更新する
                    if ($edge['capacity'] > 0 && (!isset($dist[$edge['to']]) || $dist[$edge['to']] < $dist[$node] + $edge['cost']) && !$passed) {
                        $dist[$edge['to']] = $dist[$node] + $edge['cost'];
                        // 直前のノード
                        $prevNode[$edge['to']] = $node;
                        // 直前のエッジ
                        $prevEdge[$edge['to']] = $i;
                        $updated = true;
                    }
                }
            }
            if (!$updated) {
                break;
            }
        }

        if (!isset($dist[$end])) {
            return 0;
        }

        $d = $flowCount;
        for ($node = $end; $node != $start; $node = $prevNode[$node]) {
            $d = min($d, $graph[$prevNode[$node]][$prevEdge[$node]]['capacity']);
        }
        $flowCount -= $d;
        $revenue += $dist[$end] * $d;
        for ($node = $end; $node != $start; $node = $prevNode[$node]) {
            $edge = $graph[$prevNode[$node]][$prevEdge[$node]];
            $edge['capacity'] -= $d;
            $graph[$prevNode[$node]][$prevEdge[$node]] = $edge;

            if ($edge['from'] != $edge['to']) {
                $reEdge = $graph[$edge['to']][$edge['revEdge']];
                $reEdge['capacity'] += $d;
                $graph[$edge['to']][$edge['revEdge']] = $reEdge;
            } else {
                $reEdge = $graph[$prevEdge[$node]][$edge['revEge'] + 1];
                $reEdge['capacity'] += $d;
                $graph[$prevEdge[$node]][$edge['revEge'] + 1] = $reEdge;
            }
        }
    }
    return $revenue;
}


