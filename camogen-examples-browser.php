<?php

/***************************************************************************************************
*                  CAMOuflage GENerator for PHP
*   _________ _____ ___  ____  ____ ____  ____ 
*  / ___/ __ `/ __ `__ \/ __ \/ __ `/ _ \/ __ \
* / /__/ /_/ / / / / / / /_/ / /_/ /  __/ / / /
* \___/\__,_/_/ /_/ /_/\____/\__, /\___/_/ /_/ 
*                           /____/             
*                                              
* Example Images Browser Script
* 
* USAGE
* 
* Run this script in a browser like this:
* 
* 127.0.0.1/camogen-examples-browser.php
* 
* Please see README.md for usage notes, Imagick/GD issues, and PHP version compability.
***************************************************************************************************/

?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>camogen for PHP | Example Images</title>
		<link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
		<style>
			body {
				background-color: #F5F5F5;
				font-family: "Lato", sans-serif;
			}
			#example-images-wrapper {
				text-align: center;
			}
			#example-images-wrapper img {
				max-width: 500px;
			}
			@media (max-width: 599px) {
				#example-images-wrapper img {
					max-width: 100%;
				}
			}
		</style>
	</head>
<body>

<div id="example-images-wrapper">

<h1>camogen for PHP v0.1</h1>
<h2>Example Images</h2>
<h4>(Refresh your browser tab to generate new variants)</h4>
<?php
ini_set('display_errors', 1);
error_reporting(-1);
set_time_limit(60);

require_once('camogen.php');

function generate_example_image($description, $filename, $parameters)
{
	echo '<h3>' . $description . '</h3>';

	$camogen = new camogen($parameters);
	$camogen->save_image($filename);

	echo '<img src="' . $filename . '" alt="' . $description . '">';

	unset($camogen);
}

// Green Blots
$parameters = array(
	'width' => 700,
	'height' => 700,
	'polygon_size' => 200,
	'color_bleed' => 6,
	'colors' => array(
		'#264722',
		'#023600',
		'#181F16',
	),
	'spots' => array(
		'amount' => 20000,
		'radius' => array('min' => 7, 'max' => 14),
		'sampling_variation' => 10,
	),
);
generate_example_image('Green Blots', 'images/green_blots.png', $parameters);

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

// Vodka
$parameters = array(
	'width' => 700,
	'height' => 700,
	'polygon_size' => 200,
	'color_bleed' => 0,
	'colors' => array(
		'#2A4029',
		'#CCDED0',
		'#FFFFFF',
		'#FFFFFF',
		'#FFFFFF',
		'#FFFFFF',
		'#FFFFFF',
		'#FFFFFF',
	),
	'spots' => array(
		'amount' => 3000,
		'radius' => array('min' => 30, 'max' => 40),
		'sampling_variation' => 10,
	),
	'pixelize' => array(
		'percentage' => 0.75,
		'sampling_variation' => 10,
		'density' => array(
			'x' => 60, // width / pixelize.density.x = block size in pixels
			'y' => 100, // width / pixelize.density.y = block size in pixels
		),
	),
);
generate_example_image('Vodka', 'images/vodka.png', $parameters);

// Maple Warrior
$parameters = array(
	'width' => 700,
	'height' => 700,
	'polygon_size' => 150,
	'color_bleed' => 3,
	'colors' => array(
		'#072900',
		'#34611D',
		'#0C3000',
		'#7A6231',
		'#034700',
		'#092E00',
		'#00450E',
		'#043D00',
	),
	'spots' => array(
		'amount' => 500,
		'radius' => array('min' => 20, 'max' => 30),
		'sampling_variation' => 20,
	),
	'pixelize' => array(
		'percentage' => 1,
		'sampling_variation' => 20,
		'density' => array(
			'x' => 70, // width / pixelize.density.x = block size in pixels
			'y' => 50, // width / pixelize.density.y = block size in pixels
		),
	),
);
generate_example_image('Maple Warrior', 'images/maple_warrior.png', $parameters);

// Desert
$parameters = array(
	'width' => 700,
	'height' => 700,
	'polygon_size' => 400,
	'color_bleed' => 0,
	'colors' => array(
		'#B5A083',
		'#F7D6B4',
		'#F5CFA4',
		'#FAD7BB',
		'#EBD3A2',
		'#FCD5B3',
	),
	'spots' => array(
		'amount' => 2500,
		'radius' => array('min' => 8, 'max' => 16),
		'sampling_variation' => 14,
	),
);
generate_example_image('Desert', 'images/desert.png', $parameters);

// Desert 2
$parameters = array(
	'width' => 700,
	'height' => 700,
	'polygon_size' => 500,
	'color_bleed' => 0,
	'colors' => array(
		'#997948',
		'#C9AC31',
		'#FFE680',
	),
	'spots' => array(
		'amount' => 2500,
		'radius' => array('min' => 8, 'max' => 16),
		'sampling_variation' => 18,
	),
);
generate_example_image('Desert 2', 'images/desert_2.png', $parameters);

// Klosterschwester
$parameters = array(
	'width' => 700,
	'height' => 700,
	'polygon_size' => 300,
	'color_bleed' => 0,
	'colors' => array(
		'#F0DFBD',
		'#9E704D',
		'#145000',
	),
);
generate_example_image('Klosterschwester', 'images/klosterschwester.png', $parameters);

