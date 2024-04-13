<?php

namespace Digilist\DependencyGraph\Tests;

use Digilist\DependencyGraph\CircularDependencyException;
use Digilist\DependencyGraph\DependencyGraph;
use Digilist\DependencyGraph\DependencyNode;
use PHPUnit\Framework\TestCase;

class DependencyGraphTest extends TestCase
{

    /**
     * In case there are no dependencies all nodes should be returned in their added order.
     */
    public function testWithoutDependencies(): void
    {
        $graph = new DependencyGraph();

        $graph->addNode($nodeA = new DependencyNode('A'));
        $graph->addNode($nodeB = new DependencyNode('B'));
        $graph->addNode($nodeC = new DependencyNode('C'));
        $graph->addNode($nodeD = new DependencyNode('D'));

        $resolved = $graph->resolve();
        $this->assertEquals(array('A', 'B', 'C', 'D'), $resolved);
        $this->assertEquals(array($nodeA, $nodeB, $nodeC, $nodeD), $graph->getNodes());
    }

    /**
     * Tests whether the declaration of dependencies directly on the graph works correctly
     * and the dependencies are set on the node.
     */
    public function testDeclarationOnGraph(): void
    {
        $graph = new DependencyGraph();

        $nodeA = new DependencyNode('A');
        $nodeB = new DependencyNode('B');
        $nodeC = new DependencyNode('C');
        $nodeD = new DependencyNode('D');
        $nodeE = new DependencyNode('E');
        $nodeF = new DependencyNode('F');
        $nodeG = new DependencyNode('G');

        $graph->addDependency($nodeA, $nodeB);
        $graph->addDependency($nodeA, $nodeD);
        $graph->addDependency($nodeB, $nodeC);
        $graph->addDependency($nodeB, $nodeE);
        $graph->addDependency($nodeC, $nodeD);
        $graph->addDependency($nodeC, $nodeE);
        $graph->addDependency($nodeF, $nodeG);

        $this->assertEquals(array($nodeB, $nodeD), $nodeA->getDependencies());
        $this->assertEquals(array($nodeC, $nodeE), $nodeB->getDependencies());
        $this->assertEquals(array($nodeD, $nodeE), $nodeC->getDependencies());
        $this->assertEquals(array(), $nodeD->getDependencies());
        $this->assertEquals(array(), $nodeE->getDependencies());
    }

    /**
     * Tests whether the declaration of dependencies directly on the nodes works correctly
     * and the dependencies are detected by the graph.
     */
    public function testDeclarationOnNodes(): void
    {
        $graph = new DependencyGraph();

        $nodeA = new DependencyNode('A');
        $nodeB = new DependencyNode('B');
        $nodeC = new DependencyNode('C');
        $nodeD = new DependencyNode('D');
        $nodeE = new DependencyNode('E');

        $nodeA->dependsOn($nodeB);
        $nodeA->dependsOn($nodeD);
        $nodeB->dependsOn($nodeC);
        $nodeB->dependsOn($nodeE);
        $nodeC->dependsOn($nodeD);
        $nodeC->dependsOn($nodeE);

        $graph->addNode($nodeA);
        $dependencies = $graph->getDependencies();

        $this->assertEquals(array($nodeB, $nodeD), $dependencies[$nodeA]->getArrayCopy());
        $this->assertEquals(array($nodeC, $nodeE), $dependencies[$nodeB]->getArrayCopy());
        $this->assertEquals(array($nodeD, $nodeE), $dependencies[$nodeC]->getArrayCopy());
        $this->assertEquals(array(), $dependencies[$nodeD]->getArrayCopy());
        $this->assertEquals(array(), $dependencies[$nodeE]->getArrayCopy());
    }

    /**
     * Tests the behaviour if no nodes where added.
     */
    public function testResolvingWithoutNodes(): void
    {
        $graph = new DependencyGraph();
        $resolved = $graph->resolve();
        $this->assertEquals(array(), $resolved);
    }

