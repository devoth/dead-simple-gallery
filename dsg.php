<?php
if ( basename(__FILE__) == basename($_SERVER['PHP_SELF']) ) exit('Direct access not permitted.');
/**
 * Dead Simple Gallery
 * Allows you to generate an instant list of image thumbnails with links to previews,
 * from a directory containing images, ideal for Lightbox - like galleries,
 * you can manage the images through FTP client, no coding skills or databases needed!
 *
 * Main features:
 * * simplicity & customizability, use it just by including this script,
 *   or if you are a power user - overwrite the defaults with your own
 *   outside of this script, for full info check the documentation
 * * create thumbnails by cropping (default) or scaling
 * * set thumbnail dimensions
 * * set preview dimensions
 * * supports JPG, GIF & PNG
 * * supports compression
 * * thumbnails are generated on the fly
 * * image caching
 * * works perfectly with JS & jQuery galleries like Lightbox or Fancybox
 * * works with image slideshows
 * * control image alt and link title attributes with image names
 * * supports both HTML & XHTML
 * * highly customizable pagination
 *
 * @author Devoth - Łukasz Mazurek - http://www.devoth.com/
 * @version 2.1.0
 * @copyright Devoth Design, 21 April, 2012
 **/

// configuring and checking for user overwrites

$dsg_output_messages = array();
$dsg_starttime;
$dsg_execution_time;

// set up gallery directory
if ( ! isset($dsg_gallery_dir) ) {
  $dsg_gallery_dir = 'ds_galleries';
}
// for script consistency we remove trailing forward slash from the end of gallery dir name
$dsg_gallery_dir = rtrim($dsg_gallery_dir, '/');

// set up thumbnails directory name
if ( ! isset($dsg_thumbs_dir) || $dsg_thumbs_dir == '' ) {
  $dsg_thumbs_dir = 'dynamic_thumbnails';
}
// for script consistency we remove trailing forward slash from the end of thumbs dir name
$dsg_thumbs_dir = trim($dsg_thumbs_dir, '/');

// set up previews directory name
if ( ! isset($dsg_previews_dir) || $dsg_previews_dir == '' ) {
  $dsg_previews_dir = 'dynamic_previews';
}
// for script consistency we remove trailing forward slash from the end of previews dir name
$dsg_previews_dir = trim($dsg_previews_dir, '/');

// initially JPG, GIF and PNG files are allowed, but user can overwrite that
$dsg_allowed_extensions = array();
if ( ! isset($dsg_allow_gif) || $dsg_allow_gif == TRUE ) {
  array_push($dsg_allowed_extensions, 'gif');
}
if ( ! isset($dsg_allow_jpg) || $dsg_allow_jpg == TRUE ) {
  array_push($dsg_allowed_extensions, 'jpg');
  array_push($dsg_allowed_extensions, 'jpeg');
}
if ( ! isset($dsg_allow_png) || $dsg_allow_png == TRUE ) {
  array_push($dsg_allowed_extensions, 'png');
}

// script can generate XHTML or HTML tags
if ( ! isset($dsg_xhtml) || $dsg_xhtml !== TRUE ) {
  $dsg_xhtml = FALSE;
}

// set up default preview width
if ( ! isset($dsg_preview_width) ) {
  $dsg_preview_width = 800;
}

// set up default preview height
if ( ! isset($dsg_preview_height) ) {
  $dsg_preview_height = 700;
}

// set up default thumbnail width
if ( ! isset($dsg_thumb_width) ) {
  $dsg_thumb_width = 210;
}

// set up default thumbnail height
if ( ! isset($dsg_thumb_height) ) {
  $dsg_thumb_height = 140;
}

// set up default operation for thumbnail generation
if ( ! isset($dsg_thumb_operation) ) {
  $dsg_thumb_operation = 'crop';
}
else if ($dsg_thumb_operation != 'scale') {
  $dsg_thumb_operation = 'crop';
}

// set up default thumbnail compression
if ( ! isset($dsg_compression) ) {
  $dsg_compression = 80;
}

if ( ! isset($dsg_order)) {
  $dsg_order = 'alphabetical';
}

// user can force refresh to recreate thumbnails on each page refresh
// use with caution
if ( ! isset($dsg_force_refresh) || $dsg_force_refresh == FALSE ) {
  $dsg_force_refresh = FALSE;
}
else {
  $dsg_output_messages['force_refresh'] = "Force refresh is active. In this mode <b>thumbnails</b> and <b>previews</b> are recreated on every page refresh. Avoid leaving it this way on production servers &mdash; it's meant only for testing purposes.";
}

// set default variable delimiter for extracting (exploding) values from base file name
if ( ! isset($dsg_filename_delimiter) ) {
  $dsg_filename_delimiter = '|';
}

// set default output pattern
if ( ! isset($dsg_line_pattern) ) {
  $dsg_line_pattern = '<li><a href="{PREVIEW_URL}" title="{ORIGINAL_FILENAME_HUMANIZED}">';
  $dsg_line_pattern.= '<img src="{THUMB_URL}" width="{THUMB_WIDTH}" height="{THUMB_HEIGHT}" alt="{ORIGINAL_FILENAME_HUMANIZED}"{SELFCLOSE}>';
  $dsg_line_pattern.= '</a></li>{NEWLINE}';
}

// determines whether user wants to print output html or use it via variable
if ( ! isset($dsg_auto_output) ) {
  $dsg_auto_output = TRUE;
}

// set up default pagination per page value
// it doesn't show pagination when set to null
if ( ! isset($dsg_pagination_per_page) ) {
  $dsg_pagination_per_page = NULL;
}

// set up default pagination link count
if ( ! isset($dsg_pagination_num_links) ) {
  $dsg_pagination_num_links = 3;
}

// pagination parameter used to retrieve page number from $_GET global variable ($_GET[$dsg_pagination_parameter])
if ( ! isset($dsg_pagination_parameter) ) {
  $dsg_pagination_parameter = 'dsg_page';
}

