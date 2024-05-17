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

class Helper
{
	public static function distort()
	{
		// Generate a random number between 0 and 1
		//
		// @return float
		return lcg_value();
	}

	public static function dist_vertices($v1, $v2)
	{
		// Calculate the distance between two vertices; note that the distance value must be
		// rounded to avoid problems when sorting floating point numbers (see the
		// Helper::sort_edge_lengths function for more details about this)
		//
		// @param Vertex $v1
		// @param Vertex $v2
		// @return float
		$a_x = $v1->x - $v2->x;
		$a_y = $v1->y - $v2->y;

		return round(sqrt(($a_x * $a_x) + ($a_y * $a_y)), 8);
	}

	public static function edge_split($v1, $v2, $frac)
	{
		// Split the edge between $v1 and $v2; $frac is a randomly calculated ratio representing
		// the point at which the split is made e.g. 0.5 = 50% of the distance along one side
		//
		// @param Vertex $v1
		// @param Vertex $v2
		// @param float $frac
		// @return Vertex
		if ($v1->x < $v2->x) {
			$new_x = $v1->x + abs($v2->x - $v1->x) * $frac;
		} else {
			$new_x = $v1->x - abs($v2->x - $v1->x) * $frac;
		}

		if ($v1->y < $v2->y) {
			$new_y = $v1->y + abs($v2->y - $v1->y) * $frac;
		} else {
			$new_y = $v1->y - abs($v2->y - $v1->y) * $frac;
		}

		return new Vertex($new_x, $new_y);
	}

	public static function new_edge($va1, $va2, $vb1, $vb2)
	{
		// Create a new edge between edges A and B
		//
		// @param Vertex $va1
		// @param Vertex $va2
		// @param Vertex $vb1
		// @param Vertex $vb2
		// @return array(Vertex, Vertex)

		// Calculate the fractions
		$frac_a = 0.4 + (rand(0, 2) / 10);
		$frac_b = 0.4 + (rand(0, 2) / 10);

		// Split edge A
		$new_vert_a = Helper::edge_split($va1, $va2, $frac_a);

		// Split edge B
		$new_vert_b = Helper::edge_split($vb1, $vb2, $frac_b);

		return array($new_vert_a, $new_vert_b);
	}

	public static function sort_edge_lengths($edge_lengths)
	{
		// Sort edges by length (longest first), then by array key (highest first); if $edge_lengths
		// were to contain the following values
		//
		// array(
		//   [0]=>
		//     array(
		//       'key' => int 0, 'distance' => float(420)
		//     )
		//   [1]=>
		//     array(
		//       'key' => int 1, 'distance' => float(593.9696961967)
		//     )
		//   [2]=>
		//     array(
		//       'key' => int 2, 'distance' => float(420)
		//     )
		// )
		//
		// then after sorting it should look like this
		//
		// array(
		//   [0]=>
		//     array(
		//       'key' => int 1, 'distance' => float(593.9696961967)
		//     )
		//   [1]=>
		//     array(
		//       'key' => int 2, 'distance' => float(420)
		//     )
		//   [2]=>
		//     array(
		//       'key' => int 0, 'distance' => float(420)
		//     )
		// )
		//
		// Due to the way PHP internally stores floating point numbers, it's possible that two
		// floating numbers that *appear* identical when using var_dump() are actually not, which
		// causes problems when using PHP sort functions; to resolve this, the numbers need to
		// be rounded first
		//
		// @param array $edge_lengths
		// @return array $idx_edge_sorted
		$keys = array_column($edge_lengths, 'key');
		$distances = array_column($edge_lengths, 'distance');

		array_multisort($distances, SORT_DESC, $keys, SORT_DESC, $edge_lengths);

		$idx_edge_sorted = array();

		foreach ($edge_lengths as $key => $value) {
			$idx_edge_sorted[] = $value['key'];
		}

		return $idx_edge_sorted;
	}

	public static function draw_polygons($use_index=false, $apply_polygon_draw_style=false)
	{
		// Draw the polygons; polygon colors are based on either a unique index (for boundary
		// checking) or their "true" (assigned) pattern color as defined in the parameters
		//
		// @param bool $use_index
		// @param bool $apply_polygon_draw_style
		$extension = Image_Generator::get_extension();

		foreach (Pattern::$list_polygons as $i => $polygon) {
			if (!$use_index) {
				$hex_color = Pattern::$colors[$polygon->color_index];
			} else {
				$hex_color = self::get_hex_color_from_integer($i); // if $i = 1 then color is #000001, etc.
			}

			$points = array();

			if ($extension === 'Imagick') {
				foreach ($polygon->list_vertices as $v) {
					$points[] = array(
						'x' => $v->x,
						'y' => $v->y,
					);
				}
			} else if ($extension === 'GD') {
				foreach ($polygon->list_vertices as $v) {
					$points[] = $v->x;
					$points[] = $v->y;
				}
			}

			Image_Generator::draw_polygon($points, $hex_color, $apply_polygon_draw_style);
		}
	}

