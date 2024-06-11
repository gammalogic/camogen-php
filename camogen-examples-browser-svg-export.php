<?php

/***************************************************************************************************
*                  CAMOuflage GENerator for PHP
*   _________ _____ ___  ____  ____ ____  ____ 
*  / ___/ __ `/ __ `__ \/ __ \/ __ `/ _ \/ __ \
* / /__/ /_/ / / / / / / /_/ / /_/ /  __/ / / /
* \___/\__,_/_/ /_/ /_/\____/\__, /\___/_/ /_/ 
*                           /____/             
*                                              
* Example Image + SVG Browser Script
* 
* USAGE
* 
* Run this script in a browser like this:
* 
* 127.0.0.1/camogen-examples-browser-svg-export.php
* 
* Please see README.md for usage notes, Imagick/GD issues, and PHP version compability.
***************************************************************************************************/

?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>camogen for PHP | Example Image + SVG</title>
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
<h2>Example Image + SVG</h2>
<h4>(Refresh your browser tab to generate new variants)</h4>
<?php
ini_set('display_errors', 1);
error_reporting(-1);
set_time_limit(60);

require_once('camogen.php');

function generate_example_image($description, $filename, $parameters)
{
	$svg_filename = str_replace('.png', '.svg', $filename);

	echo '<h3>' . $description . '</h3>';

	$camogen = new camogen($parameters, $export_svg=true);
	$camogen->save_image($filename);
	$camogen->save_svg($svg_filename);

	echo '<h4>PNG</h4>';
	echo '<img src="' . $filename . '" alt="' . $description . ' (PNG)">';
	echo '<h4>SVG</h4>';
	echo '<img src="' . $svg_filename . '" alt="' . $description . ' (SVG)">';

	unset($camogen);
}

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

?>
<h4>Script Memory Usage: <?php echo Helper::get_script_memory_usage(); ?></h4>

</div><!-- #example-images-wrapper -->

</body>
</html>