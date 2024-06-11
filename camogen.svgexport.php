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

class SVG_Export
{
	public static $svg_paths = array();

	public static function initialize()
	{
		self::$svg_paths = array();
	}

	public static function build_svg()
	{
		$svg = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="no"?><svg/>');
		$svg->addAttribute('xmlns', 'http://www.w3.org/2000/svg');
		$svg->addAttribute('width', Pattern::$width);
		$svg->addAttribute('height', Pattern::$height);
		$svg->addAttribute('version', '1.1');

		// Add pattern parameters as meta data
		$metadata = $svg->addChild('metadata');
		$camogen = $metadata->addChild('camogen');
		$camogen->addAttribute('xmlns:xmlns:camogen', 'https://camogen-php.com/');
		$camogen->addAttribute('version', CAMOGEN_VERSION);
		$camogen->addChild('camogen:camogen:width', Pattern::$width);
		$camogen->addChild('camogen:camogen:height', Pattern::$height);
		$camogen->addChild('camogen:camogen:polygon-size', Pattern::$polygon_size);
		$camogen->addChild('camogen:camogen:polygon-draw-style', Pattern::$polygon_draw_style);
		$camogen->addChild('camogen:camogen:polygon-color-bleed', Pattern::$color_bleed);
		$camogen->addChild('camogen:camogen:max-depth', Pattern::$max_depth);

		$camogen_colors = $camogen->addChild('camogen:camogen:colors');
		for ($i = 0; $i <= Pattern::$nbr_colors; $i++) {
			$camogen_color = $camogen_colors->addChild('camogen:camogen:color');
			$camogen_color->addAttribute('id', $i);
			$camogen_color->addAttribute('color', Pattern::$colors[$i]);
		}

		$camogen->addChild('camogen:camogen:preprocess-distort', (Pattern::$preprocess_distort ? 'true' : 'false'));

		$camogen->addChild('camogen:camogen:preprocess-hexagons', (Pattern::$preprocess_hexagons ? 'true' : 'false'));
		if (Pattern::$preprocess_hexagons) {
			$camogen->addChild('camogen:camogen:hexagon-percentage', Pattern::$hexagon_percentage);
			$camogen->addChild('camogen:camogen:hexagon-height', Pattern::$hexagon_height);
		}

		$camogen->addChild('camogen:camogen:postprocess-spots', (Pattern::$postprocess_spots ? 'true' : 'false'));
		if (Pattern::$postprocess_spots) {
			$camogen->addChild('camogen:camogen:spots-amount', Pattern::$spots_amount);
			$camogen->addChild('camogen:camogen:spots-radius-min', Pattern::$spots_radius_min);
			$camogen->addChild('camogen:camogen:spots-radius-max', Pattern::$spots_radius_max);
			$camogen->addChild('camogen:camogen:spots-sampling', Pattern::$spots_sampling);
		}

		$camogen->addChild('camogen:camogen:postprocess-rain', (Pattern::$postprocess_rain ? 'true' : 'false'));
		if (Pattern::$postprocess_rain) {
			$camogen->addChild('camogen:camogen:rain-stroke-width', Pattern::$rain_stroke_width);
			$camogen->addChild('camogen:camogen:rain-stroke-width-padding-factor', Pattern::$rain_stroke_width_padding_factor);
			$camogen->addChild('camogen:camogen:rain-stroke-length', Pattern::$rain_stroke_length);
			$camogen->addChild('camogen:camogen:rain-stroke-segments', Pattern::$rain_stroke_segments);
			$camogen->addChild('camogen:camogen:rain-stroke-line-thickness', Pattern::$rain_stroke_line_thickness);
			$camogen->addChild('camogen:camogen:rain-stroke-color', Pattern::$rain_stroke_color);
		}

		$camogen->addChild('camogen:camogen:postprocess-pixelize', (Pattern::$postprocess_pixelize ? 'true' : 'false'));
		if (Pattern::$postprocess_pixelize) {
			$camogen->addChild('camogen:camogen:pixelize-percentage', Pattern::$pixelize_percentage);
			$camogen->addChild('camogen:camogen:pixelize-sampling', Pattern::$pixelize_sampling);
			$camogen->addChild('camogen:camogen:pixelize-density-x', Pattern::$pixelize_density_x);
			$camogen->addChild('camogen:camogen:pixelize-density-y', Pattern::$pixelize_density_y);
		}

		$camogen->addChild('camogen:camogen:postprocess-motion-blur', (Pattern::$postprocess_motion_blur ? 'true' : 'false'));
		if (Pattern::$postprocess_motion_blur) {
			$camogen->addChild('camogen:camogen:motion-blur-radius', Pattern::$motion_blur_radius);
			$camogen->addChild('camogen:camogen:motion-blur-sigma', Pattern::$motion_blur_sigma);
			$camogen->addChild('camogen:camogen:motion-blur-angle', Pattern::$motion_blur_angle);
		}

		// Apply a stroke to some types of shapes to cover any visible gaps; these gaps would not
		// normally be visible in the rasterized versions because of anti-aliasing
		$default_polygon_stroke_width = (int) 1;
		$default_rect_stroke_width = (int) 1;

		foreach (self::$svg_paths as $svg_path) {
			switch ($svg_path['type']) {
				case 'circle':
					$circle = $svg->addChild('circle');
					$circle->addAttribute('cx', $svg_path['cx']);
					$circle->addAttribute('cy', $svg_path['cy']);
					$circle->addAttribute('r', $svg_path['r']);
					$circle->addAttribute('style', self::build_style_attribute(
						$svg_path['fill']
					));
					break;
				case 'path':
					$path = $svg->addChild('path');
					$path->addAttribute('d', $svg_path['points']);
					$path->addAttribute('style', self::build_style_attribute(
						$svg_path['fill'],
						$svg_path['stroke_color'],
						$svg_path['stroke_width']
					));
					break;
				case 'polygon':
					$polygon = $svg->addChild('polygon');
					$polygon->addAttribute('points', implode(' ', $svg_path['points']));
					$polygon->addAttribute('style', self::build_style_attribute(
						$svg_path['fill'],
						$svg_path['fill'],
						$default_polygon_stroke_width
					));
					break;
				case 'rect':
					$rect = $svg->addChild('rect');
					$rect->addAttribute('x', $svg_path['x']);
					$rect->addAttribute('y', $svg_path['y']);
					$rect->addAttribute('width', $svg_path['width']);
					$rect->addAttribute('height', $svg_path['height']);
					$rect->addAttribute('style', self::build_style_attribute(
						$svg_path['fill'],
						$svg_path['fill'],
						$default_rect_stroke_width
					));
					break;
				default:break;
			}
		}

		return self::pretty_print_xml($svg->asXML());
	}