	public static function find_neighbors()
	{
		// Find the neighbors of all polygons in the pattern; each corner of the polygon is sampled
		// a few pixels outside of it and the color found there will enable the function to identify
		// the neighboring polygon, because the color's hex value will be the same as the index in
		// Pattern::$list_polygons e.g. #000001 = Pattern::$list_polygons[1]
		foreach (Pattern::$list_polygons as $i => $polygon) {
			// Check all of the edges
			for ($e = 0; $e < $polygon->nbr_vertices; $e++) {
				// Compare the (c)urrent corner to the (n)ext and (p)revious corners to determine
				// which direction the current corner is facing, then sample a pixel offset from
				// that direction; as an example, if the previous corner is to the right and the
				// next is below, the current corner should be pointing northwest
				$c = $polygon->list_vertices[$e];
				$n = $polygon->list_vertices[($e + 1) % $polygon->nbr_vertices];
				$p = $polygon->list_vertices[self::python_modulo($e - 1, $polygon->nbr_vertices)];

				// Initialize the x,y offsets
				$x_offset = 0;
				$y_offset = 0;

				// Calculate the x,y offsets based on the direction the corner is currently facing

				// NE
				if ($n->x < $c->x) $x_offset++;
				if ($p->y > $c->y) $y_offset--;

				// NW
				if ($p->x > $c->x) $x_offset--;
				if ($n->y > $c->y) $y_offset--;

				// SW
				if ($n->x > $c->x) $x_offset--;
				if ($p->y < $c->y) $y_offset++;

				// SE
				if ($p->x < $c->x) $x_offset++;
				if ($n->y < $c->y) $y_offset++;

				// Calculate the x,y coordinates of the sample pixel
				$x = $c->x + $x_offset;
				$y = $c->y + $y_offset;

				// Make sure the sample point is within the boundaries of the drawing area
				if ((0 <= $x && $x < Pattern::$width) && (0 <= $y && $y < Pattern::$height)) {
					$hex_color = Image_Generator::get_pixel_color($x, $y);

					// Transform the hex value into an index value
					$idx = (int) hexdec(str_replace('#', '', $hex_color));

					// If the index value is valid, add the identified polygon as a neighbor
					if ($idx < Pattern::$nbr_polygons && $idx != $i) {
						$polygon->add_neighbor($idx);
						Pattern::$list_polygons[$idx]->add_neighbor($i);
					}
				}
			}
		}

		// Remove any duplicate neighboring polygons found
		foreach (Pattern::$list_polygons as $i => $polygon) {
			Pattern::$list_polygons[$i]->list_neighbors = array_unique(
				Pattern::$list_polygons[$i]->list_neighbors,
				SORT_NUMERIC
			);
		}
	}

	public static function color_polygon($index, $color, $bleed)
	{
		// Color the polygons recursively; for $bleed values > 0, this function will identify which
		// polygons neighbor each other and color them the same, with the effect of grouping
		// polygons of the same color together to create larger areas of color rather than the
		// polygons appearing to be smaller and more dispersed
		//
		// @param int $index Starting index for the polygons
		// @param int $color Color index
		// @param int $bleed Recursion depth
		$polygon = Pattern::$list_polygons[$index];
		$polygon->color_index = $color;

		// Check whether the bleed recursion depth has been reached
		if ($bleed > 0) {
			// Find all neighboring polygons that do not have a color assigned to them yet, then
			// identify which of these polygons has the most neighbors of the same color and assign
			// the same color to that polygon; this process continues until the recursion depth is
			// reached, at which point it starts over again with another polygon
			$candidates = array();
			$nbr_neighbors_same_color = array();

			foreach ($polygon->list_neighbors as $neigh_index) {
				if (Pattern::$list_polygons[$neigh_index]->color_index === null) {
					$candidates[] = $neigh_index;
					$nbr_neighbors_same_color[] = self::colored_neighbors($neigh_index, $color);
				}
			}

			if (count($candidates) > 0) {
				$idx_sorted = self::reverse_sort_by_key($nbr_neighbors_same_color);
				$idx_sorted_first_key = $idx_sorted[0]['key'];

				self::color_polygon($candidates[$idx_sorted_first_key], $color, $bleed - 1);
			}
		}
	}

