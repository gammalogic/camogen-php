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

class Pattern
{
	public static $parameters;
	public static $width;
	public static $height;
	public static $polygon_size;
	public static $polygon_draw_style;
	public static $color_bleed;
	public static $max_depth;
	public static $colors;
	public static $nbr_colors;
	// Distort; this feature is experimental
	public static $preprocess_distort;
	// Hexagons
	public static $preprocess_hexagons;
	public static $hexagon_percentage;
	public static $hexagon_height;
	// Spots
	public static $postprocess_spots;
	public static $spots_amount;
	public static $spots_radius_min;
	public static $spots_radius_max;
	public static $spots_sampling;
	// Rain
	public static $postprocess_rain;
	public static $rain_stroke_width;
	public static $rain_stroke_width_padding_factor;
	public static $rain_stroke_length;
	public static $rain_stroke_segments;
	public static $rain_stroke_line_thickness;
	public static $rain_stroke_color;
	// Pixelize
	public static $postprocess_pixelize;
	public static $pixelize_percentage;
	public static $pixelize_sampling;
	public static $pixelize_density_x;
	public static $pixelize_density_y;
	// Motion Blur
	public static $postprocess_motion_blur;
	public static $motion_blur_radius;
	public static $motion_blur_sigma;
	public static $motion_blur_angle;
	// Polygons
	public static $list_polygons = array();
	public static $nbr_polygons;
	// Precalculated values for optimization
	public static $catmull_rom_spline_alpha;
	public static $catmull_rom_spline_tension;
	public static $catmull_rom_spline_tension_remainder;
	public static $is_PHP_8;