// set default pagination pattern
if ( ! isset($dsg_pagination_pattern) ) {
  $dsg_pagination_pattern = '<nav><ul class="pagination">{FIRST_PAGE}{PREV_PAGE}{PAGES}{NEXT_PAGE}{LAST_PAGE}</ul></nav>';
}

// set default pagination pattern
if ( ! isset($dsg_pagination_spacer_pattern) ) {
  $dsg_pagination_spacer_pattern = '<li class="spacer disabled"><span>&hellip;</span></li>{NEWLINE}';
}

// set default first page pattern
if ( ! isset($dsg_first_page_pattern) ) {
  $dsg_first_page_pattern = '<li class="first"><a href="{PAGE_URL}" title="First page">First</a></li>{NEWLINE}';
}

// set default first page disabled pattern
if ( ! isset($dsg_first_page_disabled_pattern) ) {
  $dsg_first_page_disabled_pattern = '<li class="first disabled"><span>First</span></li>{NEWLINE}';
}

// set default previous page pattern
if ( ! isset($dsg_prev_page_pattern) ) {
  $dsg_prev_page_pattern = '<li class="prev"><a href="{PAGE_URL}" title="Previous page">Previous</a></li>{NEWLINE}';
}

// set default previous page disabled pattern
if ( ! isset($dsg_prev_page_disabled_pattern) ) {
  $dsg_prev_page_disabled_pattern = '<li class="prev disabled"><span>Previous</span></li>{NEWLINE}';
}

// set default page pattern
if ( ! isset($dsg_page_pattern) ) {
  $dsg_page_pattern = '<li><a href="{PAGE_URL}" title="Page {PAGE_NUMBER}">{PAGE_NUMBER}</a></li>{NEWLINE}';
}

// set default current page pattern
if ( ! isset($dsg_current_page_pattern) ) {
  $dsg_current_page_pattern = '<li class="active"><span>{PAGE_NUMBER}</span></li>{NEWLINE}';
}

// set default next page pattern
if ( ! isset($dsg_next_page_pattern) ) {
  $dsg_next_page_pattern = '<li class="next"><a href="{PAGE_URL}" title="Next page">Next</a></li>{NEWLINE}';
}

// set default next page disabled pattern
if ( ! isset($dsg_next_page_disabled_pattern) ) {
  $dsg_next_page_disabled_pattern = '<li class="next disabled"><span>Next</span></li>{NEWLINE}';
}

// set default last page pattern
if ( ! isset($dsg_last_page_pattern) ) {
  $dsg_last_page_pattern = '<li class="last"><a href="{PAGE_URL}" title="Last page">Last</a></li>{NEWLINE}';
}

// set default last page disabled pattern
if ( ! isset($dsg_last_page_disabled_pattern) ) {
  $dsg_last_page_disabled_pattern = '<li class="last disabled"><span>Last</span></li>{NEWLINE}';
}

// put all config parameters into a nice config array
$dsg_config = array();
$dsg_config['gallery_dir'] = $dsg_gallery_dir;
$dsg_config['thumbs_dir'] = $dsg_thumbs_dir;
$dsg_config['previews_dir'] = $dsg_previews_dir;
$dsg_config['allowed_extensions'] = $dsg_allowed_extensions;
$dsg_config['xhtml'] = $dsg_xhtml;
$dsg_config['preview_width'] = $dsg_preview_width;
$dsg_config['preview_height'] = $dsg_preview_height;
$dsg_config['thumb_width'] = $dsg_thumb_width;
$dsg_config['thumb_height'] = $dsg_thumb_height;
$dsg_config['thumb_operation'] = $dsg_thumb_operation;
$dsg_config['compression'] = $dsg_compression;
$dsg_config['order'] = $dsg_order;
$dsg_config['force_refresh'] = $dsg_force_refresh;
$dsg_config['filename_delimiter'] = $dsg_filename_delimiter;
$dsg_config['line_output_pattern'] = $dsg_line_pattern;

// Pagination
$dsg_config['pagination_per_page'] = $dsg_pagination_per_page;
$dsg_config['pagination_parameter'] = $dsg_pagination_parameter;
$dsg_config['pagination_num_links'] = $dsg_pagination_num_links;
$dsg_config['pagination_output_pattern'] = $dsg_pagination_pattern;
$dsg_config['pagination_spacer_output_pattern'] = $dsg_pagination_spacer_pattern;
$dsg_config['first_page_output_pattern'] = $dsg_first_page_pattern;
$dsg_config['first_page_disabled_output_pattern'] = $dsg_first_page_disabled_pattern;
$dsg_config['prev_page_output_pattern'] = $dsg_prev_page_pattern;
$dsg_config['prev_page_disabled_output_pattern'] = $dsg_prev_page_disabled_pattern;
$dsg_config['page_output_pattern'] = $dsg_page_pattern;
$dsg_config['current_page_output_pattern'] = $dsg_current_page_pattern;
$dsg_config['next_page_output_pattern'] = $dsg_next_page_pattern;
$dsg_config['next_page_disabled_output_pattern'] = $dsg_next_page_disabled_pattern;
$dsg_config['last_page_output_pattern'] = $dsg_last_page_pattern;
$dsg_config['last_page_disabled_output_pattern'] = $dsg_last_page_disabled_pattern;

// functions

if( ! function_exists('dsgEchuj')):
/**
 * Prints out variables in a more readable way
 *
 * @param mixed $variable variable to be printed, most likely an Array
 * @return void
 * @author Devoth
 */
function dsgEchuj($variable) {
  echo '<pre>';
  print_r($variable);
  echo '</pre>';
}
endif;

if( ! function_exists('dsgExecutionTimeTrackingStart')):
/**
 * Starts tracking time of script execution
 *
 * @return float Returns start time for use in other functions
 * @author Devoth
 */
