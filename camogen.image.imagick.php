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
	public static $extension = 'Imagick';
	public static $img, $draw, $file_type;

	public static function initialize($image_width, $image_height, $file_type='png')
	{
		self::$img = new Imagick();
		self::set_antialiasing_mode(true);
		self::$img->newImage($image_width, $image_height, new ImagickPixel('transparent'));
		self::$draw = new ImagickDraw();

		switch (strtolower($file_type)) {
			case 'png':
				self::$img->setImageFormat('png');
				break;
			default:
				self::$img->setImageFormat('png');
				break;
		}
	}

	public static function get_extension()
	{
		return self::$extension;
	}

	public static function set_antialiasing_mode($antialiasing=true)
	{
		if ($antialiasing) {
			self::$img->setAntiAlias(true);
		} else {
			self::$img->setAntiAlias(false);
		}
	}

	public static function update_drawing()
	{
		self::$img->drawImage(self::$draw);
	}

	public static function set_fill_color($hex_color)
	{
		self::$draw->setFillColor($hex_color);
	}

	public static function set_stroke_color($hex_color)
	{
		self::$draw->setStrokeColor($hex_color);
	}

	public static function set_stroke_width($stroke_width)
	{
		self::$draw->setStrokeWidth($stroke_width);
	}

	public static function draw_bezier_curve($start_x, $start_y, $points)
	{
		self::$draw->pathStart();
		self::$draw->pathMoveToAbsolute($start_x, $start_y); // start point x,y

		foreach ($points as $key => $point) {
			self::$draw->pathCurveToAbsolute(
				$point['x1'], // control point 1, x
				$point['y1'], // control point 1, y
				$point['x2'], // control point 2, x
				$point['y2'], // control point 2, y
				$point['xe'], // end point, x
				$point['ye']  // end point, y
			);

			if (Pattern::$export_svg) {
				SVG_Export::add_svg_cubic_bezier_curve(
					array(
						$start_x,
						$start_y,
						$point['x1'],
						$point['y1'],
						$point['x2'],
						$point['y2'],
						$point['xe'],
						$point['ye'],
					)
				);
			}

			$start_x = $point['xe'];
			$start_y = $point['ye'];
		}

		self::$draw->pathFinish();
	}

	public static function draw_polygon($points, $hex_color, $apply_polygon_draw_style=false)
	{
		self::set_fill_color($hex_color);

		if ($apply_polygon_draw_style && Pattern::$polygon_draw_style === 'smooth') {
			$points = Helper::calculate_catmull_rom_spline_interpolation_points($points);
		}

		self::$draw->polygon($points);

		if (Pattern::$export_svg) {
			SVG_Export::add_svg_polygon(
				$points,
				$hex_color
			);
		}
	}

	public static function draw_ellipse($x, $y, $radius_x, $radius_y, $start_angle, $end_angle, $hex_color)
	{
		self::set_fill_color($hex_color);

		self::$draw->ellipse(
			$x,
			$y,
			$radius_x,
			$radius_y,
			$start_angle,
			$end_angle
		);

		if (Pattern::$export_svg) {
			SVG_Export::add_svg_circle(
				$x,
				$y,
				$radius_x,
				$hex_color
			);
		}
	}

	public static function draw_rectangle($x1, $y1, $x2, $y2, $hex_color)
	{
		self::set_fill_color($hex_color);

		self::$draw->rectangle(
			$x1,
			$y1,
			$x2,
			$y2
		);

		if (Pattern::$export_svg) {
			SVG_Export::add_svg_rectangle(
				$x1,
				$y1,
				abs($x1 - $x2),
				abs($y1 - $y2),
				$hex_color
			);
		}
	}

	public static function apply_motion_blur($radius, $sigma, $angle)
	{
		self::$img->motionBlurImage($radius, $sigma, $angle);
	}

	public static function get_pixel_color($x, $y, $return_as_hex=true)
	{
		$pixel = self::$img->getImagePixelColor((int) $x, (int) $y);
		$colors = $pixel->getColor();

		if ($return_as_hex) {
			return Helper::get_hex_color_from_rgb(
				$colors['r'],
				$colors['g'],
				$colors['b'],
				true
			);
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
		file_put_contents($filename, self::$img); // filename, Imagick image object
		self::$img->clear();

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
}
