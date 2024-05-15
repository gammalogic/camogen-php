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

interface Image_Generator_Core
{
	public static function initialize($image_width, $image_height, $file_type);
	public static function get_extension();
	public static function set_antialiasing_mode($antialiasing);
	public static function update_drawing();
	public static function set_fill_color($color);
	public static function set_stroke_color($color);
	public static function set_stroke_width($stroke_width);
	public static function draw_bezier_curve($start_x, $start_y, $points);
	public static function draw_polygon($points, $color, $apply_polygon_draw_style);
	public static function draw_ellipse($x, $y, $radius_x, $radius_y, $start_angle, $end_angle, $color);
	public static function draw_rectangle($x1, $y1, $x2, $y2, $color);
	public static function apply_motion_blur($radius, $sigma, $angle);
	public static function get_pixel_color($x, $y, $return_as_hex);
	public static function get_image_type();
	public static function save_image_to_file($filename);
}
