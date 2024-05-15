<?php

/***************************************************************************************************
*                  CAMOuflage GENerator for PHP
*   _________ _____ ___  ____  ____ ____  ____ 
*  / ___/ __ `/ __ `__ \/ __ \/ __ `/ _ \/ __ \
* / /__/ /_/ / / / / / / /_/ / /_/ /  __/ / / /
* \___/\__,_/_/ /_/ /_/\____/\__, /\___/_/ /_/ 
*                           /____/             
*                                              
* @author Neil Withnall with thanks to Gael Lederrey and Ulf Åström
* @version 0.1 ("Stealthy Salamander")
* @licence MIT
* @system-requirements PHP 5.4+ with Imagick or GD extensions
* 
* camogen for PHP (c) 2024 Neil Withnall (gammalogic). This software contains code that has been
* directly derived from, or inspired by, the camogen Python application (c) 2017 Gael Lederrey.
* 
* Please see LICENSE for full copyright notice and licensing terms.
***************************************************************************************************/

class Polygon
{
	public $color_index;
	public $list_vertices = array();
	public $nbr_vertices = 0;
	public $list_neighbors = array();

	function __construct()
	{
		// Constructor for the polygon
	}

	function circumference()
	{
		// Calculate the circumference of the polygon by summing the distances between all vertices
		//
		// @return float $total
		$total = (float) 0;

		for ($i = 0; $i < $this->nbr_vertices; $i++) {
			$va = $this->list_vertices[$i];
			$vb = $this->list_vertices[($i + 1) % $this->nbr_vertices];

			$total += Helper::dist_vertices($va, $vb);
		}

		return $total;
	}

	function add_vertex($v)
	{
		// Add a vertex to the list of vertices
		//
		// @param Vertex $v
		$this->list_vertices[] = $v;
	}

	function add_vertices($vs)
	{
		// Add a complete set of vertices to the list of vertices
		//
		// @param array(Vertex) $vs
		$this->list_vertices = $vs;
	}

	function add_neighbor($idx)
	{
		// Add a neighbor to the list of neighbors
		//
		// @param int $idx The polygon's index in Pattern::$list_polygons
		$this->list_neighbors[] = $idx;
	}

	function update_vertices_count()
	{
		$this->nbr_vertices = count($this->list_vertices);
	}
}
