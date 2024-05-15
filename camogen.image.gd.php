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

class Image_Generator implements Image_Generator_Core
{
	public static $extension = 'GD';
	public static $img, $draw, $file_type;
	public static $fill_color, $stroke_color, $stroke_width;
	public static $allocated_colors = array();

	public static function initialize($image_width, $image_height, $file_type='png')
	{
		self::$draw = imagecreatetruecolor($image_width, $image_height);
		self::set_antialiasing_mode(true);

		foreach (Pattern::$colors as $hex_color) {
			list($r,$g,$b) = Helper::get_rgb_from_hex_color($hex_color);
			self::$allocated_colors[$hex_color] = imagecolorallocate(self::$draw, $r, $g, $b);
		}
	}

	public static function get_extension()
	{
		return self::$extension;
	}

	public static function set_antialiasing_mode($antialiasing=true)
	{
		if (function_exists('imageantialias')) {
			if ($antialiasing) {
				imageantialias(self::$draw, true);
			} else {
				imageantialias(self::$draw, false);
			}
		}
	}

	public static function update_drawing()
	{
		// PLACEHOLDER
	}

	public static function set_fill_color($color)
	{
		if (Helper::check_hex_color($color)) {
			list($r,$g,$b) = Helper::get_rgb_from_hex_color($color);
			self::$fill_color = imagecolorallocate(self::$draw, $r, $g, $b);
		}
	}

	public static function set_stroke_color($color)
	{
		if (Helper::check_hex_color($color)) {
			list($r,$g,$b) = Helper::get_rgb_from_hex_color($color);
			self::$stroke_color = imagecolorallocate(self::$draw, $r, $g, $b);
		}
	}

	public static function set_stroke_width($stroke_width)
	{
		self::$stroke_width = $stroke_width;

		imagesetthickness(self::$draw, self::$stroke_width);
	}

	public static function draw_bezier_curve($start_x, $start_y, $points)
	{
		foreach ($points as $key => $point) {
			self::draw_cubic_bezier_curve_line_segments(array(
				$start_x, // start point, x
				$start_y, // start point, y
				$point['x1'], // control point 1, x
				$point['y1'], // control point 1, y
				$point['x2'], // control point 2, x
				$point['y2'], // control point 2, y
				$point['xe'], // end point, x
				$point['ye'], // end point, y
			));

			$start_x = $point['xe'];
			$start_y = $point['ye'];
		}
	}

	public static function draw_polygon($points, $color, $apply_polygon_draw_style=false)
	{
		if (array_key_exists($color, self::$allocated_colors)) {
			$color = self::$allocated_colors[$color];
		} else {
			list($r,$g,$b) = Helper::get_rgb_from_hex_color($color);
			$color = imagecolorallocate(self::$draw, $r, $g, $b);
		}

		if ($apply_polygon_draw_style && Pattern::$polygon_draw_style === 'smooth') {
			$points = Helper::calculate_catmull_rom_spline_interpolation_points($points);
		}

		if (Pattern::$is_PHP_8) {
			imagefilledpolygon(
				self::$draw,
				$points,
				$color
			);
		} else {
			imagefilledpolygon(
				self::$draw,
				$points,
				(count($points) / 2),
				$color
			);
		}
	}

	public static function draw_ellipse($x, $y, $radius_x, $radius_y, $start_angle, $end_angle, $color)
	{
		if (array_key_exists($color, self::$allocated_colors)) {
			$color = self::$allocated_colors[$color];
		} else {
			list($r,$g,$b) = Helper::get_rgb_from_hex_color($color);
			$color = imagecolorallocate(self::$draw, $r, $g, $b);
		}

		imagefilledellipse(
			self::$draw,
			$x,
			$y,
			($radius_x * 2),
			($radius_y * 2),
			$color
		);
	}

	public static function draw_rectangle($x1, $y1, $x2, $y2, $color)
	{
		if (array_key_exists($color, self::$allocated_colors)) {
			$color = self::$allocated_colors[$color];
		} else {
			list($r,$g,$b) = Helper::get_rgb_from_hex_color($color);
			$color = imagecolorallocate(self::$draw, $r, $g, $b);
		}

		imagefilledrectangle(
			self::$draw,
			$x1,
			$y1,
			$x2,
			$y2,
			$color
		);
	}

