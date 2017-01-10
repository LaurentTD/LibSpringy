<?php

require '../LibSpringy.php';

$graph = new Graph();

$node1 = $graph->newNode(array('label' => 'node1'));
$node2 = $graph->newNode(array('label' => 'node2'));
$node3 = $graph->newNode(array('label' => 'node3'));
$node4 = $graph->newNode(array('label' => 'node4'));

$graph->newEdge($node1, $node2, array());
$graph->newEdge($node1, $node3, array());
$graph->newEdge($node2, $node3, array());
$graph->newEdge($node2, $node4, array());

$force = new ForceDirected($graph);

$force->start();
$force->autosize(500, 500, 100);

$crossingLines = $force->getCrossingLines();

echo 'steps : '.$force->step.'<br />';
echo 'energy : '.$force->totalEnergy().'<br />';
echo 'crossingLines : '.count($crossingLines).'<br />';

?>