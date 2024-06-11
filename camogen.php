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
* OVERVIEW
* 
* This software is a PHP port of the camogen application by Gael Lederrey, which is itself a Python
* port of the original camogen PHP web application by Ulf Åström, previously available at
* http://www.happyponyland.net/camogen.php (site currently down).
*
* Please see README.md for usage notes, Imagick/GD issues, and PHP version compability.
*
* COPYRIGHT NOTICE
* 
* camogen for PHP (c) 2024 Neil Withnall (gammalogic). This software contains code that has been
* directly derived from, or inspired by, the camogen Python application (c) 2017 Gael Lederrey
* available at https://github.com/glederrey/camogen
* 
* Please see LICENSE for full copyright notice and licensing terms.
***************************************************************************************************/

ini_set('display_errors', 1);
error_reporting(-1);

define('CAMOGEN_VERSION', '0.1a');

require_once('camogen.vertex.php');
require_once('camogen.polygon.php');
require_once('camogen.pattern.php');
require_once('camogen.helpers.php');
require_once('camogen.image.php');

// Dependency checks; if you know which extension you have installed, or want to use a specific
// extension, just copy-and-paste the relevant require_once() statement before this check and
// comment-out the try/catch block
try {
	if (extension_loaded('gd')) {
		require_once('camogen.image.gd.php');
	} else if (extension_loaded('imagick')) {
		require_once('camogen.image.imagick.php');
	} else {
		throw new Exception('Sorry, unable to load the Imagick or GD extensions. Please check your server configuration.');
	}
} catch (Exception $e) {
	die($e->getMessage());
}

// Dependency check for SVG export; if you do not intend to use the SVG export facility or do not
// have the SimpleXML extension installed you can comment-out this try/catch block
try {
	if (extension_loaded('simplexml')) {
		require_once('camogen.svgexport.php');
	} else {
		throw new Exception('Sorry, unable to load the SimpleXML extension. Please check your server configuration.');
	}
} catch (Exception $e) {
	die($e->getMessage());
}

class camogen
{
	function __construct($parameters=array(), $export_svg=false)
	{
		// @param array $parameters Pattern parameters

		// Initialize the pattern object; this will store the pattern parameters and polygons that
		// make up the camouflage pattern
		Pattern::initialize($parameters, $export_svg);

		// Generate a camouflage pattern based on the supplied parameters
		self::generate_pattern();
	}

	function generate_polygons($polygon, $depth)
	{
		// Main polygon generator function
		//
		// This function takes a single "seed" polygon as its input and splits the polygon into two
		// separate polygons (A and B), then keeps splitting each of these polygons until the
		// maximum recursion depth is reached to create a camouflage pattern that fills the drawing
		// area; the exact points at which the polygons are split is randomised, and random colors
		// are assigned to each polygon, which provides the ability to generate different patterns
		// even with the same starting parameters
		//
		// @param Polygon $polygon Drawing shape for the camouflage pattern
		// @param int $depth Polygon generation recursion depth

		// Stop the recursion if the circumference of the most recent polygon is smaller than the
		// minimum polygon pattern size or the maximum recursion depth has now been reached,
		// otherwise continue splitting the polygon
		if ($polygon->circumference() < Pattern::$polygon_size || $depth <= 0) {
			Pattern::add_polygon($polygon);
		} else {
			// Create a list of all the edge lengths
			$edge_lengths = array();

			for ($i = 0; $i < $polygon->nbr_vertices; $i++) {
				$edge_lengths[] = array(
					'key' => $i,
					'distance' => Helper::dist_vertices(
						$polygon->list_vertices[$i],
						$polygon->list_vertices[(($i + 1) % $polygon->nbr_vertices)]
					),
				);
			}

			// Sort edges by length (longest first), then by array key (highest first)
			$idx_edge_sorted = Helper::sort_edge_lengths($edge_lengths);

			// Find the two longest edges; the order in which these edges are assigned is important
			// because the script must move around the perimeter of the polygon in an anti-clockwise
			// direction in order for the polygon splitting to work properly
			$idx_edge_a = min($idx_edge_sorted[0], $idx_edge_sorted[1]);
			$idx_edge_b = max($idx_edge_sorted[0], $idx_edge_sorted[1]);

			// Get the vertices of the two longest edges
			$va1 = $polygon->list_vertices[$idx_edge_a];
			$va2 = $polygon->list_vertices[(($idx_edge_a + 1) % $polygon->nbr_vertices)];

			$vb1 = $polygon->list_vertices[$idx_edge_b];
			$vb2 = $polygon->list_vertices[(($idx_edge_b + 1) % $polygon->nbr_vertices)];

			// Create the new polygons A and B
			$polygon_a = new Polygon();
			$polygon_b = new Polygon();

			// Calculate the vertices of the shared edge between polygons A and B; these polygons
			// are not exact mirror images of each other, but polygon B is an approximate
			// reflection of polygon A along one edge i.e. as if you had folded the starting polygon
			// in two approximately halfway across the middle of it
			$edge_c = Helper::new_edge($va1, $va2, $vb1, $vb2);

			// Calculate the vertices that will be used to draw the new polygons; each polygon
			// consists of one "half" of the old polygon, and both polygons share a common edge

			// Polygon A
			foreach (range(0, $idx_edge_a) as $i) {
				$polygon_a->add_vertex($polygon->list_vertices[$i]);
			}

			for ($v = 0; $v < count($edge_c); $v++) {
				$polygon_a->add_vertex($edge_c[$v]);
			}

			for ($i = $idx_edge_b + 1; $i < $polygon->nbr_vertices; $i++) {
				$polygon_a->add_vertex($polygon->list_vertices[$i]);
			}

			$polygon_a->update_vertices_count();

			// Polygon B
			for ($i = $idx_edge_a + 1; $i < $idx_edge_b + 1; $i++) {
				$polygon_b->add_vertex($polygon->list_vertices[$i]);
			}

			$edge_c_reversed = array_reverse($edge_c);
			foreach ($edge_c_reversed as $v) {
				$polygon_b->add_vertex($v);
			}

			$polygon_b->update_vertices_count();

			$this->generate_polygons($polygon_b, $depth - 1);
			$this->generate_polygons($polygon_a, $depth - 1);
		}
	}