	public static function colored_neighbors($index, $color)
	{
		// Count the number of neighboring polygons with the specified color
		//
		// @param int $index Index of a Polygon
		// @param int $color Color index
		// @return int $count
		$polygon = Pattern::$list_polygons[$index];

		$count = (int) 0;

		foreach ($polygon->list_neighbors as $neigh_index) {
			if (Pattern::$list_polygons[$neigh_index]->color_index === $color) {
				$count++;
			}
		}

		return $count;
	}

	public static function draw_hexagons()
	{
		// Add hexagons to the image (pre-processing effect)
		$extension = Image_Generator::get_extension();

		$hexagon_height_half = (int) (Pattern::$hexagon_height / 2);

		// Calculate half the pixel distance across the width of the hexagon; a regular hexagon's
		// height is longer than its width, but for aesthetic reasons a hexagon with equal
		// width/height is used here; if you want to generate geometrically correct hexagons,
		// un-comment the first line of code and comment the second line of code
		//$hexagon_width_half = (int) ($hexagon_height_half * sin(deg2rad(60)));
		$hexagon_width_half = $hexagon_height_half;

		// Calculate the vertical pixel separation distance from the top of each side to the apex;
		// 0.5 is equivalent to sin(deg2rad(30))
		$y_angle_length = ($hexagon_height_half * 0.5);

		// Calculate the number of hexagons that will fit within the drawing area (with some
		// over-drawing beyond the boundaries to prevent gaps); these values are halved because the
		// hexagon drawing loop uses both negative and positive numbers as offsets to draw outwards
		// from the center of the image
		$compensation_factor = 1;
		$columns_total = (int) ceil((Pattern::$width / ($hexagon_width_half * 2)) / 2) + $compensation_factor;
		$rows_total = (int) ceil((Pattern::$height / ($hexagon_height_half * 2)) / 2) + $compensation_factor;

		// Calculate the coordinates of the center of the drawing area
		$x_start = ((Pattern::$width / 2) - $hexagon_width_half);
		$y_start = (Pattern::$height / 2);

		$x1 = $x_start; // Left edge of the hexagon
		$x2 = ($x1 + $hexagon_width_half); // Horizontal center of the hexagon
		$x3 = ($x2 + $hexagon_width_half); // Right edge of the hexagon
		$y1 = $y_start; // Vertical center of the hexagon

		// Pre-calculated value to offset the vertical positioning for each row of hexagons
		$hexagon_row_offset_height = (($y_angle_length + $y_angle_length + $y_angle_length) * 2);

		// Draw even rows
		$row_types = array('even', 'odd');

		foreach ($row_types as $row_type) {
			for ($i = -$columns_total; $i <= $columns_total; $i++) {
				for ($j = -$rows_total; $j <= $rows_total; $j++) {
					if ($row_type == 'even') {
						$hexagon_col_offset = ($hexagon_width_half * ($i * 2));
						$hexagon_row_offset = ($hexagon_row_offset_height * $j);
					} else {
						$hexagon_col_offset = (($hexagon_width_half * ($i * 2)) - $hexagon_width_half);
						$hexagon_row_offset = ($hexagon_row_offset_height * $j) + ($hexagon_row_offset_height / 2);
					}

					$draw_hexagon = false;

					// Check if we need to draw this hexagon; this automatically applies when
					// Pattern::$hexagon_percentage = 1 or Pattern::$hexagon_percentage is > 0 && < 1
					// and lcg_value() is less than this
					// e.g.
					// Pattern::$hexagon_percentage = 0.5
					// lcg_value() = 0.44409172795429
					if (Pattern::$hexagon_percentage === 1 || Pattern::$hexagon_percentage > lcg_value()) {
						$draw_hexagon = true;
					}

					if ($draw_hexagon) {
						// Make sure the hexagon is within the boundaries of the drawing area
						if (($x3 + $hexagon_col_offset) < 0) {
							// Right side of hexagon
							$draw_hexagon = false;
						} else if (($x1 + $hexagon_col_offset) > Pattern::$width) {
							// Left side of hexagon
							$draw_hexagon = false;
						} else if (($y1 - $y_angle_length - $y_angle_length - $hexagon_row_offset) > Pattern::$height) {
							// Top of hexagon
							$draw_hexagon = false;
						} else if (($y1 + $y_angle_length + $y_angle_length - $hexagon_row_offset) < 0) {
							// Bottom of hexagon
							$draw_hexagon = false;
						}
					}

					if ($draw_hexagon) {
						// Hexagons are drawn in an anti-clockwise direction starting from the top
						// vertex of the left side
						if ($extension === 'Imagick') {
							$points = array(
								array(
									'x' => $x1 + $hexagon_col_offset,
									'y' => $y1 - $y_angle_length - $hexagon_row_offset,
								),
								array(
									'x' => $x1 + $hexagon_col_offset,
									'y' => $y1 + $y_angle_length - $hexagon_row_offset,
								),
								array(
									'x' => $x2 + $hexagon_col_offset,
									'y' => $y1 + $y_angle_length + $y_angle_length - $hexagon_row_offset,
								),
								array(
									'x' => $x3 + $hexagon_col_offset,
									'y' => $y1 + $y_angle_length - $hexagon_row_offset,
								),
								array(
									'x' => $x3 + $hexagon_col_offset,
									'y' => $y1 - $y_angle_length - $hexagon_row_offset,
								),
								array(
									'x' => $x2 + $hexagon_col_offset,
									'y' => $y1 - $y_angle_length - $y_angle_length - $hexagon_row_offset,
								),
							);
						} else if ($extension === 'GD') {
							$points = array(
								$x1 + $hexagon_col_offset,
								$y1 - $y_angle_length - $hexagon_row_offset,
								$x1 + $hexagon_col_offset,
								$y1 + $y_angle_length - $hexagon_row_offset,
								$x2 + $hexagon_col_offset,
								$y1 + $y_angle_length + $y_angle_length - $hexagon_row_offset,
								$x3 + $hexagon_col_offset,
								$y1 + $y_angle_length - $hexagon_row_offset,
								$x3 + $hexagon_col_offset,
								$y1 - $y_angle_length - $hexagon_row_offset,
								$x2 + $hexagon_col_offset,
								$y1 - $y_angle_length - $y_angle_length - $hexagon_row_offset,
							);
						}

						Image_Generator::draw_polygon(
							$points,
							Pattern::$colors[rand(0, Pattern::$nbr_colors)],
							$apply_polygon_draw_style=false
						);
					}
				}
			}
		}
	}