	public static function initialize($parameters)
	{
		// Initialize pattern parameters
		self::$parameters = null;
		self::$width = null;
		self::$height = null;
		self::$polygon_size = null;
		self::$polygon_draw_style = null;
		self::$color_bleed = null;
		self::$max_depth = null;
		self::$colors = null;
		self::$nbr_colors = null;
		// Distort; this feature is experimental
		self::$preprocess_distort = false;
		// Hexagons
		self::$preprocess_hexagons = false;
		self::$hexagon_percentage = null;
		self::$hexagon_height = null;
		// Spots
		self::$postprocess_spots = false;
		self::$spots_amount = null;
		self::$spots_radius_min = null;
		self::$spots_radius_max = null;
		self::$spots_sampling = null;
		// Rain
		self::$postprocess_rain = false;
		self::$rain_stroke_width = null;
		self::$rain_stroke_width_padding_factor = null;
		self::$rain_stroke_length = null;
		self::$rain_stroke_segments = null;
		self::$rain_stroke_line_thickness = null;
		self::$rain_stroke_color = null;
		// Pixelize
		self::$postprocess_pixelize = false;
		self::$pixelize_percentage = null;
		self::$pixelize_sampling = null;
		self::$pixelize_density_x = null;
		self::$pixelize_density_y = null;
		// Motion Blur
		self::$postprocess_motion_blur = false;
		self::$motion_blur_radius = null;
		self::$motion_blur_sigma = null;
		self::$motion_blur_angle = null;
		// Polygons
		self::$list_polygons = array();
		self::$nbr_polygons = (int) 0;
		// Precalculated values for optimization
		self::$catmull_rom_spline_alpha = (float) 0.5;
		self::$catmull_rom_spline_tension = (float) 0;
		self::$catmull_rom_spline_tension_remainder = ((float) 1 - self::$catmull_rom_spline_tension);
		if (version_compare(phpversion(), '8', '>=')) {
			self::$is_PHP_8 = true;
		} else {
			self::$is_PHP_8 = false;
		}

		try {
			// Parse the user supplied parameters and make sure that all non-optional parameters
			// have been supplied or safe defaults are used
			if (!is_array($parameters)) {
				throw new Exception('Sorry, an error occurred (parameters must be a valid array)');
			}

			self::$parameters = $parameters;

			// Width/height
			self::check_parameter_value(array('width'), 'width', array('valid_array_key','is_integer'));
			self::check_parameter_value(array('height'), 'height', array('valid_array_key','is_integer'));

			self::$width = (int) max(1, abs($parameters['width']));
			self::$height = (int) max(1, abs($parameters['height']));

			// Polygon size
			self::check_parameter_value(array('polygon_size'), 'polygon size', array('valid_array_key','is_integer'));

			self::$polygon_size = (int) max(1, abs($parameters['polygon_size']));

			// Polygon draw style
			if (array_key_exists('polygon_draw_style', $parameters)) {
				switch ($parameters['polygon_draw_style']) {
					case 'smooth':
					case 'straight':
						self::$polygon_draw_style = $parameters['polygon_draw_style'];
						break;
					default:
						self::$polygon_draw_style = 'straight';
						break;
				}
			} else {
				self::$polygon_draw_style = 'straight';
			}

			// Maximum polygon generation recursion depth; although no upper limit is enforced, 15
			// is a practical maximum for images <= 700 pixels width/height before unwanted
			// artifacts start appearing or the polygon splitting routine becomes redundant
			if (array_key_exists('max_depth', $parameters)) {
				self::check_parameter_value(array('max_depth'), 'max depth', array('is_integer'));

				self::$max_depth = (int) max(0, abs($parameters['max_depth']));
			} else {
				self::$max_depth = (int) 15;
			}

			// Color bleed
			self::check_parameter_value(array('color_bleed'), 'color bleed', array('valid_array_key','is_integer'));

			self::$color_bleed = max(0, abs($parameters['color_bleed']));

			// Colors
			if (!array_key_exists('colors', $parameters) || count($parameters['colors']) === 0) {
				throw new Exception('Sorry, an error occurred (no colors specified)');
			}
			foreach ($parameters['colors'] as $hex_color) {
				if (!Helper::check_hex_color($hex_color)) {
					throw new Exception('Sorry, an error occurred (invalid hex color value)');
				}
			}

			self::$colors = $parameters['colors'];
			self::$nbr_colors = (count(self::$colors) - 1);

			// Hexagons (optional)
			if (array_key_exists('hexagons', $parameters)) {
				self::$preprocess_hexagons = true;

				self::check_parameter_value(array('hexagons','percentage'), 'hexagon percentage', array('valid_array_key','is_float'));
				self::check_parameter_value(array('hexagons','height'), 'hexagon height', array('valid_array_key','is_integer'));

				self::$hexagon_percentage = (float) min(1, abs($parameters['hexagons']['percentage']));
				self::$hexagon_height = (int) max(10, abs($parameters['hexagons']['height']));
			}

			// Spots (optional)
			if (array_key_exists('spots', $parameters)) {
				self::$postprocess_spots = true;

				self::check_parameter_value(array('spots','amount'), 'spot amount', array('valid_array_key','is_integer'));
				self::check_parameter_value(array('spots','radius'), 'spot radius', array('valid_array_key'));
				self::check_parameter_value(array('spots','radius','min'), 'minimum spot radius', array('valid_array_key','is_integer'));
				self::check_parameter_value(array('spots','radius','max'), 'maximum spot radius', array('valid_array_key','is_integer'));
				self::check_parameter_value(array('spots','sampling_variation'), 'spot sampling variation', array('valid_array_key','is_integer'));
				if ((self::$width % 2) > 0) {
					throw new Exception('Sorry, an error occurred (pattern width value must be an even number in order to generate spots)');
				}
				if ((self::$height % 2) > 0) {
					throw new Exception('Sorry, an error occurred (pattern height value must be an even number in order to generate spots)');
				}

				self::$spots_amount = (int) min(50000, abs($parameters['spots']['amount']));
				self::$spots_radius_min = (int) max(1, abs($parameters['spots']['radius']['min']));
				self::$spots_radius_max = (int) max(1, abs($parameters['spots']['radius']['max']));
				if (self::$spots_radius_min > self::$spots_radius_max) {
					list(self::$spots_radius_min,self::$spots_radius_max) = array(self::$spots_radius_max,self::$spots_radius_min);
				}
				self::$spots_sampling = (int) max(0, abs($parameters['spots']['sampling_variation']));
			}

			// Rain (optional)
			if (array_key_exists('rain', $parameters)) {
				self::$postprocess_rain = true;

				self::check_parameter_value(array('rain','stroke_width'), 'rain stroke width', array('valid_array_key','is_integer'));
				self::check_parameter_value(array('rain','stroke_width_padding_factor'), 'rain stroke width padding factor', array('valid_array_key','is_integer'));
				self::check_parameter_value(array('rain','stroke_length'), 'rain stroke length', array('valid_array_key','is_integer'));
				self::check_parameter_value(array('rain','stroke_segments'), 'rain stroke segments', array('valid_array_key','is_integer'));
				self::check_parameter_value(array('rain','stroke_line_thickness'), 'rain stroke line thickness', array('valid_array_key','is_integer'));
				self::check_parameter_value(array('rain','stroke_color'), 'rain stroke color', array('valid_array_key','is_hex_color'));

				self::$rain_stroke_width = (int) max(1, abs($parameters['rain']['stroke_width']));
				self::$rain_stroke_width_padding_factor = (int) max(1, abs($parameters['rain']['stroke_width_padding_factor']));
				self::$rain_stroke_length = (int) max(2, abs($parameters['rain']['stroke_length']));
				self::$rain_stroke_segments = (int) max(2, abs($parameters['rain']['stroke_segments']));
				self::$rain_stroke_segments = min(self::$rain_stroke_length, self::$rain_stroke_segments);
				self::$rain_stroke_line_thickness = (int) max(1, abs($parameters['rain']['stroke_line_thickness']));
				self::$rain_stroke_color = $parameters['rain']['stroke_color'];
			}

			// Pixelize (optional)
			if (array_key_exists('pixelize', $parameters)) {
				self::check_parameter_value(array('pixelize','percentage'), 'pixelize percentage', array('valid_array_key','is_float'));
				self::check_parameter_value(array('pixelize','sampling_variation'), 'pixelize sampling variation', array('valid_array_key','is_integer'));
				self::check_parameter_value(array('pixelize','density'), 'pixelize density', array('valid_array_key'));
				self::check_parameter_value(array('pixelize','density','x'), 'pixelize density x', array('valid_array_key','is_integer'));
				self::check_parameter_value(array('pixelize','density','y'), 'pixelize density y', array('valid_array_key','is_integer'));

				self::$pixelize_percentage = (float) min(1, abs($parameters['pixelize']['percentage']));
				self::$pixelize_sampling = (int) abs($parameters['pixelize']['sampling_variation']);
				self::$pixelize_density_x = (int) max(2, abs($parameters['pixelize']['density']['x']));
				self::$pixelize_density_y = (int) max(2, abs($parameters['pixelize']['density']['y']));

				if (self::$pixelize_percentage > 0) {
					self::$postprocess_pixelize = true;
				}
			}

			// Motion blur (optional)
			if (array_key_exists('motion_blur', $parameters)) {
				self::$postprocess_motion_blur = true;

				self::check_parameter_value(array('motion_blur','radius'), 'motion blur radius', array('valid_array_key','is_integer'));
				self::check_parameter_value(array('motion_blur','sigma'), 'motion blur sigma', array('valid_array_key','is_integer'));
				self::check_parameter_value(array('motion_blur','angle'), 'motion blur angle', array('valid_array_key','is_integer'));

				self::$motion_blur_radius = (int) min(abs($parameters['motion_blur']['radius']), self::$width);
				self::$motion_blur_sigma = (int) min(abs($parameters['motion_blur']['sigma']), self::$width);
				self::$motion_blur_angle = (int) $parameters['motion_blur']['angle'];
			}
		} catch (Exception $e) {
			die($e->getMessage());
		}
	}

