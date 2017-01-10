LibSpringy
====

LibSpringy is a PHP port of [Springy](https://github.com/dhotson/springy), *"A force directed graph layout algorithm in JavaScript."* with some new features.

How to use it ?
----
```php
require 'LibSpringy/LibSpringy.php';

$graph = new Graph();

// Create some nodes and edges between them
$node1 = $graph->newNode(array('label' => 'node1'));
$node2 = $graph->newNode(array('label' => 'node2'));
$node3 = $graph->newNode(array('label' => 'node3'));
$node4 = $graph->newNode(array('label' => 'node4'));

$graph->newEdge($node1, $node2, array());
$graph->newEdge($node1, $node3, array());
$graph->newEdge($node2, $node3, array());
$graph->newEdge($node2, $node4, array());

$force = new ForceDirected($graph);

// Run it
$force->start();

// And get stats
$crossingLines = $force->getCrossingLines();

echo 'steps : '.$force->step.'<br />';
echo 'energy : '.$force->totalEnergy().'<br />';
echo 'crossingLines : '.count($crossingLines).'<br />';
```

And now ?
----
You can graph it, with GD, Imagick, or any other graphical tool.
```php
// Auto resizing and positionning of elements in a 500*500 area with a margin of 100
$force->autosize(500, 500, 100);

$imagick = new \Imagick();
$imagick->newImage(500, 500, 'rgb(255, 255, 255)');
$imagick->setImageFormat("png");

$drawEdges = new \ImagickDraw();
$drawEdges->setStrokeColor('rgb(0, 0, 0)');
$drawEdges->setFillColor('none');	
$drawEdges->setStrokeWidth(3);

// Draw edges
foreach($graph->edges as $edge) {
	$source = $force->point($edge->source);
	$target = $force->point($edge->target);

	$drawEdges->line($source->p->x, $source->p->y, $target->p->x, $target->p->y);
}

$imagick->drawImage($drawEdges);

$drawPoints = new \ImagickDraw();
$drawPoints->setStrokeOpacity(1);
$drawPoints->setStrokeColor('rgb(0, 0, 0)');
$drawPoints->setFillColor('rgb(0, 0, 0)');
$drawPoints->setStrokeWidth(1);

// And draw nodes
foreach($graph->nodes as $node) {
	$point = $force->point($node);
	
	$drawPoints->circle($point->p->x, $point->p->y, $point->p->x, $point->p->y+20);
}

$imagick->drawImage($drawPoints);

// Add some debug
$drawDebug = new \ImagickDraw();
$drawDebug->annotation(5, 15, $force->step . ' steps - ' . $duree);

$imagick->drawImage($drawDebug);

// And show
header('Content-Type: image/png');
echo $imagick;
```

*See examples/ directory for a more complete example.*

Want to go faster ?
----
Try [HHVM](https://github.com/facebook/hhvm).

For the same starting scenario (61 nodes and 68 edges) it take 14.84 sec by run with PHP 5.6.29, and 9.11 sec by run with HHVM 3.17.0 (average for 100 runs on an idle server).


Faster again ?
----
Use all of your CPU cores, by runing it multiple times until the random generator find a good solution.  
You can use this [quick and (very) dirty tool](https://github.com/LaurentTD/Qvdm) to run your script over all your CPU cores.