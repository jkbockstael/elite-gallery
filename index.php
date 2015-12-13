<?php
/* Simple gallery for Elite screenshots
 * 2015 Jean-Karim Bockstael <jkb@jkbockstael.be>
 * License: MIT
 *
 * Very basic gallery that I coded for my own use, obviously very specific to
 * my needs, but maybe parts of it may be of some interest to someone else.
 *
 * This code uses quite a few conventions:
 * - images are in the same directory
 * - image names have this format: "YYYYMMDDHHMM_image_name.jpg"
 * - a text file containing descriptions is in the same directory
 * - the descriptions file contains one description per line
 * - each description line has this format: "filename : description"
 */

define('DESCRIPTIONS_FILE', 'descriptions.txt');

// Extract the timestamp part of a filename
function get_timestamp_part($filename) {
	$parts = explode('_', $filename);
	return $parts[0];
}

// Extract the name part of a filename
function get_name_part($filename) {
	// Filenames all have the format YYYYMMDDHHMM_name.jpg
	$name = substr($filename, 13);
	$name = substr($name, 0, -4);
	return $name;
}

// Load the description of an image, if any. Returns null if none found.
function get_description($filename) {
	$descriptions = explode("\n", file_get_contents(DESCRIPTIONS_FILE));
	foreach ($descriptions as $description) {
		if (strpos($description, $filename) === 0) {
			$fields = explode(' : ', $description);
			return $fields[1];
		}
	}
	return null;
}

// Pretty-print a timestamp
// Timestamps have the format YYYYMMDDHHMM
function pretty_print_timestamp($timestamp) {
	$output = substr($timestamp, 0, 4); // Year
	$output .= '-' . substr($timestamp, 4, 2); // Month
	$output .= '-' . substr($timestamp, 6, 2); // Day
	$output .= ' ' . substr($timestamp, 8, 2); // Hour
	$output .= ':' . substr($timestamp, 10, 2); // Minute
	return $output;
}

// Pretty-print an image name
function pretty_print_name($name) {
	$pretty_name = ucfirst(str_replace('_', ' ', $name));
	return $pretty_name;
}

// List all the picture files in the pictures directory
function list_picture_files() {
	$picture_files = glob('*.jpg');
	return $picture_files;
}

// Check a filename existence
function image_exists($filename) {
	$picture_files = list_picture_files();
	return (array_search($filename, $picture_files) !== false);
}

// Create a link to a picture using its filename
// The showTimestamp parameter is optional, if provided it's used to hide the timestamp
// if omitted the timestamp is automatically added.
function link_to_picture($filename, $showTimestamp = true) {
	if (!isset($text)) {
		if ($showTimestamp) {
			$text = pretty_print_timestamp(get_timestamp_part($filename)) . ' - ' . pretty_print_name(get_name_part($filename));
		} else {
			$text = pretty_print_name(get_name_part($filename));
		}
	}
	$link = '<a href="?p=' . substr($filename, 0, -4) . '">';
	$link .= $text;
	$link .= '</a>';
	return $link;
}

// Format a list as a HTML unordered list
function display_unordered_list($list) {
	$ul = "<ul>\n\t<li>" . implode("</li>\n\t<li>", $list) . "</li>\n</ul>\n";
	return $ul;
}

// Display the image index
function display_index() {
	return display_unordered_list(array_map("link_to_picture", list_picture_files()));
}

// Display a specific image
function display_image($filename) {
	// I need to know which picture this is, for navigation links
	$picture_files = list_picture_files();
	$current_picture_index = array_search($filename, $picture_files);
	// Actual image
	$img_tag = '<img src="' . $filename . '" alt="' . pretty_print_name(get_name_part($filename)) . '" />';
	// Overlay navigation
	$overlay_navigation_previous = '';
	$overlay_navigation_next = '';
	if ($current_picture_index !== count($picture_files) - 1) {
		$overlay_navigation_next .= link_to_picture($picture_files[$current_picture_index + 1]);
	}
	if ($current_picture_index !== 0) {
		$overlay_navigation_previous .= link_to_picture($picture_files[$current_picture_index - 1]);
	}
	$image_display = "<div id=\"image_display\">\n\t" . $overlay_navigation_previous . "\n" . $img_tag . "\n" . $overlay_navigation_next . "</div>\n";
	// Image title
	$title = pretty_print_name(get_name_part($filename));
	$image_display .= "<div id=\"image_name\">" . $title . "</div>\n";
	// Image date
	$date = pretty_print_timestamp(get_timestamp_part($filename));
	$image_display .= "<div id=\"image_date\">" . $date . "</div>\n";
	// Image description
	$description = get_description($filename);
	if (!is_null($description)) {
		$image_display .= "<div id=\"image_description\">" . $description . "</div>\n";
	}
	// links to previous, index and next image
	$links = "<div id=\"image_links\">\n\t";
	// Link to previous image
	if ($current_picture_index !== 0) {
		$link_to_previous = link_to_picture($picture_files[$current_picture_index - 1], false);
		$links .= "<span id=\"image_link_previous\" class=\"image_link\">" . $link_to_previous . "</span>\n\t";
	} else {
		$links .= "<span class=\"image_link\">&nbsp;</span>";
	}
	// Link to the image index
	$link_to_index = '<a href="?p=index">Images index</a>';
	$links .= "<span id=\"image_link_index\">" . $link_to_index . "</span>\n";
	// Links to next image
	if ($current_picture_index !== count($picture_files) - 1) {
		$link_to_next = link_to_picture($picture_files[$current_picture_index + 1], false);
		$links .= "<span id=\"image_link_next\" class=\"image_link\">" . $link_to_next . "</span>\n\t";
	} else {
		$links .= "<span class=\"image_link\">&nbsp;</span>";
	}
	$links .= "</div>\n";
	$image_display .= $links;
	return $image_display;
}

