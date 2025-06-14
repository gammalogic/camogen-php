# 🦎 camogen for PHP

**camogen for PHP** is a procedural random camouflage pattern generator.

This code is a PHP port of the [camogen](https://github.com/glederrey/camogen) application written in Python by Gael Lederrey, which is itself a port of the original camogen PHP web app by Ulf Åström, previously available at [http://www.happyponyland.net/camogen.php](http://www.happyponyland.net/camogen.php) (site currently down).

Using this application you can create unique camouflage patterns that are built up from a number of different drawing routines and processing effects. It is particularly suitable for generating digital camouflage, "dazzle" patterns, and other experimental designs.

The output from this code has been tested for accuracy against the output from the camogen Python application and should generate identical images given the same starting parameters. However, any mistakes or inaccuracies in this implementation are solely my own.

Many thanks to Gael Lederrey and Ulf Åström for their work on **camogen**. This project would not have been possible without their efforts!

The first release of this code has been codenamed **_Stealthy Salamander_**. To find out more about salamanders and support their conservation please visit the [Foundation for the Conservation of Salamanders](https://www.fcsal.org/) web site.

## System requirements

- PHP 5.4+ with Imagick or GD extensions
- SimpleXML extension (for SVG export, optional)

## How to use

The following scripts can be used to generate the example image set shown below:

- `camogen-examples-cli.php`
  - Command line script to generate and save the example images
- `camogen-examples-browser.php`
  - Browser-based script to generate and save the example images, then display them in your browser
- `camogen-examples-cli-svg-export.php`
  - Command line script to generate and save an example image in PNG and SVG format
- `camogen-examples-browser-svg-export.php`
  - Browser-based script to generate and save an example image in PNG and SVG format, then display both files in your browser

Please see the comments at the top of each script for more details on how to run them.

## Examples

#### Green Blots
![Green Blots](./images/green_blots.png)

#### Mighty Swede
![Mighty Swede](./images/mighty_swede.png)

#### Vodka
![Vodka](./images/vodka.png)

#### Maple Warrior
![Maple Warrior](./images/maple_warrior.png)

#### Desert
![Desert](./images/desert.png)

#### Desert 2
![Desert 2](./images/desert_2.png)

#### Klosterschwester
![Klosterschwester](./images/klosterschwester.png)

#### Blue Sky
![Blue Sky](./images/blue_sky.png)

#### Canopy
![Canopy](./images/canopy.png)

#### Violet Rain
![Violet Rain](./images/violet_rain.png)

#### German Super Dog 2
![German Super Dog 2](./images/german_super_dog_2.png)

##### (Imagick extension)

#### Stealthy Salamander
![Stealthy Salamander](./images/stealthy_salamander.png)

##### (Imagick extension)

## Example clothing applications

![Coveralls](./images/camogen-clothing-examples-1.gif)

![Tops](./images/camogen-clothing-examples-2.gif)

## Imagick/GD

Anti-aliasing is enabled by default except for post-processed images that use the rain effect; the reason for this is that GD does not apply line thickness settings on certain drawing tools when anti-aliasing is enabled.

The code will attempt to use the GD extension if it is available, or the Imagick extension if the GD extension is not available; if neither is available then the script will exit with an error message. The Imagick extension is recommended for best quality and additional processing effects (currently, motion blur), while GD is recommended for faster performance or if you intend to batch-process complex images with a separate application. As an exercise left to the user, the `Image_Generator` class has been designed to be extensible and other image library extensions could be supported provided that they provide the same basic drawing routines as Imagick and GD.

By default, Imagick outputs images at 72dpi and GD outputs images at 96dpi.

### Imagick issues

Using Imagick may significantly increase script execution times depending on your exact server configuration and pattern design. If it's taking an excessively long time to generate images, please try using GD instead.

### GD issues

The motion blur effect is not supported with GD as this effect is not easily attainable using the limited set of filters available. If you specify any settings for this effect in the parameters they will be ignored.

Anti-aliasing may not be available in some PHP installations < 7.1 because the `imageantialias()` function is only available if PHP has been compiled with the bundled version of the GD library.

## SVG export

Images can now be exported to SVG as well as PNG. Exporting to SVG is recommended if you want to manually edit your images in a vector art package (e.g. to apply custom fills or filter effects) or if you need the images to be resolution-independent for printing. Custom metadata is included in the SVG to store the pattern parameters, making it possible to view the XML source of a saved SVG to see which parameters were used to generate the pattern. Please note that some of the pre/post-processing effect shapes may extend beyond the viewbox of the SVG when viewed in a vector art package.

## PHP version compatibility
- Development PHP version: PHP 7.4.33 with Imagick, GD and SimpleXML extensions
- Minimum PHP version tested: PHP 5.4.45 with GD extension
- Maximum PHP version tested: PHP 8.2.0 with GD extension

## EOL Notice (June 2025)

This code repository is now archived and no further updates will be made; thank you for your interest in this software. If you would like to continue development please fork the code.
