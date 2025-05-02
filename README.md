Very basic Wordpress plugin to render a Flickr photoset on a page.

Creates an admin page to store the api key and user's ID
Registers a shortcode to call the Flickr photoset functionality
Caches the API calls to Flickr to reduce traffic

Shortcode:
[NLFlickrAlbum photoset_id="{id}" layout="{layout}"]

{id}      Id of the photoset from Flickr, eg: 72177720325381230
{layout}  Style for rendering: grid, list, masonry

Integrates with the https://fslightbox.com/ script (place in /assets/js)