	public static function add_spots()
	{
		// Add spots to the image (post-processing effect)
		$color_palette = array();
		$replacement_color_palette = array();

		foreach (Pattern::$colors as $color_idx => $hex_color) {
			list($r,$g,$b) = self::get_rgb_from_hex_color($hex_color);

			$color_palette[strtolower($hex_color)] = array(
				'r' => $r,
				'g' => $g,
				'b' => $b,
			);
		}

		$spots_amount = Pattern::$spots_amount;

		// Precalculated value for optimization
		$spots_sampling_double = (Pattern::$spots_sampling * 2) + 1;

		while ($spots_amount >= 1) {
			// Calculate a random radius for the spot
			$spot_radius = (int) (rand(Pattern::$spots_radius_min, Pattern::$spots_radius_max) / 2);

			// Calculate a random position for the spot
			$spot_x = rand($spot_radius, Pattern::$width - $spot_radius);
			$spot_y = rand($spot_radius, Pattern::$height - $spot_radius);

			// Pick a sampling point slightly offset to the spot's center and make sure the point is
			// within the boundaries of the drawing area
			$sample_x = $spot_x - Pattern::$spots_sampling + rand(0, $spots_sampling_double);
			$sample_x = min(Pattern::$width - 1, max(0, $sample_x));

			$sample_y = $spot_y - Pattern::$spots_sampling + rand(0, $spots_sampling_double);
			$sample_y = min(Pattern::$height - 1, max(0, $sample_y));

			// Get the color at the sampling point
			$hex_color = Image_Generator::get_pixel_color($sample_x, $sample_y);

			// Check if the sampled color exists in the palette, and reassign it to an existing
			// color if it doesn't exist; this check is needed when anti-aliasing is enabled because
			// the sampling point may land on an anti-aliased pixel between two valid colors
			if (!array_key_exists($hex_color, $color_palette)) {
				if (array_key_exists($hex_color, $replacement_color_palette)) {
					// This color has already been matched to a replacement color, so does not need
					// to be re-evaluated
					$hex_color = $replacement_color_palette[$hex_color];
				} else {
					// Find the closest matching color from the pattern's palette
					$replacement_hex_color = self::get_replacement_color_from_hex_color($hex_color, $color_palette);

					// Save the replacement color to the replacement color look-up table
					$replacement_color_palette[$hex_color] = $replacement_hex_color;

					// Apply the replacement color
					$hex_color = $replacement_hex_color;
				}
			}

			Image_Generator::draw_ellipse(
				$spot_x,
				$spot_y,
				$spot_radius,
				$spot_radius,
				0,
				360,
				$hex_color
			);

			$spots_amount--;
		}
	}