function dsgExecutionTimeTrackingStart() {
  $mtime = microtime();
  $mtime = explode(" ",$mtime);
  $mtime = $mtime[1] + $mtime[0];
  $starttime = $mtime;
  return $starttime;
}
endif;

if( ! function_exists('dsgExecutionTimeTrackingEnd')):
/**
 * Ends time tracking and returns elapsed time info
 * calculates the difference between start time passed as a param, and current time
 *
 * @param float $starttime Number containing start time
 * @return string Returns string with output message containing info about time difference
 * @author Devoth
 */
function dsgExecutionTimeTrackingEnd($starttime) {
  $mtime = microtime();
  $mtime = explode(" ",$mtime);
  $mtime = $mtime[1] + $mtime[0];
  $endtime = $mtime;
  $totaltime = number_format($endtime - $starttime, 6);

  return "Images were generated in " . $totaltime . " seconds";
}
endif;

if( ! function_exists('dsginfo')):
/**
 * Prints out all of the script info
 *
 * @return void
 * @author Devoth
 */
function dsginfo() {
  global $dsg_output_messages;

  foreach ($dsg_output_messages as $key => $value) {
    echo $value . '<br>';
  }
}
endif;

if( ! function_exists('dsgHumanize')):
/**
 * Converts slug-like string to human readable string
 * with underscores converted to spaces and camelCase converted to space separated words
 *
 * @param string $string string to be converted
 * @return string Returns initial string converted to human readable version
 * @author Devoth
 */
function dsgHumanize($string) {
  // replace underscores with spaces, prepend space to Big letters
  $string = preg_replace('/(([A-Z])|_|-)/', ' $2', $string);

  // replace multiple spaces with one
  $string = preg_replace('/ {2,}/', ' ', $string);

  // trim spaces from the front and the end of the string
  $string = trim($string);

  // start each word from Uppercase
  $string = ucwords($string);

  return $string;
}
endif;

if( ! function_exists('dsgGetImages')):
/**
 * Fetches images from a directory, images can be filtered by extension
 *
 * @param array $config Array containing config parameters, expects gallery_dir and allowed_extension indexes, first one containing directory string, second containing extensions Array containing allowed image file extensions
 * @return array Returns an Array of images
 * @throws Exception if directory is not set, trying to get images from an invalid directory, or there are no images in the directory
 * @author Devoth
 */
function dsgGetImages ($config) {
  $dir = $config['gallery_dir'];
  $allowed_extensions = $config['allowed_extensions'];

  if ( $dir == FALSE ) {
    throw new Exception('Please specify a directory');
  }
  if ( ! is_dir($dir) ) {
    throw new Exception('Invalid gallery directory. Please create a folder called "' . $dir . '".');
  }

  $image_files = array();

  if ( FALSE !== ($dir_o = @opendir($dir)) ) {
    while ( FALSE !== ($entry = @readdir($dir_o)) ) {
      // omnit directories
      if ( is_dir($dir . '/' . $entry) ) {
        continue;
      }
      $path = pathinfo($entry);

      // ignore files without extension
      if (!isset($path['extension'])) {
        continue;
      }

      $ext = $path['extension'];
      if( in_array(strtolower($ext), $allowed_extensions) ) {
        array_push($image_files, $path['basename']);
      }
    }
    closedir($dir_o);
  }

  // check if there were images returned
  if ( empty($image_files) ) {
    throw new Exception('Sorry, no images at the moment');
  }

  $images_data = array();
  foreach ($image_files as $key => $image_file)
  {
    $image_data = array();
    $image_file_parts = explode('.', $image_file);
    $ext = array_pop($image_file_parts);
    $fullpath = $dir . '/' . $image_file;
    $size = getimagesize($fullpath);

    $image_data['basename'] = $image_file; // full file name (basename)
    $image_data['filename'] = basename($image_file, '.' . $ext ); // file name (without extension)
    $image_data['extension'] = $ext; // extension
    $image_data['dirname'] = $dir; // dir_path
    $image_data['fullpath'] = $fullpath; // fullpath
    // $image_data['size'] = $size; // size [width, height]
    $image_data['sizex'] = $size[0]; // width
    $image_data['sizey'] = $size[1]; // height
    $image_data['mime'] = $size['mime']; // mime (mime type)
    $image_data['type'] = $size[2]; // type
    $image_data['filemtime'] = filemtime($fullpath); // file modification time
    $image_data['filectime'] = filectime($fullpath); // file creation time
    $image_data['exif'] = function_exists('exif_read_data') ? @exif_read_data($fullpath) : null; // exif data
    $image_data['abs_counter'] = $key + 1; // absolute counter

    $image_data = dsgSetImageOrientationVariables($image_data);

    $images_data[] = $image_data;
  }

  return $images_data;
}
endif;

if( ! function_exists('dsgSortImages')):
/**
 * Sorts an array of images by alphabetical, numerical,
 * file creation or modification order
 *
 * @return array Returns sorted array of images
 * @author Devoth
 **/
function dsgSortImages($image_files, $config) {

  switch ($config['order']) {
    case 'alphabetical|desc':
      function sort_func($a, $b) { return -strcmp($a['filename'], $b['filename']); }
      break;
    case 'creation':
    case 'creation|asc':
      function sort_func($a, $b) { return $a['filectime'] - $b['filectime']; }
      break;
    case 'creation|desc':
      function sort_func($a, $b) { return $b['filectime'] - $a['filectime']; }
      break;
    case 'modification':
    case 'modification|asc':
      function sort_func($a, $b) { return $a['filemtime'] - $b['filemtime']; }
      break;
    case 'modification|desc':
      function sort_func($a, $b) { return $b['filemtime'] - $a['filemtime']; }
      break;
    case 'numerical':
    case 'numerical|asc':
      function sort_func($a, $b) { return $a['filename'] - $b['filename']; }
      break;
    case 'numerical|desc':
      function sort_func($a, $b) { return $b['filename'] - $a['filename']; }
      break;
    default:
      function sort_func($a, $b) { return strcmp($a['filename'], $b['filename']); }
      break;
  }

  usort($image_files, 'sort_func');
  return $image_files;
}
endif;

