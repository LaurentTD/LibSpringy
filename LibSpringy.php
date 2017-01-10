<?php

class Vector {

	public $x;
	public $y;

	public function __construct($x = 0, $y = 0) {
		$this->x = $x;
		$this->y = $y;
	}

	public function random() {
		$this->x = 10.0 * (mt_rand() / mt_getrandmax() - 0.5);
		$this->y = 10.0 * (mt_rand() / mt_getrandmax() - 0.5);
	}

	public function add($vector2) {
		$this->x += $vector2->x;
		$this->y += $vector2->y;
	}

	public function subtract($vector2) {
		$this->x -= $vector2->x;
		$this->y -= $vector2->y;
	}

	public function multiply($n) {
		$this->x *= $n;
		$this->y *= $n;
	}

	public function divide($n) {
		if ($n != 0) {
			$this->x /= $n;
			$this->y /= $n;
		} else {
			$this->x = 0;
			$this->y = 0;
		}
	}

	public function magnitude() {
		return sqrt($this->x * $this->x + $this->y * $this->y);
	}

	public function normal() {
		$x = -$this->y;
		$y = $this->x;
		$this->x = $x;
		$this->y = $y;
	}

	public function normalise() {
		$this->divide($this->magnitude());
	}

}

class Point {

	public $p;
	public $m;
	public $v;
	public $a;


	public function __construct($position, $mass) {
		$this->p = $position;        // position
		$this->m = $mass;            // mass
		$this->v = new Vector(0, 0); // velocity
		$this->a = new Vector(0, 0); // acceleration
	}

	public function applyForce($force) {
		$force->divide($this->m);
		$this->a->add($force);
	}

}

class Spring {

	public function __construct($point1, $point2, $length, $k) {
		$this->point1 = $point1;
		$this->point2 = $point2;
		$this->length = $length; // spring length at rest
		$this->k      = $k;      // spring constant (See Hooke's law) .. how stiff the spring is
	}

}

class Graph {

	public $nodeSet   = array();
	public $nodes     = array();
	public $edges     = array();
	public $adjacency = array();

	public $nextNodeId = 0;
	public $nextEdgeId = 0;
	public $eventListeners = array();

	public function __construct() {

	}

	public function addNode($node) {
		if (!isset($this->nodeSet[$node->id])) {
			$this->nodes[] = $node;
		}

		$this->nodeSet[$node->id] = $node;

		return $node;
	}

	public function addNodes($nodes) {
		foreach ($nodes as $name) {
			$node = new Node($name, array("label" => $name));
			$this->addNode($node);
		}
	}

	public function addEdge($edge) {
		$exists = false;

		foreach ($this->edges as $e) {
			if ($edge->id == $e->id) {
				$exists = true;
			}
		}

		if (!$exists) {
			$this->edges[] = $edge;
		}

		if (!isset($this->adjacency[$edge->source->id])) {
			$this->adjacency[$edge->source->id] = array();
		}

		if (!isset($this->adjacency[$edge->source->id][$edge->target->id])) {
			$this->adjacency[$edge->source->id][$edge->target->id] = array();
		}

		$exists = false;

		foreach ($this->adjacency[$edge->source->id][$edge->target->id] as $e) {
			if ($edge->id == $e->id) {
				$exists = true;
			}
		}

		if (!$exists) {
			$this->adjacency[$edge->source->id][$edge->target->id][] = $edge;
		}

		return $edge;
	}

	public function addEdges($edges) {
		foreach ($edges as $edge) {
			$node1 = $edge[0];
			if (!isset($this->nodeSet[$node1])) {
				// TODO : error : invalid node name/id
			}
			$node2 = $edge[1];
			if (!isset($this->nodeSet[$node2])) {
				// TODO : error : invalid node name/id
			}
			$attr = $edge[2];

			$this->newEdge($node1, $node2, $attr);
		}
	}

	public function newNode($data) {
		$this->nextNodeId++;
		$node = new Node($this->nextNodeId, $data);
		$this->addNode($node);
		return $node;
	}

	public function newEdge($source, $target, $data) {
		$this->nextEdgeId++;
		$edge = new Edge($this->nextEdgeId, $source, $target, $data);
		$this->addEdge($edge);
		return $edge;
	}