	function generate_image()
	{
		// Initialize the drawing object
		Image_Generator::initialize(Pattern::$width, Pattern::$height, 'png');

		// Anti-aliasing must be disabled for some post-processing effects; see README.md
		if (Pattern::$postprocess_rain && Image_Generator::get_extension() === 'GD') {
			Image_Generator::set_antialiasing_mode(false);
		}

		// Draw each polygon initially with a unique index color; these unique colors are used to
		// detect which polygons border each other
		Helper::draw_polygons($use_index=true, $apply_polygon_draw_style=false);

		Image_Generator::update_drawing();

		// Identify each polygon's neighboring polygons to determine an effective coloring scheme
		Helper::find_neighbors();

		// Reset each polygon's color as the unique index color is now no longer required
		foreach (Pattern::$list_polygons as $i => $polygon) {
			Pattern::$list_polygons[$i]->color_index = null;
		}

		// Assign the correct colors to the polygons
		foreach (Pattern::$list_polygons as $i => $polygon) {
			// This check is not redundant as it may initially look because Helper::color_polygon
			// will also be updating the colors of neighboring polygons if Pattern::$color_bleed > 0
			if ($polygon->color_index === null) {
				Helper::color_polygon(
					$i,
					rand(0, Pattern::$nbr_colors),
					Pattern::$color_bleed
				);
			}
		}

		// Fill the image background with the first color from the pattern's palette
		Image_Generator::draw_rectangle(
			0,
			0,
			Pattern::$width,
			Pattern::$height,
			Pattern::$colors[0]
		);

		// Re-draw the polygons, this time with their correct color and drawing style
		Helper::draw_polygons($use_index=false, $apply_polygon_draw_style=true);

		// Pre-processing of the image (hexagons, optional)
		if (Pattern::$preprocess_hexagons) {
			Helper::draw_hexagons();
		}

		Image_Generator::update_drawing();

		// Post-processing of the image (spots, optional)
		if (Pattern::$postprocess_spots) {
			Helper::add_spots();
		}

		// Post-processing of the image (rain, optional)
		if (Pattern::$postprocess_rain) {
			Helper::add_rain();
		}

		// Post-processing of the image (pixelize effect, optional)
		if (Pattern::$postprocess_pixelize) {
			Helper::pixelize();
		}

		if (Pattern::$postprocess_spots || Pattern::$postprocess_rain || Pattern::$postprocess_pixelize) {
			Image_Generator::update_drawing();
		}

		// Post-processing of the image (motion blur effect, optional)
		if (Pattern::$postprocess_motion_blur) {
			Image_Generator::apply_motion_blur(
				Pattern::$motion_blur_radius,
				Pattern::$motion_blur_sigma,
				Pattern::$motion_blur_angle
			);
		}
	}

	function save_image($filename=null)
	{
		try {
			if ($filename == null) {
				throw new Exception('Sorry, an error occurred (image filename missing)');
			}

			Image_Generator::save_image_to_file($filename);
		} catch (Exception $e) {
			die($e->getMessage());
		}
	}

	function save_svg($filename=null)
	{
		try {
			if (!Pattern::$export_svg) {
				throw new Exception('Sorry, an error occurred (SVG cannot be saved because SVG export option is set to false)');
			}

			if ($filename == null) {
				throw new Exception('Sorry, an error occurred (SVG filename missing)');
			}

			SVG_Export::save_svg_to_file($filename);
		} catch (Exception $e) {
			die($e->getMessage());
		}
	}

	function generate_pattern()
	{
		// Create a starting or "seed" polygon from which all other polygons are derived; under
		// normal circumstances the starting polygon will fill the whole of the drawing area and
		// will always be either a square or rectangle depending on the image's aspect ratio
		$starting_polygon = new Polygon();

		if (!Pattern::$preprocess_distort) {
			$first_vertices = [
				new Vertex(Pattern::$width, 0),
				new Vertex(0, 0),
				new Vertex(0, Pattern::$height),
				new Vertex(Pattern::$width, Pattern::$height),
			];
		} else {
			// Apply a perspective distortion effect; this feature is experimental and can be
			// enabled by setting Pattern::$preprocess_distort = true
			$first_vertices = [
				new Vertex(Pattern::$width * Helper::distort(), 0),
				new Vertex(0, Pattern::$height * Helper::distort()),
				new Vertex(Pattern::$width * Helper::distort(), Pattern::$height),
				new Vertex(Pattern::$width * Helper::distort(), Pattern::$height * Helper::distort()),
			];
		}

		$starting_polygon->add_vertices($first_vertices);
		$starting_polygon->update_vertices_count();

		// Create the polygons that will form the basis of the pattern
		$this->generate_polygons($starting_polygon, Pattern::$max_depth);

		// Shuffle the polygons
		Pattern::shuffle_polygons();

		$this->generate_image();
	}
}