	public static function add_rain()
	{
		// Add stylized rain strokes to the image (post-processing effect); in camouflage
		// terminology these elements are sometimes referred to as "rain straits"
		Image_Generator::set_stroke_color(Pattern::$rain_stroke_color);
		Image_Generator::set_stroke_width(Pattern::$rain_stroke_line_thickness);
		if (Image_Generator::get_extension() === 'Imagick') {
			Image_Generator::set_fill_color('transparent');
		}

		// Precalculated values for optimization
		$rain_stroke_width_half = (int) (Pattern::$rain_stroke_width / 2);
		$rain_stroke_width_frac = (int) floor($rain_stroke_width_half); // Factor to randomize curve width
		$rain_stroke_length_half = (int) (Pattern::$rain_stroke_length / 2);
		$rain_stroke_length_quarter = (int) (Pattern::$rain_stroke_length / 4);
		$rain_stroke_segment_length = (int) (Pattern::$rain_stroke_length / Pattern::$rain_stroke_segments);

		// Calculate a bounding box for each rain stroke to determine how many rain strokes are
		// needed to completely fill the image; this calculation allows for a padding factor to
		// control the spacing between rain strokes
		$rain_x_spacing = (int) ceil(Pattern::$rain_stroke_width * Pattern::$rain_stroke_width_padding_factor);
		$compensation_factor = 2;
		$nbr_rain_stroke_columns = (int) ceil((Pattern::$width / $rain_x_spacing)) + $compensation_factor;

		// Initialize the starting position of the drawing cursor
		$rain_x_pos = (int) 0;
		$rain_y_pos = (int) 0;

		for ($i = 1; $i <= $nbr_rain_stroke_columns; $i++) {
			// Stagger the vertical start position of the rain for every other stroke to make the
			// pattern look more randomized
			if ($i % 2 == 1) {
				$rain_y_pos_inc = (int) $rain_y_pos -(rand(0, $rain_y_pos));
			} else {
				$rain_y_pos_inc = (int) -($rain_y_pos * 2);
			}

			// Draw the rain strokes vertically down the screen
			while ($rain_y_pos_inc < Pattern::$height) {
				$rain_y_pos_inc_start = $rain_y_pos_inc;

				// Calculate a random number of curves for each rain stroke
				$nbr_curves = rand(2, Pattern::$rain_stroke_segments);

				// Randomize the end point and control point positions slightly to draw imperfect
				// curves
				$rain_x_pos_rand = $rain_x_pos + rand(-2, 2);
				$rain_stroke_segment_length_rand = $rain_stroke_segment_length + rand(-2, 2);

				$points = array();

				for ($j = 1; $j <= $nbr_curves; $j++) {
					// The bezier control points will draw the curves first to the left, then to the
					// right, then back again, to create the illusion of a straight or wavy line
					// depending on the rain stroke width value
					$rain_stroke_width_rand = Pattern::$rain_stroke_width + rand(-$rain_stroke_width_frac, $rain_stroke_width_frac);

					if ($j % 2 == 1) {
						// Left side of curve
						$points[] = array(
							'x1' => $rain_x_pos_rand - $rain_stroke_width_rand,
							'y1' => $rain_y_pos_inc,
							'x2' => $rain_x_pos_rand - $rain_stroke_width_rand,
							'y2' => $rain_y_pos_inc += $rain_stroke_segment_length,
							'xe' => $rain_x_pos_rand,
							'ye' => $rain_y_pos_inc,
						);
					} else {
						// Right side of curve
						$points[] = array(
							'x1' => $rain_x_pos_rand + $rain_stroke_width_rand,
							'y1' => $rain_y_pos_inc,
							'x2' => $rain_x_pos_rand + $rain_stroke_width_rand,
							'y2' => $rain_y_pos_inc += $rain_stroke_segment_length,
							'xe' => $rain_x_pos_rand,
							'ye' => $rain_y_pos_inc,
						);
					}
				}

				Image_Generator::draw_bezier_curve(
					$rain_x_pos,
					$rain_y_pos_inc_start,
					$points
				);

				// Move the y position further down the image with a randomized distance between
				// the rain strokes
				$rain_y_pos_inc += $rain_stroke_length_half + rand(-15, 5);
			}

			// Move the x position across to the right of the image with a randomized distance
			// between the rain strokes and reset the y position to the top of the image
			if (Pattern::$rain_stroke_width <= 5) {
				$rain_x_pos += $rain_x_spacing;
			} else {
				$rain_x_pos += $rain_x_spacing + rand(-2, 2);
			}
			$rain_y_pos = $rain_stroke_length_quarter;
		}
	}

