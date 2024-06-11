#!/usr/bin/php
<?php

/***************************************************************************************************
*                  CAMOuflage GENerator for PHP
*   _________ _____ ___  ____  ____ ____  ____ 
*  / ___/ __ `/ __ `__ \/ __ \/ __ `/ _ \/ __ \
* / /__/ /_/ / / / / / / /_/ / /_/ /  __/ / / /
* \___/\__,_/_/ /_/ /_/\____/\__, /\___/_/ /_/ 
*                           /____/             
*                                              
* Example Image + SVG CLI Script
* 
* USAGE
* 
* Update line 1 of this script to where your PHP interpreter is located on your machine and then run
* the script at the command line like this:
*
* $ chmod +x camogen-examples-cli-svg-export.php
* $ ./camogen-examples-cli-svg-export.php
* 
* Please see README.md for usage notes, Imagick/GD issues, and PHP version compability.
***************************************************************************************************/

ini_set('display_errors', 1);
error_reporting(-1);

require_once('camogen.php');

function generate_example_image($description, $filename, $parameters)
{
	$svg_filename = str_replace('.png', '.svg', $filename);

	print('Attempting to generate ' . $description . '...' . PHP_EOL);

	$camogen = new camogen($parameters, $export_svg=true);
	$camogen->save_image($filename);
	$camogen->save_svg($svg_filename);

	if (file_exists($filename) && filesize($filename) > 0) {
		print('Image generated (' . $filename . ')' . PHP_EOL);
	}
	if (file_exists($svg_filename) && filesize($svg_filename) > 0) {
		print('SVG generated (' . $svg_filename . ')' . PHP_EOL);
	}

	unset($camogen);
}

print('===================================================' . PHP_EOL);
print('camogen for PHP v0.1 Example Image + SVG CLI Script' . PHP_EOL);
print('===================================================' . PHP_EOL);

// Mighty Swede
$parameters = array(
	'width' => 700,
	'height' => 700,
	'polygon_size' => 400,
	'color_bleed' => 0,
	'colors' => array(
		'#668F46',
		'#4A6B3A',
		'#145000',
		'#003022',
	),
);
generate_example_image('Mighty Swede', 'images/mighty_swede.png', $parameters);

print('===================================================' . PHP_EOL);
print('Script Execution Completed' . PHP_EOL);
print('Script Memory Usage: ' . Helper::get_script_memory_usage() . PHP_EOL);
print('===================================================' . PHP_EOL);

exit();

?>