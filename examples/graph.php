<?php

require '../LibSpringy.php';

list($usec, $sec) = explode(' ',microtime());
$querytime_before = ((float)$usec + (float)$sec);

$graph = new Graph();

$node1 = $graph->newNode(array('label' => '1'));
$node2 = $graph->newNode(array('label' => '2'));
$node3 = $graph->newNode(array('label' => '3'));
$node4 = $graph->newNode(array('label' => '4'));
$node5 = $graph->newNode(array('label' => '5'));
$node6 = $graph->newNode(array('label' => '6'));
$node7 = $graph->newNode(array('label' => '7'));

$graph->newEdge($node1, $node2, array());
$graph->newEdge($node1, $node3, array());
$graph->newEdge($node2, $node3, array());
$graph->newEdge($node2, $node4, array());
$graph->newEdge($node4, $node5, array());
$graph->newEdge($node5, $node6, array());
$graph->newEdge($node6, $node1, array());
$graph->newEdge($node7, $node5, array());

$force = new ForceDirected($graph);

$force->start();
$force->autosize(500, 500, 100);

list($usec, $sec) = explode(' ',microtime());
$querytime_after = ((float)$usec + (float)$sec);
$duree = sprintf ('%01.2f sec.', ($querytime_after - $querytime_before));

$imagick = new \Imagick();
$imagick->newImage(500, 500, 'rgb(255, 255, 255)');
$imagick->setImageFormat("png");

$drawEdges = new \ImagickDraw();
$drawEdges->setStrokeColor('rgb(2, 34, 67)');
$drawEdges->setFillColor('none');	
$drawEdges->setStrokeWidth(3);

foreach($graph->edges as $edge) {
	$source = $force->point($edge->source);
	$target = $force->point($edge->target);

	$drawEdges->line($source->p->x, $source->p->y, $target->p->x, $target->p->y);
}

$imagick->drawImage($drawEdges);

$drawPoints = new \ImagickDraw();
$drawPoints->setStrokeOpacity(1);
$drawPoints->setStrokeColor('rgb(0, 0, 0)');
$drawPoints->setFillColor('rgb(255, 208, 99)');
$drawPoints->setStrokeWidth(1);
	
foreach($graph->nodes as $node) {
	$point = $force->point($node);
	
	$drawPoints->circle($point->p->x, $point->p->y, $point->p->x, $point->p->y+20);
	
	$textMetrics = $imagick->queryFontMetrics($drawPoints, $node->data['label']);
	$drawPoints->annotation($point->p->x - ($textMetrics['textWidth'] / 2), $point->p->y + 4, $node->data['label']);
}

$imagick->drawImage($drawPoints);

$drawDebug = new \ImagickDraw();
$drawDebug->annotation(5, 15, $force->step . ' steps - ' . $duree);

$imagick->drawImage($drawDebug);

header('Content-Type: image/png');
echo $imagick;

?>