	public static function pixelize()
	{
		// Pixelize the image (post-processing effect)
		$color_palette = array();
		$replacement_color_palette = array();

		foreach (Pattern::$colors as $color_idx => $hex_color) {
			list($r,$g,$b) = self::get_rgb_from_hex_color($hex_color);

			$color_palette[strtolower($hex_color)] = array(
				'r' => $r,
				'g' => $g,
				'b' => $b,
			);
		}

		// Calculate the width and height of the pixels
		$pixel_w = (int) ceil(Pattern::$width / Pattern::$pixelize_density_x);
		$pixel_h = (int) ceil(Pattern::$height / Pattern::$pixelize_density_y);
		// Precalculated values for optimization
		$pixel_w_half = (int) ($pixel_w / 2);
		$pixel_h_half = (int) ($pixel_h / 2);

		// Process each pixel
		foreach (range($pixel_w_half, Pattern::$width, $pixel_w) as $x) {
			foreach (range($pixel_h_half, Pattern::$height, $pixel_h) as $y) {
				// Check if we need to pixelize this pixel; this automatically applies when
				// Pattern::$pixelize_percentage = 1 or Pattern::$pixelize_percentage is > 0 && < 1
				// and lcg_value() is less than this
				// e.g.
				// Pattern::$hexagon_percentage = 0.5
				// lcg_value() = 0.44409172795429
				if (Pattern::$pixelize_percentage === 1 || Pattern::$pixelize_percentage > lcg_value()) {
					// Pick a sampling point offset to the pixel's center and make sure the point is
					// within the boundaries of the drawing area
					$sample_x = $x - Pattern::$pixelize_sampling + rand(0, Pattern::$pixelize_sampling * 2 + 1);
					$sample_x = min(Pattern::$width - 1, max(0, $sample_x));

					$sample_y = $y - Pattern::$pixelize_sampling + rand(0, Pattern::$pixelize_sampling * 2 + 1);
					$sample_y = min(Pattern::$height - 1, max(0, $sample_y));

					// Get the color at the sampling point
					$hex_color = Image_Generator::get_pixel_color($sample_x, $sample_y);

					// Check if the sampled color exists in the palette, and reassign it to an
					// existing color if it doesn't exist; this check is needed when anti-aliasing
					// is enabled because the sampling point may land on an anti-aliased pixel
					// between two valid colors
					if (!array_key_exists($hex_color, $color_palette)) {
						if (array_key_exists($hex_color, $replacement_color_palette)) {
							// This color has already been matched to a replacement color, so does
							// not need to be re-evaluated
							$hex_color = $replacement_color_palette[$hex_color];
						} else {
							// Find the closest matching color from the pattern's palette
							$replacement_hex_color = self::get_replacement_color_from_hex_color($hex_color, $color_palette);

							// Save the replacement color to the replacement color look-up table
							$replacement_color_palette[$hex_color] = $replacement_hex_color;

							// Apply the replacement color
							$hex_color = $replacement_hex_color;
						}
					}

					Image_Generator::draw_rectangle(
						$x - $pixel_w_half,
						$y - $pixel_h_half,
						$x + $pixel_w_half,
						$y + $pixel_h_half,
						$hex_color
					);
				}
			}
		}
	}

	public static function get_hex_color_from_rgb($r=255, $g=255, $b=255, $include_pound_sign=true)
	{
		if ($include_pound_sign) {
			return '#' . sprintf('%02x%02x%02x', $r, $g, $b);
		} else {
			return sprintf('%02x%02x%02x', $r, $g, $b);
		}
	}

	public static function get_hex_color_from_integer($integer=0, $include_pound_sign=true)
	{
		if ($include_pound_sign) {
			return '#' . str_pad(dechex($integer), 6, '0', STR_PAD_LEFT);
		} else {
			return str_pad(dechex($integer), 6, '0', STR_PAD_LEFT);
		}
	}

	public static function get_rgb_from_hex_color($hex_color)
	{
		$hex_color = str_replace('#', '', $hex_color);
		$r = (int) hexdec(substr($hex_color, 0, 2));
		$g = (int) hexdec(substr($hex_color, 2, 2));
		$b = (int) hexdec(substr($hex_color, 4, 2));

		return array($r, $g, $b);
	}