if( ! function_exists('dsgLoadImage')):
/**
 * Loads image pixel data of a specified image file, based on image type
 *
 * @param array $image Array containing image file data (including image path)
 * @return resource Returns an image resource identifier on success
 * @throws Exception if image type is not supported
 * @author Devoth
 */
function dsgLoadImage($image) {
  switch ($image['type']) {
    case IMAGETYPE_JPEG: return imagecreatefromjpeg( $image['fullpath'] );
    case IMAGETYPE_GIF:  return imagecreatefromgif( $image['fullpath'] );
    case IMAGETYPE_PNG:  return imagecreatefrompng( $image['fullpath'] );
    default:
      throw new Exception('Unsupported image type in ' . $image['basename']);
  }
}
endif;

if( ! function_exists('dsgScaleImage')):
/**
 * Scales an image, so it contains within a supplied dimensions box,
 * preserves original dimensions ratio
 *
 * @param array $image Array containing image file data
 * @param int $box_width Width of target dimensions box
 * @param int $box_height Height of target dimensions box
 * @return resource Returns an image resource identifier on success
 * @author Devoth
 */
function dsgScaleImage($image, $box_width, $box_height) {

  $current_image = dsgLoadImage( $image );
  list($image, $current_image) = dsgAdjustImageOrientation($image, $current_image);

  // check if image exceeds target box dimensions
  if ( $image['sizex'] < $box_width && $image['sizey'] < $box_height) {
    // if no - abort resizing, and pass original image instead
    dsgPreserveAlpha($image, $current_image);
    return $current_image;
  }

  // ratio
  $original_ratio = $image['sizex'] / $image['sizey'];
  $target_ratio = $box_width / $box_height;

  $scale = ($original_ratio >= $target_ratio ? $box_width / $image['sizex'] : $box_height / $image['sizey']);

  // calculate target dimensions
  $dest_width = round( $image['sizex'] * $scale );
  $dest_height = round( $image['sizey'] * $scale );

  // scale the image
  $new_image = imagecreatetruecolor($dest_width, $dest_height);
  copyImageWithTransparency($image, $new_image, $current_image);
  imagecopyresampled($new_image, $current_image, 0, 0, 0, 0, $dest_width, $dest_height, $image['sizex'], $image['sizey']);

  return $new_image;
}
endif;

if( ! function_exists('dsgCropImage')):
/**
 * Crops an image, so its shorter dimension (in ratio sense) contains within a supplied dimensions box,
 * cuts away excess image dimension
 *
 * @param array $image Array containing image file data
 * @param int $box_width Width of target dimensions box
 * @param int $box_height Height of target dimensions box
 * @return resource Returns an image resource identifier on success
 * @author Devoth
 */