// Blue Sky
$parameters = array(
	'width' => 700,
	'height' => 700,
	'polygon_size' => 250,
	'max_depth' => 10,
	'color_bleed' => 10,
	'colors' => array(
		'#99D6F2',
		'#486DB3',
		'#EDECE7',
		'#3D3240',
	),
	'hexagons' => array(
		'percentage' => 1, // probability factor that determines whether or not the hexagon will be drawn
		'height' => 100,
	),
	'spots' => array(
		'amount' => 10000,
		'radius' => array('min' => 20, 'max' => 30),
		'sampling_variation' => 5,
	),
	'pixelize' => array(
		'percentage' => 0.2, // probability factor that determines whether or not the pixel block will be drawn
		'sampling_variation' => 100, // lower values = more grouping; higher values = more dispersion
		'density' => array(
			'x' => 250, // width / pixelize.density.x = block size in pixels; low values = mosaic effect
			'y' => 250, // width / pixelize.density.y = block size in pixels; low values = mosaic effect
		),
	),
);
generate_example_image('Blue Sky', 'images/blue_sky.png', $parameters);

// Canopy
$parameters = array(
	'width' => 700,
	'height' => 700,
	'polygon_size' => 100,
	'polygon_draw_style' => 'smooth', // straight or smooth
	'color_bleed' => 0,
	'colors' => array(
		'#A8CC48',
		'#9EC46A',
		'#81AD31',
		'#417217',
		'#5F9025',
		'#2A5716',
		'#576828',
		'#A2BF9F',
	),
	'spots' => array(
		'amount' => 10000,
		'radius' => array('min' => 2, 'max' => 5),
		'sampling_variation' => 100,
	),
	'rain' => array(
		'stroke_width' => 1, // approximate edge-to-edge width of the stroke's curves; low values = straighter curves
		'stroke_width_padding_factor' => 3, // multiplier of the stroke width to control how much space each rain stroke is padded by; lower values = tighter spacing
		'stroke_length' => 100, // approximate maximum length of each rain stroke
		'stroke_segments' => 6, // total number of curves in the stroke
		'stroke_line_thickness' => '1', // drawing thickness of the stroke; low values = thinner lines
		'stroke_color' => '#234B0E',
	),
);
generate_example_image('Canopy', 'images/canopy.png', $parameters);

// Violet Rain
$parameters = array(
	'width' => 700,
	'height' => 700,
	'polygon_size' => 600,
	'color_bleed' => 0,
	'colors' => array(
		'#D135CA',
		'#811479',
		'#9D88FF',
	),
	'hexagons' => array(
		'percentage' => 1, // probability factor that determines whether or not the hexagon will be drawn
		'height' => 100,
	),
	'spots' => array(
		'amount' => 20000,
		'radius' => array('min' => 20, 'max' => 30),
		'sampling_variation' => 10,
	),
	'rain' => array(
		'stroke_width' => 1, // approximate edge-to-edge width of the stroke's curves; low values = straighter curves
		'stroke_width_padding_factor' => 6, // multiplier of the stroke width to control how much space each rain stroke is padded by; lower values = tighter spacing
		'stroke_length' => 40, // approximate maximum length of each rain stroke
		'stroke_segments' => 6, // total number of curves in the stroke aka Total number of control points for the bezier curve
		'stroke_line_thickness' => 2, // drawing thickness of the stroke; low values = thinner lines
		'stroke_color' => '#811479',
	),
);
generate_example_image('Violet Rain', 'images/violet_rain.png', $parameters);

// German Super Dog 2
$parameters = array(
	'width' => 700,
	'height' => 700,
	'polygon_size' => 200,
	'max_depth' => 10,
	'color_bleed' => 10,
	'colors' => array(
		'#D8C7B3',
		'#C9A36F',
		'#9F7643',
		'#9B6C37',
		'#683E20',
		'#503223',
		'#372924',
		'#372924',
		'#372924',
		'#372924',
		'#372924',
	), // The same colour can be duplicated to create a more pronounced effect
	'motion_blur' => array(
		'radius' => 20,
		'sigma' => 30, // do not set too high or the image will take a long time to generate
		'angle' => -35, // angle 0 = exactly horizontal, angle -45 bottom-left to top-right, etc.
	),
);
generate_example_image('German Super Dog 2', 'images/german_super_dog_2.png', $parameters);

// Stealthy Salamander
$parameters = array(
	'width' => 700,
	'height' => 700,
	'polygon_size' => 100,
	'max_depth' => 10,
	'color_bleed' => 50,
	'colors' => array(
		'#F9C840',
		'#91CBF9',
		'#9D220B',
		'#040404',
		'#040404',
		'#040404',
		'#040404',
	), // The same colour can be duplicated to create a more pronounced effect
	'pixelize' => array(
		'percentage' => 1, // probability factor that determines whether or not the pixel block will be drawn
		'sampling_variation' => 10, // lower values = more grouping; higher values = more dispersion
		'density' => array(
			'x' => 200, // width / pixelize.density.x = block size in pixels; high values = pointillize effect
			'y' => 200, // width / pixelize.density.y = block size in pixels; high values = pointillize effect
		),
	),
	'motion_blur' => array(
		'radius' => 30,
		'sigma' => 20, // do not set this too high or the image will take a long time to generate
		'angle' => -35, // angle 0 = exactly horizontal, angle -45 bottom-left to top-right, etc.
	),
);
generate_example_image('Stealthy Salamander', 'images/stealthy_salamander.png', $parameters);

?>
<h4>Script Memory Usage: <?php echo Helper::get_script_memory_usage(); ?></h4>

</div><!-- #example-images-wrapper -->

</body>
</html>