	public static function get_replacement_color_from_hex_color($hex_color, $color_palette)
	{
		// Find the rgb color "distance" from the sampled color to each of the palette colors, then
		// sort them to find the value with the shortest distance i.e. the closest matching color
		list($r,$g,$b) = self::get_rgb_from_hex_color($hex_color);

		$color_matches = array();

		foreach ($color_palette as $key => $values) {
			$r_diff = (int) abs($values['r'] - $r);
			$g_diff = (int) abs($values['g'] - $g);
			$b_diff = (int) abs($values['b'] - $b);

			$color_matches[($r_diff + $g_diff + $b_diff)] = $key;
		}

		ksort($color_matches, SORT_NUMERIC);
		$color_match_key = key($color_matches);

		return $color_matches[$color_match_key];
	}

	// Type checking functions
	public static function check_hex_color($value=NULL)
	{
		return preg_match('/^#[0-9A-F]{6}$/i', $value); // #FF0000 or #ff0000
	}

	public static function check_integer($value=NULL)
	{
		return preg_match('/^\-{0,1}([0-9]+)$/', $value); // 45 or -45
	}

	public static function check_float($value=NULL)
	{

		if ($value === 0 || $value === '0') {
			return true; // 0
		} else {
			return filter_var($value, FILTER_VALIDATE_FLOAT, array(
				'options' => array('min_range' => 0, 'max_range' => 1)
			)); // 0.1, 0.9, 1, etc.
		}
	}

	// Drop-in replacement mathematical and sorting functions
	public static function python_modulo($a, $b)
	{
		// This function is used to replicate the way Python handles modulus operations when the
		// dividend ($a) is a negative number; under normal circumstances if $a = -1 and $b = 5 then
		// PHP will return -1 but Python will return 4, which is the result needed because the
		// values are being used to access elements in an array
		//
		// @param int $a Dividend
		// @param int $b Divisor
		return ($a - floor($a / $b) * $b);
	}

	public static function reverse_sort_by_key($array)
	{
		$array_temp = array();

		foreach ($array as $key => $value) {
			$array_temp[] = array(
				'key' => $key,
				'value' => $value,
			);
		}

		$keys = array_column($array_temp, 'key');
		$values = array_column($array_temp, 'value');

		array_multisort($values, SORT_DESC, $keys, SORT_DESC, $array_temp);

		return $array_temp;
	}

	// Drop-in Bézier curve calculation routines
	public static function calculate_cubic_bezier_curve_interpolation_point($p, $t)
	{
		// Calculate an interpolation point that will form part of the curve, using the
		// Bernstein polynomial form
		//
		// @param array $p Cubic Bézier curve control points
		// @param float $t Bézier curve ratio
		// @return array($i_x, $i_y) Pixel coordinates of the interpolation point
		//
		// $p will contain the following values:
		//
		// $p[0] = xs, start point, x
		// $p[1] = ys, start point, y
		// $p[2] = x1, control point 1, x
		// $p[3] = y1, control point 1, y
		// $p[4] = x2, control point 2, x
		// $p[5] = y2, control point 2, y
		// $p[6] = xe, end point, x
		// $p[7] = ye, end point, y
		$remainder = 1 - $t;
		$remainder_squared = ($remainder * $remainder);
		$t_squared = ($t * $t);
		$xs_ys_multiplier = ($remainder_squared * $remainder);
		$x1_y1_multiplier = ($remainder_squared * $t * 3);
		$x2_y2_multiplier = ($t_squared * $remainder * 3);
		$xe_ye_multiplier = ($t_squared * $t);

		$i_x = $p[0] * $xs_ys_multiplier + $p[2] * $x1_y1_multiplier + $p[4] * $x2_y2_multiplier + $p[6] * $xe_ye_multiplier;
		$i_y = $p[1] * $xs_ys_multiplier + $p[3] * $x1_y1_multiplier + $p[5] * $x2_y2_multiplier + $p[7] * $xe_ye_multiplier;

		return array($i_x, $i_y);
	}

