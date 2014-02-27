#Screen Capture
by [Guillaume Molter](http://guillaumemolter.me) for [B+G & Partners SA](http://bgcom.ch/)
##A screenshot plugin for Wordpress based on PhantomJS


###Disclaimer
This plugin requires full access and control over your webserver. If you don't fully understand terms like SSH, sudo and package installation this plugin is not for you.


###Installation

- This plugin comes with the Cent OS x86_64 version of PhantomJS. If your Webserver is using a different OS you will need to replace the binaries by the one for your OS. You can find them [here](http://phantomjs.org/download.html) and replace the binaries in **/bin/** (make sure to preserve the binaries filename).
- Give execution rights to **/bin/**  if it's not already the case.

####Requirements
The plugin has no dependencies however PhantomJS needs a few things to work properly:

- Some base libraries are necessary for rendering : FreeType, Fontconfig
- Please also make sure that the basic font files are available in the system.

More details [here](http://phantomjs.org/download.html).

####How it works

The plugin allows you to call a function that will generate a screencapture into the /wp-content/upload/ and will create a post attachment and attach it to post.

To create a screen capture simply call:

	<?php bgp_scr_create_screen($postID,$format); ?>
	
`$postID` *(Int - required)* is the ID of the post you want to screencapture.

`$format` *(string - optional - default to "png")* is the format of the screencapture. Accepted values are :"png","jpg" or "pdf"

######Return

On success the function returns an array with 2 keys:
  
	array(
		"attachment_id" => 123,
		"url" => "http://pathto/image.png"
	)

On error the function echos en error message and exit.