	public static function add_svg_circle($cx, $cy, $r, $fill)
	{
		self::$svg_paths[] = array(
			'type' => 'circle',
			'cx' => $cx,
			'cy' => $cy,
			'r' => $r,
			'fill' => $fill,
		);
	}

	public static function add_svg_cubic_bezier_curve($points)
	{
		$points_temp = null;
		$points_temp .= 'M ' . $points[0] . ' ' . $points[1];
		$points_temp .= ' C ';
		$points_temp .= $points[2] . ' ' . $points[3] . ',';
		$points_temp .= $points[4] . ' ' . $points[5] . ',';
		$points_temp .= $points[6] . ' ' . $points[7];

		self::$svg_paths[] = array(
			'type' => 'path',
			'points' => $points_temp,
			'fill' => 'transparent',
			'stroke_color' => Pattern::$rain_stroke_color,
			'stroke_width' => Pattern::$rain_stroke_line_thickness,
		);
	}

	public static function add_svg_polygon($points, $fill)
	{
		$points_temp = array();

		$nbr_points = count($points);

		if (Image_Generator::get_extension() === 'Imagick') {
			for ($i = 0; $i < $nbr_points; $i++) {
				$points_temp[] = $points[$i]['x'] . ',' . $points[$i]['y'];
			}
		} else if (Image_Generator::get_extension() === 'GD') {
			for ($i = 0; $i < $nbr_points; $i += 2) {
				$points_temp[] = $points[$i] . ',' . $points[$i + 1];
			}
		}

		self::$svg_paths[] = array(
			'type' => 'polygon',
			'points' => $points_temp,
			'fill' => $fill,
		);
	}

	public static function add_svg_rectangle($x, $y, $width, $height, $fill)
	{
		self::$svg_paths[] = array(
			'type' => 'rect',
			'x' => $x,
			'y' => $y,
			'width' => $width,
			'height' => $height,
			'fill' => $fill,
		);
	}

	public static function build_style_attribute($fill_color=null, $stroke_color=null, $stroke_width=null)
	{
		$attribute = null;

		$attribute_array = array();
		if ($fill_color != null) {
			$attribute_array['fill'] = $fill_color;
		}
		if ($stroke_color != null) {
			$attribute_array['stroke'] = $stroke_color;
		}
		if ($stroke_width != null) {
			$attribute_array['stroke-width'] = $stroke_width;
		}

		array_walk(
			$attribute_array,
			function ($value, $key) use (&$attribute) {
				$attribute .= $key . ':' . $value . ';';
			}
		); // fill:#668F46;stroke:#668F46;stroke-width:1;

		return $attribute;
	}

	public static function pretty_print_xml($xml)
	{
		// Basic pretty printer routine; SimpleXML will normally output all of the data after the
		// XML string as a single line
		$indent = "\t";

		$search = array(
			'<camogen ',
			'<camogen:',
			'</camogen:colors>',
			'</camogen>',
			'<circle',
			'<metadata>',
			'</metadata>',
			'<path',
			'<polygon',
			'<rect',
			'</svg>',
		);
		$replace = array(
			PHP_EOL . str_repeat($indent, 2) . '<camogen ',
			PHP_EOL . str_repeat($indent, 3) . '<camogen:',
			PHP_EOL . str_repeat($indent, 3) . '</camogen:colors>',
			PHP_EOL . str_repeat($indent, 2) . '</camogen>',
			PHP_EOL . str_repeat($indent, 1) . '<circle',
			PHP_EOL . str_repeat($indent, 1) . '<metadata>',
			PHP_EOL . str_repeat($indent, 1) . '</metadata>',
			PHP_EOL . str_repeat($indent, 1) . '<path',
			PHP_EOL . str_repeat($indent, 1) . '<polygon',
			PHP_EOL . str_repeat($indent, 1) . '<rect',
			PHP_EOL . str_repeat($indent, 0) . '</svg>'
		);
		return str_replace($search, $replace, $xml);
	}

	public static function save_svg_to_file($filename)
	{
		$svg = self::build_svg();
		file_put_contents($filename, $svg);
		unset($svg);

		try {
			if (!file_exists($filename)) {
				throw new Exception('Sorry, an error occurred (SVG was not saved to file)');
			}
			if (filesize($filename) === 0) {
				throw new Exception('Sorry, an error occurred (SVG saved but file is 0 bytes in size)');
			}
		} catch (Exception $e) {
			die($e->getMessage());
		}
	}
}