	// Drop-in Catmull-Rom spline calculation routines
	public static function calculate_catmull_rom_spline_interpolation_points($p)
	{
		$pv = array();

		if (Image_Generator::get_extension() === 'Imagick') {
			for ($i = 0; $i < count($p); $i++) {
				$pv[] = new Vertex($p[$i]['x'], $p[$i]['y']);
			}
		} else if (Image_Generator::get_extension() === 'GD') {
			for ($i = 0; $i < count($p); $i += 2) {
				$pv[] = new Vertex($p[$i], $p[$i + 1]);
			}
		}

		$pv[] = $pv[0];

		for ($i = count($pv) - 1; $i > 0; $i--) {
			if (Helper::dist_vertices($pv[$i], $pv[$i - 1]) < 1) {
				array_splice($pv, $i, 1);
			}
		}

		if (Helper::dist_vertices($pv[0], $pv[count($pv) - 1]) < 1) {
			array_splice($pv, count($pv) - 1, 1);
		}

		$pv[] = $pv[0];
		$pv[] = $pv[1];
		array_splice($pv, 0, 0, array($pv[count($pv) - 1]));

		$points = array();

		for ($i = 1; $i < count($pv) - 2; $i++) {
			$p0 = $pv[$i - 1];
			$p1 = $pv[$i];
			$p2 = $pv[$i + 1];
			$p3 = $pv[$i + 2];

			$p0_p1_distance = Helper::dist_vertices($p0, $p1);

			$t01 = pow($p0_p1_distance, Pattern::$catmull_rom_spline_alpha);
			$t12 = pow(Helper::dist_vertices($p1, $p2), Pattern::$catmull_rom_spline_alpha);
			$t23 = pow(Helper::dist_vertices($p2, $p3), Pattern::$catmull_rom_spline_alpha);

			$t0 = (float) 0;
			$t1 = $t0 + $t01;
			$t2 = $t1 + $t12;
			$t3 = $t2 + $t23;

			$t0_m_t1 = $t0 - $t1;
			$t0_m_t2 = $t0 - $t2;
			$t1_m_t2 = $t1 - $t2;
			$t1_m_t3 = $t1 - $t3;
			$t2_m_t1 = $t2 - $t1;
			$t2_m_t3 = $t2 - $t3;
			$tension_remainder_t2_m_t1 = Pattern::$catmull_rom_spline_tension_remainder * $t2_m_t1;

			$m1x = $tension_remainder_t2_m_t1 * (($p0->x - $p1->x) / $t0_m_t1 - ($p0->x - $p2->x) / $t0_m_t2 + ($p1->x - $p2->x) / $t1_m_t2);
			$m1y = $tension_remainder_t2_m_t1 * (($p0->y - $p1->y) / $t0_m_t1 - ($p0->y - $p2->y) / $t0_m_t2 + ($p1->y - $p2->y) / $t1_m_t2);
			$m2x = $tension_remainder_t2_m_t1 * (($p1->x - $p2->x) / $t1_m_t2 - ($p1->x - $p3->x) / $t1_m_t3 + ($p2->x - $p3->x) / $t2_m_t3);
			$m2y = $tension_remainder_t2_m_t1 * (($p1->y - $p2->y) / $t1_m_t2 - ($p1->y - $p3->y) / $t1_m_t3 + ($p2->y - $p3->y) / $t2_m_t3);

			$ax = 2 * $p1->x - 2 * $p2->x + $m1x + $m2x;
			$ay = 2 * $p1->y - 2 * $p2->y + $m1y + $m2y;
			$bx = -3 * $p1->x + 3 * $p2->x - 2 * $m1x - $m2x;
			$by = -3 * $p1->y + 3 * $p2->y - 2 * $m1y - $m2y;
			$cx = $m1x;
			$cy = $m1y;
			$dx = $p1->x;
			$dy = $p1->y;

			$amount = max(10, ceil($p0_p1_distance / 10));

			for ($j = 1; $j <= $amount; $j++) {
				$t = ($j / $amount);

				// Precalculated values for optimization
				$t_sqrd = $t * $t;
				$t_cube = $t * $t * $t;

				$px = $ax * $t_cube + $bx * $t_sqrd + $cx * $t + $dx;
				$py = $ay * $t_cube + $by * $t_sqrd + $cy * $t + $dy;

				if (Image_Generator::get_extension() === 'Imagick') {
					$points[] = array(
						'x' => (int) $px,
						'y' => (int) $py,
					);
				} else if (Image_Generator::get_extension() === 'GD') {
					$points[] = (int) $px;
					$points[] = (int) $py;
				}
			}
		}

		return $points;
	}

	// Testing/debugging functions
	public static function get_script_memory_usage()
	{
		// Determine the script memory usage
		//
		// @return string $memory_usage
		$memory_usage_size = memory_get_usage(true);

		$units = array('B','KB','MB','GB','TB','PB');

		$memory_usage = round($memory_usage_size / pow(1024, ($i = floor(log($memory_usage_size, 1024)))), 2) . ' ' . $units[$i];

		return $memory_usage;
	}
}