	public static function add_polygon($polygon)
	{
		// Add a new polygon to the pattern's list of polygons
		//
		// @param Polygon $polygon
		self::$list_polygons[] = $polygon;

		self::$nbr_polygons++;
	}

	public static function shuffle_polygons()
	{
		// Shuffle the order of the polygons in the pattern's list of polygons
		shuffle(self::$list_polygons);
	}

	public static function check_parameter_value($keys, $description, $checks)
	{
		if (!is_array($keys) || count($keys) === 0 || !is_array($checks) || count($checks) === 0) {
			throw new Exception('Sorry, an error occurred (parameter value check failed)');
		}

		foreach ($checks as $check) {
			$error = false;

			switch ($check) {
				case 'is_float':
					if (count($keys) === 1) {
						if (!Helper::check_float(self::$parameters[$keys[0]])) {
							$error = true;
						}
					} else if (count($keys) === 2) {
						if (!Helper::check_float(self::$parameters[$keys[0]][$keys[1]])) {
							$error = true;
						}
					} else if (count($keys) === 3) {
						if (!Helper::check_float(self::$parameters[$keys[0]][$keys[1]][$keys[2]])) {
							$error = true;
						}
					}

					if ($error) {
						throw new Exception('Sorry, an error occurred (invalid ' . $description . ' value; value must be a float between 0 and 1)');
					}
					break;
				case 'is_hex_color':
					if (count($keys) === 1) {
						if (!Helper::check_hex_color(self::$parameters[$keys[0]])) {
							$error = true;
						}
					} else if (count($keys) === 2) {
						if (!Helper::check_hex_color(self::$parameters[$keys[0]][$keys[1]])) {
							$error = true;
						}
					} else if (count($keys) === 3) {
						if (!Helper::check_hex_color(self::$parameters[$keys[0]][$keys[1]][$keys[2]])) {
							$error = true;
						}
					}

					if ($error) {
						throw new Exception('Sorry, an error occurred (invalid ' . $description . ' value; value must be a hex color)');
					}
					break;
				case 'is_integer':
					if (count($keys) === 1) {
						if (!Helper::check_integer(self::$parameters[$keys[0]])) {
							$error = true;
						}
					} else if (count($keys) === 2) {
						if (!Helper::check_integer(self::$parameters[$keys[0]][$keys[1]])) {
							$error = true;
						}
					} else if (count($keys) === 3) {
						if (!Helper::check_integer(self::$parameters[$keys[0]][$keys[1]][$keys[2]])) {
							$error = true;
						}
					}

					if ($error) {
						throw new Exception('Sorry, an error occurred (invalid ' . $description . ' value; value must be an integer)');
					}
					break;
				case 'valid_array_key':
					if (count($keys) === 1) {
						if (!array_key_exists($keys[0], self::$parameters)) {
							$error = true;
						}
					} else if (count($keys) === 2) {
						if (!array_key_exists($keys[1], self::$parameters[$keys[0]])) {
							$error = true;
						}
					} else if (count($keys) === 3) {
						if (!array_key_exists($keys[2], self::$parameters[$keys[0]][$keys[1]])) {
							$error = true;
						}
					}

					if ($error) {
						throw new Exception('Sorry, an error occurred (' . $description . ' parameter key missing)');
					}
					break;
				default:break;
			}
		}
	}
}