	public function getEdges($node1, $node2) {
		if (isset($this->adjacency[$node1->id]) && isset($this->adjacency[$node1->id][$node2->id])) {
			return $this->adjacency[$node1->id][$node2->id];
		} else {
			return array();
		}
	}

}

class Node {

	public $id;
	public $data;

	public function __construct($id, $data = array()) {
		$this->id    = $id;
		$this->data = $data;
	}

}

class Edge {

	public $id;
	public $source;
	public $target;
	public $data;

	public function __construct($id, $source, $target, $data = array()) {
		$this->id     = $id;
		$this->source = $source;
		$this->target = $target;
		$this->data   = $data;
	}

}

class ForceDirected {

	public $graph;

	public $stiffness;
	public $repulsion;
	public $damping;
	public $minEnergyThreshold;
	public $tick;

	public $step;

	public $nodePoints  = array();
	public $edgeSprings = array();

	public function __construct($graph, $stiffness = 400, $repulsion = 400, $damping = 0.5, $minEnergyThreshold = 0.00001, $tick = 0.03) {
		$this->graph = $graph;

		$this->stiffness          = $stiffness;          // spring stiffness constant
		$this->repulsion          = $repulsion;          // repulsion constant
		$this->damping            = $damping;            // velocity damping factor
		$this->minEnergyThreshold = $minEnergyThreshold; // threshold used to determine render stop
		$this->tick               = $tick;
	}

	public function point($node) {
		if (!isset($this->nodePoints[$node->id])) {
			if (isset($node->data['mass'])) {
				$mass = $node->data['mass'];
			} else {
				$mass = 1;
			}
			if (isset($node->data['position'])) {
				$position = $node->data['position'];
			} else {
				$position = null;
			}

			if ($position) {
				$vector = new Vector($position->x, $position->y);
			} else {
				$vector = new Vector();
				$vector->random();
			}
			$this->nodePoints[$node->id] = new Point($vector, $mass);
		}

		return $this->nodePoints[$node->id];
	}

	public function spring($edge) {
		if (!isset($this->edgeSprings[$edge->id])) {
			if (isset($edge->data['length'])) {
				$length = $edge->data['length'];
			} else {
				$length = 1;
			}

			$existingSpring = false;

			$from = $this->graph->getEdges($edge->source, $edge->target);
			foreach ($from as $e) {
				if ($existingSpring === false && isset($this->edgeSprings[$e->id])) {
					$existingSpring = $this->edgeSprings[$e->id];
				}
			}

			if ($existingSpring !== false) {
				return new Spring($existingSpring->point1, $existingSpring->point2, 0, 0);
			}

			$to = $this->graph->getEdges($edge->target, $edge->source);
			foreach ($to as $e) { // $from in springy.js !?
				if ($existingSpring === false && isset($this->edgeSprings[$e->id])) {
					$existingSpring = $this->edgeSprings[$e->id];
				}
			}

			if ($existingSpring !== false) {
				return new Spring($existingSpring->point2, $existingSpring->point1, 0, 0);
			}

			$this->edgeSprings[$edge->id] = new Spring($this->point($edge->source), $this->point($edge->target), $length, $this->stiffness);

		}

		return $this->edgeSprings[$edge->id];
	}

	public function eachNode($callback) {
		$t = $this;
		foreach ($this->graph->nodes as $n) {
			$callback($t, $n, $t->point($n));
		}
	}

	public function eachEdge($callback) {
		$t = $this;
		foreach ($this->graph->edges as $e) {
			$callback($t, $e, $t->spring($e));
		}
	}

	public function eachSpring($callback) {
		$t = $this;
		foreach ($this->graph->edges as $e) {
			$callback($t, $t->spring($e));
		}
	}