	public static function apply_motion_blur($radius, $sigma, $angle)
	{
		// UNSUPPORTED WITH THIS EXTENSION; SEE README.md
	}

	public static function get_pixel_color($x, $y, $return_as_hex=true)
	{
		$colors = array();

		$pixel = imagecolorat(self::$draw, (int) $x, (int) $y);
		$colors_temp = imagecolorsforindex(self::$draw, $pixel);
		$colors['r'] = $colors_temp['red'];
		$colors['g'] = $colors_temp['green'];
		$colors['b'] = $colors_temp['blue'];

		if ($return_as_hex) {
			return Helper::get_hex_color_from_rgb($colors['r'], $colors['g'], $colors['b'], true);
		} else {
			return $colors;
		}
	}

	public static function get_image_type()
	{
		return $file_type;
	}

	public static function save_image_to_file($filename)
	{
		imagepng(self::$draw, $filename, 9); // GD drawing object, filename, lossless compression amount (0 = none, 9 = most)
		imagedestroy(self::$draw);

		try {
			if (!file_exists($filename)) {
				throw new Exception('Sorry, an error occurred (image was not saved to file)');
			}
			if (filesize($filename) === 0) {
				throw new Exception('Sorry, an error occurred (image saved but file is 0 bytes in size)');
			}
		} catch (Exception $e) {
			die($e->getMessage());
		}
	}

	// Drop-in Bézier curve drawing routines
	public static function draw_cubic_bezier_curve_line_segments($p)
	{
		// Draw a cubic Bézier curve using line segments
		//
		// @param array $p Cubic Bézier curve control points
		//
		// $p will contain the following values:
		//
		// $p[0] = xs, start point of curve, x
		// $p[1] = ys, start point of curve, y
		// $p[2] = x1, start point of first control point, x
		// $p[3] = y1, start point of first control point, y
		// $p[4] = x2, start point of second control point, x
		// $p[5] = y2, start point of second control point, y
		// $p[6] = xe, end point of curve, x
		// $p[7] = ye, end point of curve, y

		// Define the ratio; t is equivalent to the percentage of the distance along the curve
		$t_step = 0.05; // 0.05 = 5%

		// Calculate the vertices for the complete drawing path
		$vertices = array();
		$vertices[] = (int) $p[0]; // start point of curve, x
		$vertices[] = (int) $p[1]; // start point of curve, y
		for ($t = 0 + $t_step; $t < 1; $t += $t_step) {
			$q = Helper::calculate_cubic_bezier_curve_interpolation_point($p, $t);
			$vertices[] = (int) $q[0];
			$vertices[] = (int) $q[1];
		}
		$vertices[] = (int) $p[6]; // end point of curve, x
		$vertices[] = (int) $p[7]; // end point of curve, y

		$nbr_vertices = count($vertices);

		for ($i = 0; $i < $nbr_vertices; $i += 2) {
			if (array_key_exists($i + 2, $vertices) && array_key_exists($i + 3, $vertices)) {
				// GD's imageline() function does not always draw line caps perpendicularly, which
				// means that gaps will appear at certain points in the curve at higher stroke
				// widths; to correct this the line is over-drawn with ellipses of the same
				// diameter as the line width, which generally fills any gaps but is not meant to
				// be a comprehensive solution and is offered on a "best efforts" basis only
				if (self::$stroke_width >= 5) {
					imagefilledellipse(
						self::$draw,
						$vertices[$i],
						$vertices[$i + 1],
						self::$stroke_width,
						self::$stroke_width,
						self::$stroke_color
					);
					imagefilledellipse(
						self::$draw,
						$vertices[$i + 2],
						$vertices[$i + 3],
						self::$stroke_width,
						self::$stroke_width,
						self::$stroke_color
					);
				}

				imageline(
					self::$draw,
					$vertices[$i],
					$vertices[$i + 1],
					$vertices[$i + 2],
					$vertices[$i + 3],
					self::$stroke_color
				);
			}
		}
	}
}