function dsgCropImage ($image, $box_width = 100, $box_height = 100) {

  $current_image = dsgLoadImage( $image );
  list($image, $current_image) = dsgAdjustImageOrientation($image, $current_image);

  // check if image exceeds target box dimensions
  if ( $image['sizex'] < $box_width && $image['sizey'] < $box_height) {
    // if no - abort resizing, and pass original image instead
    dsgPreserveAlpha($image, $current_image);
    return $current_image;
  }

  // ratio
  $original_ratio = $image['sizex'] / $image['sizey'];
  $target_ratio = $box_width / $box_height;

  // calculate crop parameters
  if ( $original_ratio >= $target_ratio ) {
    $dst_w = round( $box_height * $image['sizex'] / $image['sizey'] );
    $dst_h = $box_height;
  }
  else {
    $dst_w = $box_width;
    $dst_h = round( $box_width * $image['sizey'] / $image['sizex'] );
  }
  $dst_x = round( - ($dst_w - $box_width ) / 2 );
  $dst_y = round( - ($dst_h - $box_height ) / 2 );
  $src_x = 0;
  $src_y = 0;
  $src_w = $image['sizex'];
  $src_h = $image['sizey'];

  // crop the image
  $new_image = imagecreatetruecolor($box_width, $box_height);
  copyImageWithTransparency($image, $new_image, $current_image);
  imagecopyresampled($new_image, $current_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

  return $new_image;
}
endif;

if( ! function_exists('copyImageWithTransparency')):
/**
 * Copy image preserving transparency
 * @param array $image Array containing image file data
 * @param  resource $new_image New source
 * @param  resource $current_image Image source
 * @return resource
 */
function copyImageWithTransparency($image, $new_image, $current_image)
{
  // Preserve transparency
  $transparent_index = imagecolortransparent($current_image);
  if ($transparent_index >= 0) {  // GIF
    imagepalettecopy($current_image, $new_image);
    imagefill($new_image, 0, 0, $transparent_index);
    imagecolortransparent($new_image, $transparent_index);
    imagetruecolortopalette($new_image, true, 256);
  }
  else  // PNG
  {
    imagealphablending($new_image, false);
    imagesavealpha($new_image, true);
    $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
    imagefilledrectangle($new_image, 0, 0, $image['sizex'], $image['sizey'], $transparent);
  }
  return $new_image;
}
endif;

if( ! function_exists('dsgPreserveAlpha')):
/**
 * Preserve alpha channel for png images and make transparent background for gifs
 *
 * @param array $image Array containing image file data
 * @param  resource $imgsrc Image source
 * @return resource
 * @author Devoth
 */
function dsgPreserveAlpha($image, $imgsrc)
{
  switch ($image['mime']) {
    case 'image/png':
      imagealphablending($imgsrc, FALSE);
      imagesavealpha($imgsrc, TRUE);
      break;

    case 'image/gif':
      $background = imagecolorallocate($imgsrc, 0, 0, 0);
      imagecolortransparent($imgsrc, $background);
      break;
  }

  return $imgsrc;
}
endif;

if( ! function_exists('dsgMirrorImage')):
/**
 * Mirror image
 * @param  resource $imgsrc Image source
 * @return resource
 * @author Devoth
 */
function dsgMirrorImage($imgsrc)
{
  $width = imagesx ( $imgsrc );
  $height = imagesy ( $imgsrc );

  $src_x = $width -1;
  $src_y = 0;
  $src_width = -$width;
  $src_height = $height;

  $imgdest = imagecreatetruecolor ( $width, $height );

  if ( imagecopyresampled ( $imgdest, $imgsrc, 0, 0, $src_x, $src_y, $width, $height, $src_width, $src_height ) ) {
    return $imgdest;
  }

  return $imgsrc;
}
endif;

if( ! function_exists('dsgSetImageOrientationVariables')):
/**
 * Get orientation information from Exif data and prepare to rotate or mirror image
 *
 * @param  array $image Array containing image file data
 * @return array
 * @author Devoth
 */
function dsgSetImageOrientationVariables($image)
{
  $exif = $image['exif'];
  if (!$exif || !isset($exif['Orientation']) || $exif['Orientation'] == 1) {
    return $image;
  }

  $mirror = false;
  $rotate = 0;

  switch ($exif['Orientation']) {
    case 2:
      $mirror = true;
      break;
    case 3:
      $rotate = 180;
      break;
    case 4:
      $rotate = 180;
      $mirror = true;
      break;
    case 5:
      $rotate = 270;
      $mirror = true;
      break;
    case 6:
      $rotate = 270;
      break;
    case 7:
      $rotate = 90;
      $mirror = true;
      break;
    case 8:
      $rotate = 90;
      break;
  }
  if ($rotate) {
    $image['rotate'] = $rotate;
  }

  if ($mirror) {
    $image['mirror'] = $mirror;
  }

  return $image;
}
endif;

if( ! function_exists('dsgAdjustImageOrientation')):
/**
 * Rotate image using already provided informations
 *
 * @param  array $image Array containing image file data
 * @param  resource $imgsrc Image source
 * @return array
 * @author Devoth
 */
function dsgAdjustImageOrientation($image, $imgsrc)
{
  if (isset($image['rotate']) && $image['rotate'] > 0) {
    $imgsrc = imagerotate($imgsrc, $image['rotate'], 0);
  }
  if (isset($image['mirror']) && $image['mirror']) {
    $imgsrc = dsgMirrorImage($imgsrc);
  }

  // Set new image width and height
  $image['sizex'] = imagesx($imgsrc);
  $image['sizey'] = imagesy($imgsrc);

  return array($image, $imgsrc);
}
endif;

if( ! function_exists('dsgSaveImage')):
/**
 * Outputs image pixels to a destination file, based on file type
 *
 * @param resource $new_image Image resource identifier
 * @param string $image_path Destination to where image has to be saved, includes image name
 * @param int $image_type integer defining image type
 * @param string $image_basename Image file name including extension
 * @param int $compression Desired image compression - applies to JPEG files only
 * @throws Exception if image type is not supported, or writing to file fails
 * @return void
 * @author Devoth
 */
function dsgSaveImage($new_image, $destination_path, $image_type, $image_basename, $compression) {
  // save image to destination, save function depends on type
  switch ($image_type) {
    case IMAGETYPE_JPEG:
      if (! @imagejpeg($new_image, $destination_path, $compression) ) {
        throw new Exception ("Writing to file failed.");
      }
      break;
    case IMAGETYPE_GIF:
        if (! @imagegif($new_image, $destination_path) ) {
          throw new Exception ("Writing to file failed.");
        }
    break;
    case IMAGETYPE_PNG:
      if (! @imagepng($new_image, $destination_path) ) {
        throw new Exception ("Writing to file failed.");
      }
    break;
    default:
      throw new Exception('Unsupported image type in ' . $image_basename);
  }
}
endif;

if( ! function_exists('dsgGenerateThumbnails')):
/**
 * Creates thumbnails from an array of images and saves them to a directory,
 * thumbnails will be either scaled or cropped based chosen $dsg_thumb_operation
 *
 * @param array $images Array of images data
 * @param array $config Array containing config parameters
 * @return array Array with two indexes, first containing Array of images supplemented with thumbs data, second containing an Array of output messages
 * @author Devoth
 */
function dsgGenerateThumbnails( $images, $config ) {
  $output_messages = array();

  // create destination directory if nonexistent
  $destination_dir = $config['gallery_dir'] . '/' . $config['thumbs_dir'];
  ! is_dir($destination_dir) ? @mkdir($destination_dir) : '';

  foreach ($images as $key => $image) {
    $thumb_path =
      $destination_dir . '/' . $image['filename']
      . '_c' . $config['compression']
      . '_' . $config['thumb_operation']
      . '_' . $config['thumb_width'] . '_' . $config['thumb_height']
      . '.' . $image['extension'];

    $image['thumbpath'] = $thumb_path;
    $image['thumbw'] = $config['thumb_width'];
    $image['thumbh'] = $config['thumb_height'];
    $images[$key] = $image;

    // don't create a thumb if it already exists and it's newer than the original
    if ( file_exists($thumb_path) && ( filemtime($image['fullpath']) < filemtime($thumb_path) || filectime($image['fullpath']) < filectime($thumb_path) ) ) {
      // if force_refresh is in play, image will be created
      if ( ! $config['force_refresh'] ) {
        continue;
      }
    }

    if ($config['thumb_operation'] == 'scale') {
      $thumb = dsgScaleImage($image, $config['thumb_width'], $config['thumb_height']);
    }
    else {
      $thumb = dsgCropImage($image, $config['thumb_width'], $config['thumb_height']);
    }
    dsgSaveImage( $thumb, $image['thumbpath'], $image['type'], $image['basename'], $config['compression'] );
    $output_messages[] = 'Created thumb for ' . $image['basename'] . '.';
  }
  return array( $images, $output_messages );
}
endif;

if ( ! function_exists('dsgGeneratePreviews') ):
/**
 * Creates previews from an array of images and saves them to a directory,
 * previews will be scaled
 *
 * @param array $images Array of images data
 * @param array $config Array containing config parameters
 * @return array Array with two indexes, first containing Array of images supplemented with previews data, second containing an Array of output messages
 * @author Devoth
 */
function dsgGeneratePreviews( $images, $config ) {
  $output_messages = array();

  // create destination directory if nonexistent
  $destination_dir = $config['gallery_dir'] . '/' . $config['previews_dir'];
  ! is_dir($destination_dir) ? @mkdir($destination_dir) : '';

  foreach ($images as $key => $image) {
    $preview_path =
      $destination_dir . '/' . $image['filename']
      . '_c' . $config['compression']
      . '_scale'
      . '_' . $config['preview_width'] . '_' . $config['preview_height']
      . '.' . $image['extension'];

    $image['previewpath'] = $preview_path;
    $image['previeww'] = $config['preview_width'];
    $image['previewh'] = $config['preview_height'];
    $images[$key] = $image;

    // don't create a preview if it already exists and is newer than the original
    if ( file_exists($preview_path) && ( filemtime($image['fullpath']) < filemtime($preview_path) || filectime($image['fullpath']) < filectime($preview_path) ) ) {
      // if force_refresh is in play, image will be created
      if ( ! $config['force_refresh'] ) {
        continue;
      }
    }

    $preview = dsgScaleImage($image, $config['preview_width'], $config['preview_height']);
    dsgSaveImage( $preview, $image['previewpath'], $image['type'], $image['basename'], $config['compression'] );
    $output_messages[] = 'Created preview for ' . $image['basename'] . '.';
  }
  return array( $images, $output_messages );
}
endif;

if ( ! function_exists('dsgShowImages') ):
/**
 * Creates HTML code with thumbnails gallery, each one linking to it's preview,
 * generates either HTML or XHTML compliant tags, based on $dsg_xhtml global variable
 *
 * @param array $images Array containing arrays of image data
 * @param array $config Array containing congifuration parameters
 * @return string
 * @author Devoth
 */
function dsgShowImages($images, $config) {

  // prepare placeholder variables
  $placeholder_variables = dsgGetGlobalPlaceholderVariables($config);

  $output = '';

  foreach ($images as $key => $image)
  {
    // get placholder variable values for current image
    $line_placeholder_variables = dsgGetImagePlaceholderVariables( $image, $config['filename_delimiter'] );
    $line_placeholder_variables['COUNTER'] = (int)($key + 1);

    // merge line placeholder variables with global (for all lines) placeholder variables
    $line_placeholder_variables = array_merge($line_placeholder_variables, $placeholder_variables);

    $output .= dsgReplaceVariables($line_placeholder_variables, $config['line_output_pattern']);
  }

  return $output;
}
endif;


if ( ! function_exists('dsgGetGlobalPlaceholderVariables') ) {
  /**
   * Return an array of global placeholder values
   *
   * @param array $config Array containing congifuration parameters
   * @return array Array of placeholder variables, placeholder name as array keys, placeholder value as array values
   * @author Devoth
   **/
  function dsgGetGlobalPlaceholderVariables( $config ) {
    $placeholder_variables = array();
    $placeholder_variables['SELFCLOSE'] = $config['xhtml'] ? ' /' : '';
    $placeholder_variables['NEWLINE'] = "\n";

    return $placeholder_variables;
  }
}

if ( ! function_exists('dsgGetImagePlaceholderVariables') ) {
  /**
   * Return an array of placeholder values for a given image
   *
   * @param array $image_data Array containing image data like path, filename, size, etc
   * @param string $filename_delimiter String containing a delimiter used to explode parts of file name into variables
   * @return array Array of placeholder variables, placeholder name as array keys, placeholder value as array values
   * @author Devoth
   **/
  function dsgGetImagePlaceholderVariables( $image_data, $filename_delimiter ) {
    $placeholder_variables = array();

    $thumb_size = getimagesize( $image_data['thumbpath'] );
    $preview_size = getimagesize( $image_data['previewpath'] );

    $thumb_path_info = pathinfo($image_data['thumbpath']);
    $preview_path_info = pathinfo($image_data['previewpath']);

    $placeholder_variables['ABS_COUNTER'] = $image_data['abs_counter'];
    $placeholder_variables['ORIGINAL_FILENAME_HUMANIZED'] = dsgHumanize( $image_data['filename'] );

    $placeholder_variables['ORIGINAL_URL'] = $image_data['fullpath'];
    $placeholder_variables['ORIGINAL_FILENAME'] = $image_data['filename'];
    $placeholder_variables['ORIGINAL_BASENAME'] = $image_data['basename'];
    $placeholder_variables['ORIGINAL_EXT'] = $image_data['extension'];
    $placeholder_variables['ORIGINAL_WIDTH'] = $image_data['sizex'];
    $placeholder_variables['ORIGINAL_HEIGHT'] = $image_data['sizey'];

    $placeholder_variables['THUMB_URL'] = $image_data['thumbpath'];
    $placeholder_variables['THUMB_FILENAME'] = basename( $thumb_path_info['basename'], '.' . $thumb_path_info['extension'] );
    $placeholder_variables['THUMB_BASENAME'] = $thumb_path_info['basename'];
    $placeholder_variables['THUMB_EXT'] = $thumb_path_info['extension'];
    $placeholder_variables['THUMB_BOXWIDTH'] = $image_data['thumbw'];
    $placeholder_variables['THUMB_BOXHEIGHT'] = $image_data['thumbh'];
    $placeholder_variables['THUMB_WIDTH'] = $thumb_size[0];
    $placeholder_variables['THUMB_HEIGHT'] = $thumb_size[1];

    $placeholder_variables['PREVIEW_URL'] = $image_data['previewpath'];
    $placeholder_variables['PREVIEW_FILENAME'] = basename( $preview_path_info['basename'], '.' . $thumb_path_info['extension'] );
    $placeholder_variables['PREVIEW_BASENAME'] = $preview_path_info['basename'];
    $placeholder_variables['PREVIEW_EXT'] = $preview_path_info['extension'];
    $placeholder_variables['PREVIEW_BOXWIDTH'] = $image_data['previeww'];
    $placeholder_variables['PREVIEW_BOXHEIGHT'] = $image_data['previewh'];
    $placeholder_variables['PREVIEW_WIDTH'] = $preview_size[0];
    $placeholder_variables['PREVIEW_HEIGHT'] = $preview_size[1];


    /* get variables from file name and add them to $placeholder_variables, as VAR_1 to VAR_10 */

    // extract variables from filename
    $variable_values = explode( $filename_delimiter, $image_data['filename'] );

    // let's make sure the array with variables has always 10 items
    $variable_values = $variable_values + array_fill( 0, 10, '' );

    // create keys array prefilled with 'VAR_'
    $variable_keys = array_fill( 0, 10, 'VAR_' );

    // add number to each 'VAR_' in keys array
    array_walk( $variable_keys, create_function('&$v,$k', '$v.= ($k+1);'));

    // merge $placeholder_variables array with exploaded filename vars (combined from keys & values arrays)
    $placeholder_variables += array_combine($variable_keys, $variable_values);

    return $placeholder_variables;
  }
}


if ( ! function_exists('dsgGetPageVariables') ) {
  /**
   * Return an array of placeholder values for a given page
   *
   * @param integer|null $page Page number or null when page does not exist
   * @param array $config Array containing congifuration parameters
   * @param array $placeholder_variables Array containing variables with their replacements
   * @author Devoth
   **/
  function dsgGetPageVariables( $page, $config, $placeholder_variables = array() ) {
    // Do not generate page variables for not existing pages
    if ($page === null) {
      return $placeholder_variables;
    }

    $pagination_parameter = $config['pagination_parameter'];

    // Build url based on the query string
    $query_string = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    parse_str($query_string, $query_data);
    $query_data[$pagination_parameter] = $page;
    $url = '?'.http_build_query($query_data);

    // get placholder variable values for specified page
    $placeholder_variables['PAGE_NUMBER'] = $page;
    $placeholder_variables['PAGE_URL'] = htmlspecialchars($url);

    return $placeholder_variables;
  }
}

if ( ! function_exists('dsgReplaceVariables') ):
/**
 * Replace placeholders with apropriate values
 *
 * @param array $placeholder_variables Array containing variables with their replacements
 * @param string $output_pattern Source pattern
 * @return array
 * @author Devoth
 */
function dsgReplaceVariables($placeholder_variables, $output_pattern) {

  // get placeholders into a single array
  $patterns = array_keys($placeholder_variables);

  // convert placeholder names array into patterns array
  array_walk( $patterns, create_function('&$v,$k', '$v = "/(?<!\{)\{$v}/";') );

  // get placeholder values (replacements) into single array
  $replacements = array_values($placeholder_variables);

  // replace placeholders with apropriate values
  $output = preg_replace($patterns, $replacements, $output_pattern);

  // clean double curly brackets
  $output = preg_replace('/\{\{([\w]+)}}/', '{$1}', $output);

  return $output;
}
endif;


if ( ! function_exists('dsgPaginate') ):
/**
 * Show pagination links
 *
 * @param integer $cur_page Current page number
 * @param integer $num_pages Total page number
 * @param array $config Array containing configuration parameters
 * @return string Html output
 * @author Devoth
 */
function dsgPaginate($cur_page, $num_pages, $config) {

  // prepare placeholder variables
  $placeholder_variables = dsgGetGlobalPlaceholderVariables($config);

  // Count of pagination links shown before and after current page
  $num_links = $config['pagination_num_links'];

  // Previous page
  $previous_page_enabled = ($cur_page != 1);
  $previous_page_number = $previous_page_enabled ? $cur_page - 1 : null;

  // Next page
  $next_page_enabled = ($cur_page < $num_pages);
  $next_page_number = $next_page_enabled ? $cur_page + 1 : null;

  // Determine output pattern templates (enabled or disabled)
  $first_page_pattern = ($cur_page > 1 && $num_pages > 1) ? $config['first_page_output_pattern'] : $config['first_page_disabled_output_pattern'];
  $prev_page_pattern = $previous_page_enabled ? $config['prev_page_output_pattern'] : $config['prev_page_disabled_output_pattern'];
  $next_page_pattern = $next_page_enabled ? $config['next_page_output_pattern'] : $config['next_page_disabled_output_pattern'];
  $last_page_pattern = ($num_pages > 1 && $cur_page < $num_pages) ? $config['last_page_output_pattern'] : $config['last_page_disabled_output_pattern'];

  // Prepare template variables
  $first_page_variables = dsgGetPageVariables(1, $config, $placeholder_variables);
  $prev_page_variables = dsgGetPageVariables($previous_page_number, $config, $placeholder_variables);
  $next_page_variables = dsgGetPageVariables($next_page_number, $config, $placeholder_variables);
  $last_page_variables = dsgGetPageVariables($num_pages, $config, $placeholder_variables);

  // Generate spacer output
  $spacer_output = dsgReplaceVariables($placeholder_variables, $config['pagination_spacer_output_pattern']);

  $pagination_variables = $placeholder_variables;
  $page_items = '';

  // First page link
  $pagination_variables['FIRST_PAGE'] = dsgReplaceVariables($first_page_variables, $first_page_pattern);

  // Previous page link
  $pagination_variables['PREV_PAGE'] = dsgReplaceVariables($prev_page_variables, $prev_page_pattern);

  // Spacer
  if ($cur_page > $num_links + 1) {
    $page_items .= $spacer_output;
  }

  // Page numbers range that we render links for (make sure they are in sepcified range)
  $start_from = ($cur_page - $num_links > 0) ? $cur_page - $num_links : 1;
  $end_at = ($cur_page + $num_links < $num_pages) ? $cur_page + $num_links : $num_pages;

  // Pagination links
  for ($page = $start_from; $page <= $end_at; $page++)
  {
    // Determine page pattern (active page pattern or normal page pattern)
    $output_pattern = ($page == $cur_page) ? $config['current_page_output_pattern'] : $config['page_output_pattern'];

    $page_variables = dsgGetPageVariables($page, $config, $placeholder_variables);
    $page_items .= dsgReplaceVariables($page_variables, $output_pattern);
  }

  // Spacer
  if ($cur_page + $num_links < $num_pages) {
    $page_items .= $spacer_output;
  }

  // Next page link
  $pagination_variables['NEXT_PAGE'] = dsgReplaceVariables($next_page_variables, $next_page_pattern);

  // Last page link
  $pagination_variables['LAST_PAGE'] = dsgReplaceVariables($last_page_variables, $last_page_pattern);

  // Pagination variables
  $pagination_variables['PAGES'] = $page_items;
  $pagination_variables['CURRENT_PAGE_NUM'] = $cur_page;
  $pagination_variables['TOTAL_PAGES_NUM'] = $num_pages;
  $pagination = dsgReplaceVariables($pagination_variables, $config['pagination_output_pattern']);

  return $pagination;
}
endif;
// track script execution time
$dsg_starttime = dsgExecutionTimeTrackingStart();
$dsg_output = $dsg_pagination_nav = '';
$dsg_cur_page = $dsg_num_pages = $dsg_start_from = 0;

try {
  if (!function_exists('exif_read_data')) {
    $dsg_output_messages[] = 'Exif extension for PHP is not loaded. Thumbnails may not be generated with proper orientation.';
  }

  // get images
  $dsg_images = dsgGetImages( $dsg_config );

  $dsg_images = dsgSortImages($dsg_images, $dsg_config);

  // check if images dir is writable
  if ( ! is_writable($dsg_gallery_dir) ) {
    throw new Exception('Please make gallery directory "' . $dsg_gallery_dir . '" writeable');
  }

  // Whether or not show pagination
  if ($dsg_pagination_per_page > 0) {
    // Retrieve page number
    $dsg_cur_page = isset($_GET[$dsg_pagination_parameter]) ? intval($_GET[$dsg_pagination_parameter]) : 1;
    $dsg_num_pages = ceil(count($dsg_images) / $dsg_pagination_per_page);

    // Check if page parameter is valid
    if ( $dsg_cur_page < 1 || $dsg_cur_page > $dsg_num_pages ) {
      $dsg_cur_page = 1;
    }

    // Slice images array to contain only entries for current page
    $dsg_start_from = ($dsg_cur_page - 1) * $dsg_pagination_per_page;
    $dsg_images = array_slice($dsg_images, $dsg_start_from, $dsg_pagination_per_page);

    $dsg_pagination_nav = dsgPaginate($dsg_cur_page, $dsg_num_pages, $dsg_config);
  }

  // generate thumbnails
  list ($dsg_images, $tmp_output_messages) = dsgGenerateThumbnails( $dsg_images, $dsg_config );

  // add resulting messages to global messages array
  $dsg_output_messages = array_merge( $dsg_output_messages, $tmp_output_messages );

  // generate previews
  list ($dsg_images, $tmp_output_messages) = dsgGeneratePreviews( $dsg_images, $dsg_config );

  // add resulting messages to global messages array
  $dsg_output_messages = array_merge( $dsg_output_messages, $tmp_output_messages );

  // generate and print (X)HTML output
  $dsg_output = dsgShowImages( $dsg_images, $dsg_config );

  // We can disable writing output to the screen so the user can decide where to show gallery and pagination
  if ($dsg_auto_output) {
    echo $dsg_output;
  }

} catch (Exception $e) {
  echo '<p class="errorMsg">' . $e->getMessage() . '</p>';
}

// calculate script execution time
$dsg_output_messages['execution_time'] = dsgExecutionTimeTrackingEnd( $dsg_starttime );

// unset variables
unset($dsg_gallery_dir);
unset($dsg_thumbs_dir);
unset($dsg_previews_dir);
unset($dsg_allow_gif);
unset($dsg_allow_jpg);
unset($dsg_allow_png);
unset($dsg_preview_width);
unset($dsg_preview_height);
unset($dsg_thumb_width);
unset($dsg_thumb_height);
unset($dsg_thumb_operation);
unset($dsg_compression);
unset($dsg_force_refresh);
unset($dsg_filename_delimiter);
unset($dsg_line_pattern);
unset($dsg_auto_output);

unset($dsg_pagination_per_page);
unset($dsg_pagination_parameter);
unset($dsg_pagination_pattern);
unset($dsg_first_page_pattern);
unset($dsg_first_page_disabled_pattern);
unset($dsg_prev_page_pattern);
unset($dsg_prev_page_disabled_pattern);
unset($dsg_page_pattern);
unset($dsg_current_page_pattern);
unset($dsg_next_page_pattern);
unset($dsg_next_page_disabled_pattern);
unset($dsg_last_page_pattern);
unset($dsg_last_page_disabled_pattern);


// there is only one parameter that isn't getting unset: xhtml
// once you declare it - it will be used for all galleries in a document
// unless of course you set it to something else manually