	public function applyCoulombsLaw() {

		$repulsion = $this->repulsion;
		$nodesList = $this->graph->nodes;

		foreach($nodesList as $node1) {
			$point1 = $this->point($node1);
			
			$point1X  = $point1->p->x;
			$point1Y  = $point1->p->y;
			$point1M  = $point1->m;
			$point1AX = $point1->a->x;
			$point1AY = $point1->a->y;
			
			foreach($nodesList as $node2) {
				$point2 = $this->point($node2);

				if ($point1 !== $point2) {

					/*
					In order to speed up big loops, we do not use Vector here
					For the sames starting conditions (1300 nodes) :
					 - with Vector : 12 sec
					 - without     :  6 sec

					$d = new Vector($point1->p->x, $point1->p->y);
					$d->subtract($point2->p);

					$distance = $d->magnitude() + 0.1; // avoid massive forces at small distances (and divide by zero)

					$direction = new Vector($d->x, $d->y);
					$direction->normalise();

					$forcePoint1 = new Vector($direction->x, $direction->y);
					$forcePoint1->multiply($this->repulsion);
					$forcePoint1->divide($distance * $distance * 0.5);

					$forcePoint2 = new Vector($direction->x, $direction->y);
					$forcePoint2->multiply($this->repulsion);
					$forcePoint2->divide($distance * $distance * -0.5);

					$point1->applyForce($forcePoint1);
					$point2->applyForce($forcePoint2);
					*/

					$point2X = $point2->p->x;
					$point2Y = $point2->p->y;
					$point2M = $point2->m;

					$magnitude = sqrt(($point1X - $point2X) * ($point1X - $point2X) + ($point1Y - $point2Y) * ($point1Y - $point2Y));

					$distance = $magnitude + 0.1;
					$distanceDistance = $distance * $distance;

					if ($magnitude != 0) {
						$directionRepulsionX = ($point1X - $point2X) / $magnitude * $repulsion;
						$directionRepulsionY = ($point1Y - $point2Y) / $magnitude * $repulsion;
					} else {
						$directionRepulsionX = 0;
						$directionRepulsionY = 0;
					}

					$point1X /= $point1M;
					$point1Y /= $point1M;
					$point1AX += ($directionRepulsionX) / ($distanceDistance * 0.5);
					$point1AY += ($directionRepulsionY) / ($distanceDistance * 0.5);

					$point2->p->x /= $point2M;
					$point2->p->y /= $point2M;
					$point2->a->x += ($directionRepulsionX) / ($distanceDistance * -0.5);
					$point2->a->y += ($directionRepulsionY) / ($distanceDistance * -0.5);

				}
			}
			
			$point1->p->x = $point1X;
			$point1->p->y = $point1Y;
			$point1->m    = $point1M;
			$point1->a->x = $point1AX;
			$point1->a->y = $point1AY;
			
		}
	}

	public function applyHookesLaw() {
		foreach($this->graph->edges as $edge) {
			$spring = $this->spring($edge);

			// the direction of the spring
			$d = new Vector($spring->point2->p->x, $spring->point2->p->y);
			$d->subtract($spring->point1->p);

			$displacement = $spring->length - $d->magnitude();

			$direction = new Vector($d->x, $d->y);
			$direction->normalise();

			$forcePoint1 = new Vector($direction->x, $direction->y);
			$forcePoint1->multiply($spring->k * $displacement * -0.5);

			$forcePoint2 = new Vector($direction->x, $direction->y);
			$forcePoint2->multiply($spring->k * $displacement * 0.5);

			$spring->point1->applyForce($forcePoint1);
			$spring->point2->applyForce($forcePoint2);

		}
	}

	public function attractToCentre() {
		foreach($this->graph->nodes as $node) {
			$point = $this->point($node);

			$direction = new Vector($point->p->x, $point->p->y);
			$direction->multiply(-1);
			$direction->multiply($this->repulsion / 50);

			$point->applyForce($direction);

		}
	}

	public function exploseFromCentre() {
		foreach($this->graph->nodes as $node) {
			$point = $this->point($node);

			$direction = new Vector($point->p->x * (mt_rand() / mt_getrandmax()) * 2, $point->p->y * (mt_rand() / mt_getrandmax()) * 2);
			$direction->multiply(floor(((mt_rand() / mt_getrandmax()) * 300) + 200));

			$point->applyForce($direction);

		}
	}

	public function updateVelocity($timestep) {
		foreach($this->graph->nodes as $node) {
			$point = $this->point($node);

			$point->a->multiply($timestep);
			$point->v->add($point->a);
			$point->v->multiply($this->damping);

			$point->a = new Vector(0, 0);
		}
	}