    /**
     * Tests whether the internal declaration of dependencies (on the graph) works correctly.
     */
    public function testResolving(): void
    {
        $graph = new DependencyGraph();

        $nodeA = new DependencyNode('A');
        $nodeB = new DependencyNode('B');
        $nodeC = new DependencyNode('C');
        $nodeD = new DependencyNode('D');
        $nodeE = new DependencyNode('E');

        $graph->addDependency($nodeA, $nodeB);
        $graph->addDependency($nodeA, $nodeD);
        $graph->addDependency($nodeB, $nodeC);
        $graph->addDependency($nodeB, $nodeE);
        $graph->addDependency($nodeC, $nodeD);
        $graph->addDependency($nodeC, $nodeE);

        $resolved = $graph->resolve();
        $this->assertEquals(array('D', 'E', 'C', 'B', 'A'), $resolved);
    }

    /**
     * Test whether dependencies are solved correctly if there are multiple roots.
     */
    public function testWithMultipleRoots(): void
    {
        $graph = new DependencyGraph();

        $nodeA = new DependencyNode('A');
        $nodeB = new DependencyNode('B');
        $nodeC = new DependencyNode('C');
        $nodeD = new DependencyNode('D');
        $nodeE = new DependencyNode('E');
        $nodeF = new DependencyNode('F');
        $nodeG = new DependencyNode('G');

        $graph->addDependency($nodeA, $nodeB);
        $graph->addDependency($nodeA, $nodeD);
        $graph->addDependency($nodeB, $nodeC);
        $graph->addDependency($nodeB, $nodeE);
        $graph->addDependency($nodeC, $nodeD);
        $graph->addDependency($nodeC, $nodeE);
        $graph->addDependency($nodeF, $nodeG);

        $resolved = $graph->resolve();
        $this->assertEquals(array('D', 'E', 'C', 'B', 'A', 'G', 'F'), $resolved);
    }

    /**
     * Tests whether a circular dependency is detected.
     */
    public function testCircularDependencyException(): void
    {
        $graph = new DependencyGraph();

        $nodeA = new DependencyNode('A');
        $nodeB = new DependencyNode('B');
        $nodeC = new DependencyNode('C');
        $nodeD = new DependencyNode('D');
        $nodeE = new DependencyNode('E');

        $graph->addDependency($nodeA, $nodeB);
        $graph->addDependency($nodeA, $nodeD);
        $graph->addDependency($nodeB, $nodeC);
        $graph->addDependency($nodeB, $nodeE);
        $graph->addDependency($nodeC, $nodeD);
        $graph->addDependency($nodeC, $nodeE);
        $graph->addDependency($nodeD, $nodeB);

        static::expectException(CircularDependencyException::class);

        $graph->resolve();
    }

	/**
	 * Tests whether a circular dependency is detected for a->b b->a.
	 */
    public function testCircularDependencyException2(): void
    {
        $graph = new DependencyGraph();

        $nodeA = new DependencyNode('A');
        $nodeB = new DependencyNode('B');

        $graph->addDependency($nodeA, $nodeB);
        $graph->addDependency($nodeB, $nodeA);

        static::expectException(CircularDependencyException::class);

        $graph->resolve();
    }

    /**
     * Tests whether a circular dependency is detected for a->b b->a c->d.
     */
    public function testCircularDependencyException3(): void
    {
        $graph = new DependencyGraph();

        $nodeA = new DependencyNode('A');
        $nodeB = new DependencyNode('B');
        $nodeC = new DependencyNode('C');
        $nodeD = new DependencyNode('D');

        $graph->addDependency($nodeA, $nodeB);
        $graph->addDependency($nodeB, $nodeA);
        $graph->addDependency($nodeC, $nodeD);

        static::expectException(CircularDependencyException::class);

        $graph->resolve();
    }
}