// Display the latest image
function display_latest() {
	$picture_files = list_picture_files();
	$latest = $picture_files[count($picture_files) - 1];
	return display_image($latest);
}

// Let's be fancy and call this part "routing" (at least it sounds better than "do stuff").
// If the "p" parameter is set it means we have to display a specific image.
// A special "index" value for "p" displays a list of all images.
// No "p" parameter or an empty "p" parameter displays the latest image.
function route_request($request) {
	if (!empty($request['p'])) {
		if ($request['p'] === 'index') {
			// Display a full list of images
			return display_index();
		}
		else {
			// Display the requested image if it exists, the latest otherwise
			if (image_exists($request['p'] . '.jpg')) {
				return display_image($request['p'] . '.jpg');
			}
			else {
				return display_latest();
			}
		}
	}
	else {
		// Display the latest image
		return display_latest();
	}
}
?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=Edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>CMDR JKB's sights from Elite: Dangerous</title>
	<meta name="author" content="Jean-Karim Bockstael" />
	<meta name="description" content="Gallery of Elite Dangerous screenshots" />
	<script type="text/javascript">
		function handleKeyDown(e) {
			var keycode = (window.event) ? window.event.keyCode : e.which;
			var query;
			switch (keycode) {
				case 72: // h
				case 37: // left arrow
					query = "#image_display a:first-child";
					break;
				case 76: // l
				case 39: // right arrow
					query = "#image_display a:last-child";
					break;
				default:
					return;
			}
			document.location = document.querySelector(query).href;
		}
		document.onkeydown = handleKeyDown;
	</script>
	<style type="text/css">
		* {
			box-sizing: border-box;
		}
		html, body {
			margin: 0;
			padding: 0;
		}
		#image_display {
			position: relative;
			max-width: 100%;
			height: auto;
			width: auto;
			overflow: hidden;
		}
		#image_display img {
			max-width: 100%;
			height: auto;
			width: auto;
		}
		#image_display a {
			background: black;
			opacity: 0;
			position: absolute;
			top: 0;
			bottom: 0;
			text-align: center;
			width: 100px;
			cursor: pointer;
			line-height: 0;
			font-size: 0;
			color: transparent;
		}
		#image_display a:hover {
			opacity: 0.5;
		}
		#image_display a:after {
			top: 40%;
			content: "";
			position: absolute;
			width: 0;
			height: 0;
			border-top: 25px solid transparent;
			border-bottom: 25px solid transparent;
		}
		#image_display a:first-child {
			left: 0;
		}
		#image_display a:first-child:after {
			border-right: 50px solid #C06400;
			left: 20px;
		}
		#image_display a:last-child {
			right: 0;
		}
		#image_display a:last-child:after {
			border-left: 50px solid #C06400;
			right: 20px;
		}
		#image_date, #image_name, #image_description, #image_links {
			margin: 1em;
		}
		#image_links {
			display: -ms-flexbox;
			display: -webkit-flex;
			display: flex;
			 -webkit-flex-wrap: wrap;
			-ms-flex-wrap: wrap;
			flex-wrap: wrap;
		}
		#image_links span {
			-webkit-flex: 1 1 33%;
			-ms-flex: 1 1 33%;
			flex: 1 1 33%;
			position: relative;
		}
		@media (max-width: 640px) {
			#image_links .image_link {
				-webkit-flex: 1 0 50%;
				-ms-flex: 1 0 50%;
				flex: 1 0 50%;
			}
			#image_links .image_link:first-child {
				 -webkit-order: 1;
				-ms-flex-order: 1;
				order: 1;
			}
			#image_links #image_link_index {
				-webkit-flex: 1 0 100%;
				-ms-flex: 1 0 100%;
				flex: 1 0 100%;
				 -webkit-order: 3;
				-ms-flex-order: 3;
				order: 3;
				margin-top: 1em;
				text-align: center;
			}
			#image_links .image_link:last-child {
				-webkit-order: 2;
				-ms-flex-order: 2;
				order: 2;
				text-align: right;
				padding-right: 1em;
			}
		}
		#image_link_previous {
			padding-left: 1em;
			-webkit-justify-content: flex-start;
			-ms-flex-pack: start;
			justify-content: flex-start;
		}
		#image_link_index {
			text-align: center;
		}
		#image_link_next {
			text-align: right;
			padding-right: 1em;
		}
		#image_link_previous:hover:before {
			border-right-color: #C06400;
		}
		#image_link_next:hover:after {
			border-left-color: #C06400;
		}
		#image_link_previous:before {
			content: "";
			position: absolute;
			width: 0;
			height: 0;
			left: 0;
			border-top: 6px solid transparent;
			border-right: 12px solid #B32900;
			border-bottom: 6px solid transparent;
			top: 3px;
		}
		#image_link_next:after {
			content: "";
			position: absolute;
			width: 0;
			height: 0;
			right: 0;
			border-top: 6px solid transparent;
			border-left: 12px solid #B32900;
			border-bottom: 6px solid transparent;
			top: 3px;
		}
		body {
			background-color: black;
			font-family: "Helvetica", sans-serif;
			color: #CCC;
		}
		a {
			text-decoration: none;
		}
		a, a:active {
			color: #FF3B00;
		}
		a:visited {
			color: #B32900;
		}
		a:hover {
			color: #C06400;
		}
	</style>
</head>
<body>
<?php echo route_request($_REQUEST); ?>
</body>
</html>