	public function updatePosition($timestep) {
		foreach($this->graph->nodes as $node) {
			$point = $this->point($node);

			$velocity = new Vector($point->v->x, $point->v->y);
			$velocity->multiply($timestep);

			$point->p->add($velocity);
		}
	}

	public function totalEnergy() {
		$energy = 0;

		foreach($this->graph->nodes as $node) {
			$point = $this->point($node);

			$speed = $point->v->magnitude();
			$energy += 0.5 * $point->m * $speed * $speed;
		}

		return $energy;
	}

	public function tick($timestep) {
		$this->applyCoulombsLaw();
		$this->applyHookesLaw();
		$this->attractToCentre();
		$this->updateVelocity($timestep);
		$this->updatePosition($timestep);
	}

	public function start($maxSteps = 0) {
		$energy = PHP_INT_MAX;
		$this->step = 0;

		while ($energy > $this->minEnergyThreshold && ($maxSteps == 0 || $this->step < $maxSteps)) {
			$this->tick($this->tick);
			$energy = $this->totalEnergy();
			$this->step++;
		}
	}

	public function autoSize($width, $height, $margin = 0) {
		$minX = PHP_INT_MAX;
		$minY = PHP_INT_MAX;
		$maxX = -PHP_INT_MAX;
		$maxY = -PHP_INT_MAX;

		foreach($this->graph->nodes as $node) {
			$point = $this->point($node);
			if ($point->p->x < $minX) {
				$minX = $point->p->x;
			}
			if ($point->p->y < $minY) {
				$minY = $point->p->y;
			}

			if ($point->p->x > $maxX) {
				$maxX = $point->p->x;
			}
			if ($point->p->y > $maxY) {
				$maxY = $point->p->y;
			}
		}

		$decalX = -$minX;
		$decalY = -$minY;
		$minX = 0;
		$minY = 0;
		$maxX += $decalX;
		$maxY += $decalY;

		$ratioX = ($width - $margin) / $maxX;
		$ratioY = ($height - $margin) / $maxY;

		foreach($this->graph->nodes as $node) {
			$point = $this->point($node);
			$point->p->x += $decalX;
			$point->p->x *= $ratioX;
			$point->p->x += $margin / 2;

			$point->p->y += $decalY;
			$point->p->y *= $ratioY;
			$point->p->y += $margin / 2;
		}
	}

	public function getCrossingLines() {
		$crossingLines = array();

		foreach($this->graph->edges as $edge1) {
			$spring1 = $this->spring($edge1);
			foreach($this->graph->edges as $edge2) {
				$spring2 = $this->spring($edge2);

				$p1 = $spring1->point1;
				$p2 = $spring1->point2;
				$p3 = $spring2->point1;
				$p4 = $spring2->point2;

				$denom = (($p4->p->y - $p3->p->y)*($p2->p->x - $p1->p->x) - ($p4->p->x - $p3->p->x)*($p2->p->y - $p1->p->y));

				if ($denom === 0) {
					continue;
				}

				if (round($p1->p->x, 3) == round($p3->p->x, 3) && round($p1->p->y, 3) == round($p3->p->y, 3)) {
					continue;
				}

				if (round($p1->p->x, 3) == round($p4->p->x, 3) && round($p1->p->y, 3) == round($p4->p->y, 3)) {
					continue;
				}

				if (round($p2->p->x, 3) == round($p3->p->x, 3) && round($p2->p->y, 3) == round($p3->p->y, 3)) {
					continue;
				}

				if (round($p2->p->x, 3) == round($p4->p->x, 3) && round($p2->p->y, 3) == round($p4->p->y, 3)) {
					continue;
				}

				$ua = (($p4->p->x - $p3->p->x)*($p1->p->y - $p3->p->y) - ($p4->p->y - $p3->p->y)*($p1->p->x - $p3->p->x)) / $denom;
				$ub = (($p2->p->x - $p1->p->x)*($p1->p->y - $p3->p->y) - ($p2->p->y - $p1->p->y)*($p1->p->x - $p3->p->x)) / $denom;

				if ($ua < 0 || $ua > 1 || $ub < 0 || $ub > 1) {
					continue;
				}

				$crossingLines[] = array("edge1" => $edge1,
				                         "edge2" => $edge2);

			}
		}

		return $crossingLines;
	}
}

?>