<?php
/*
Plugin Name:    NLFlickrAlbumRenderer
Plugin URI:     https://www.niklouch.com/
Description:    UNDER DEVELOPMENT Plugin for rendering a Flickr album (photoset)
Version:        1.2
Author:         Nik Louch
Author URI:     https://www.niklouch.com/
License:        GPL2
License URI:    https://www.gnu.org/licenses/gpl-2.0.html
*/

// SETTINGS

    // SETTINGS MENU ITEM

        // Define the settings menu item
        function NLFlickrAlbumRenderer_menu(){
            add_menu_page(
                'NL Flickr Album Renderer Settings',
                'NL Flickr Album Renderer',
                'manage_options',
                'NLFlickrAlbumRenderer_settings',
                'NLFlickrAlbumRenderer_callback',
                'dashicons-images-alt2'
            );
        }

        // Register the function to add the settings menu item upon admin menu being displayed
        add_action('admin_menu', 'NLFlickrAlbumRenderer_menu');

    // SETTINGS PAGE

        // Function to render settings page
        function NLFlickrAlbumRenderer_callback(){
            ?>
                <div class="wrap">
                    <h1><?php echo get_admin_page_title() ?></h1>
                    <form method="post" action="options.php">
                        <?php
                            // Render the settings fields
                            settings_fields('NLFlickrAlbumRenderer_settings');
                            do_settings_sections('NLFlickrAlbumRenderer'); 
                            submit_button();
                        ?>
                    </form>
                </div>
            <?php
        }

        // Function to render the settings fields
        function NLFlickrAlbumRenderer_settings_fields(){
            // Register the settings fields
            register_setting('NLFlickrAlbumRenderer_settings', 'flickrApiKey', 'text');
            register_setting('NLFlickrAlbumRenderer_settings', 'flickrApiUser', 'text');

            // Add the settings section for API parts
            add_settings_section(
                'NLFlickrAlbumRenderer_settings_sectionId_Api',
                'FlickrAPI',
                '',
                'NLFlickrAlbumRenderer'
            );

            // Add the settings field for the API Key
            add_settings_field(
                'flickrApiKey',
                'Flickr API Key',
                'flickrApiKey_callback',
                'NLFlickrAlbumRenderer',
                'NLFlickrAlbumRenderer_settings_sectionId_Api'
            );

            // Add the settings field for the API User
            add_settings_field(
                'flickrApiUser',
                'Flickr API User',
                'flickrApiUser_callback',
                'NLFlickrAlbumRenderer',
                'NLFlickrAlbumRenderer_settings_sectionId_Api'
            );
        }

        // Function to render the API Key setting field
        function flickrApiKey_callback($args){
            $value = get_option('flickrApiKey');
            ?>
                <input type="text" name="flickrApiKey" id="flickrApiKey" value="<?php print $value; ?>" size="50" />
                <br/><small>Available from <a href="https://www.flickr.com/services/apps/by/me" target="_blank">https://www.flickr.com/services/apps/by/me</a></small>
            <?php
        }

        // Function to render the API User setting field
        function flickrApiUser_callback($args){
            $value = get_option('flickrApiUser');
            ?>
                <input type="text" name="flickrApiUser" id="flickrApiUser" value="<?php print $value; ?>" size="35" />
                <br/><small>Available from <a href="https://www.flickr.com/me" target="_blank">https://www.flickr.com/me</a></small>
            <?php
        }

        // Register the function to render the settings fields upon the initiation of the admin page
        add_action('admin_init', 'NLFlickrAlbumRenderer_settings_fields');

        

        // Function to render the update notice upon save
        function NLFlickrAlbumRenderer_notice() {
            if(isset($_GET['page']) && 'NLFlickrAlbumRenderer_settings' == $_GET['page'] && isset($_GET['settings-updated']) && true == $_GET['settings-updated']){
                ?>
                    <div class="notice notice-success is-dismissible">
                        <p>
                            <strong>NL Flickr Album Renderer settings saved.</strong>
                        </p>
                    </div>
                <?php
            }
        }

        // Register the function to notify that saved worked upon admin notifcations being displayed
        add_action('admin_notices', 'NLFlickrAlbumRenderer_notice');



// SHORTCODE

    // Register the shortcode
    add_shortcode('NLFlickrAlbum', 'RenderNLFlickrAlbum');
    


// CORE FUNCTIONALITY

    // Render the album
    function RenderNLFlickrAlbum($atts) {
        $htmlBack = "";

        // Check the photosetId is populated
        if(!isset($atts["photoset_id"]) || strlen(trim($atts["photoset_id"])) < 10){
            return false;
        }

        // Check the layout is populated
        $layout = trim($atts["layout"]);
        if(!isset($atts["layout"]) || strlen(trim($atts["layout"])) < 3){
            $layout = "list";
        }

        // Check the FlickrAPI key is set
        if('notExists' === get_option("flickrApiKey",'notExists')|| strlen(trim(get_option("flickrApiKey"))) < 5){
            return false;
        }

        // Check the FlickrAPI user is set
        if('notExists' === get_option("flickrApiUser",'notExists')|| strlen(trim(get_option("flickrApiUser"))) < 5){
            return false;
        }

        // Instaniate the class to get the photoset
        require_once("nlFlickrAlbumRenderer.class.php");
        try{
            $nlFlickrAlbumRenderer = new nlFlickrAlbumRenderer(get_option("flickrApiKey"),get_option("flickrApiUser"), plugin_dir_path(__FILE__) . "/assets/cache/");
        }
        catch(Exception $e){
            print "Exception caught: " . $e->getMessage();
        }

        // Check we have a connection to the API
        if(isset($nlFlickrAlbumRenderer) && $nlFlickrAlbumRenderer->isConnected()){
            try{
                $fullPhotoset = $nlFlickrAlbumRenderer->getFullPhotoset(trim($atts["photoset_id"]));
            }
            catch(Exception $e){
                print "Exception caught: " . $e->getMessage();
            }
        }

       // If we can proceed then do so
       if(isset($fullPhotoset->id) && $fullPhotoset->id == $atts["photoset_id"]){
            
            // Load the CSS
            $htmlBack .= "<link rel=\"stylesheet\" href=\"" . plugin_dir_url(__FILE__) . "/assets/css/nlFlickrAlbumRenderer_generic.css\">";
            $htmlBack .= "<link rel=\"stylesheet\" href=\"" . plugin_dir_url(__FILE__) . "/assets/css/nlFlickrAlbumRenderer_" . strtolower($layout) . ".css\">";
            
            // Render a header for the gallery
            $gallryUrl = "https://www.flickr.com/photos/" . get_option("flickrApiUser") . "/albums/" . $atts["photoset_id"];
            $htmlBack .= "<div class=\"photosetHeader\"><a href=\"" . $gallryUrl . "\" title=\"View in Flickr\" target=\"_blank\">";
            if(isset($fullPhotoset->title->_content) && trim($fullPhotoset->title->_content) != ""){
                $htmlBack .= "  <h3 class=\"photosetTitle\"><i class=\"fa-solid fa-images\"></i> " . $fullPhotoset->title->_content . "</h3>";
            }
            else{
                $htmlBack .= "  <h3 class=\"photosetTitle\"><i class=\"fa-solid fa-images\"></i> Photo Gallery</h3>";
            }
            if(isset($fullPhotoset->title->_content) && trim($fullPhotoset->description->_content) != ""){
                $htmlBack .= "  <h4 class=\"photosetDescription\">" . $fullPhotoset->description->_content . "</h4>";
            }
            $htmlBack .= "</a></div>";
            $htmlBack .= "<div class=\"photosetBody\">";
            
            
            // Render the output
            if($layout == "masonry"){
                $htmlBack .= renderMasonry($fullPhotoset);
            }
            if($layout == "grid"){
                $htmlBack .= renderGrid($fullPhotoset);
            }
            if($layout == "list"){
                $htmlBack .= renderList($fullPhotoset);
            }

            $htmlBack .= "<div style=\"clear: both;\"></div></div>";

            
            // Load the Lightbox JS
            $htmlBack .= "<script src=\"" . plugin_dir_url(__FILE__) . "assets/js/fslightbox.js\"></script>";
        }
        return $htmlBack;
    }

    // Function to render the photos out as a masonry grid
    function renderMasonry($fullPhotoset){
        $htmlBack = "";

        // Holder for the overall list of images
        $htmlBack .= "<section class=\"imageList masonry\">";

        // Loop through the photos
        ForEach($fullPhotoset->photos as $photo){
            
            // Ensure it's a public photo
            if($photo->ispublic == 1){

                // Note whether it's a favourite photo or not by having the tag "favourite"
                $isFavouritePhoto = false;
                foreach($photo->info->tags->tag as $tag){
                    if($tag->author == get_option("flickrApiUser") && $tag->_content == "favourite"){
                        $isFavouritePhoto = true; 
                    }
                }
                
                // Determine the shape and of the photo
                unset($photoThumbnail);
                unset($photoOriginal);
                $photoShape = "";
                ForEach($photo->sizes->size as $photoSize){
                    if($isFavouritePhoto == true){
                        if($photoSize->label == "Large"){
                            $photoThumbnail = $photoSize->source;
                            $photoShape = "big";
                            if($photoSize->width > ($photoSize->height * 2)){ $photoShape = "bighorizontal"; }
                            if($photoSize->height > ($photoSize->width * 2)){ $photoShape = "bigvertical"; }
                        }
                    }
                    else{
                        if($photoSize->label == "Medium"){
                            $photoThumbnail = $photoSize->source;
                            if($photoSize->width > ($photoSize->height * 2)){ $photoShape = "horizontal"; }
                            if($photoSize->height > ($photoSize->width * 2)){ $photoShape = "vertical"; }
                        }
                    }
                    if($photoSize->label == "Large"){
                        $photoOriginal = $photoSize->source;
                    }
                    
                }

                // Determine if the photo is a panorama
                $isPano = false;
                ForEach($photo->sizes->size as $photoSize){
                    if($photoSize->label == "VR 4K"){
                        $isPano = true;
                    }
                }

                // Render the image holder DIV
                $htmlBack .= "<div class=\"imageHolder " . $photoShape . "\">";
                
                // If the image isn't a panorama - link it to the lightbox with caption text and link it to the lightbox
                if($isPano == false){
                    $caption = "";
                    if(isset($photo->info->title->_content) && trim($photo->info->title->_content) != ""){
                        $caption .= "<strong>" . $photo->info->title->_content . "</strong>";
                    }
                    if(isset($photo->info->description->_content) && trim($photo->info->description->_content) != ""){
                        $caption .= ($caption != "" ? "<br />" : "") . $photo->info->description->_content;
                    }
                    $htmlBack .= "<a data-fslightbox=\"lightbox" . $fullPhotoset->id . "\" href=\"" . $photoOriginal . "\" data-caption=\"" . $caption . "\">";
                }

                // If the image is a panorama, just link directly to Flickr so it can render it 360
                if($isPano == true){
                    $htmlBack .= "<a href=\"" . $photo->info->urls->url[0]->_content . "\" target=\"_blank\">";
                }

                // Render the slide-down text that appears over the image
                if(isset($photo->info->title->_content) && trim($photo->info->title->_content) != ""){
                    $htmlBack .= " <div class=\"imageText\">";
                    $htmlBack .= "     <div class=\"imageTitle\">" . trim($photo->info->title->_content) . "</div>";
                    if(isset($photo->info->description->_content) && trim($photo->info->description->_content) != ""){
                        $htmlBack .= "     <div class=\"imageDescription\">" . trim($photo->info->description->_content) . "</div>";
                    }
                    $htmlBack .= " </div>";
                }

                // Render the imaage
                $htmlBack .= " <div class=\"image " . $photoShape . "\" style=\"background-image: url(" . $photoThumbnail . ");\"></div>";

                // Close tags
                $htmlBack .= "</a>";
                $htmlBack .= "</div>";
            }
        }
        $htmlBack .= "</section>";

        return $htmlBack;
    }

    // Function to render the photos out as a standard grid
    function renderGrid($fullPhotoset){
        $htmlBack = "";

        // Holder for the overall list of images
        $htmlBack .= "<section class=\"imageList grid\">";

        // Loop through the photos
        ForEach($fullPhotoset->photos as $photo){
            
            // Ensure it's a public photo
            if($photo->ispublic == 1){

                // Determine the thumbnails and source
                unset($photoThumbnail);
                unset($photoOriginal);
                ForEach($photo->sizes->size as $photoSize){
                    if($photoSize->label == "Medium"){
                        $photoThumbnail = $photoSize->source;
                    }
                    if($photoSize->label == "Large"){
                        $photoOriginal = $photoSize->source;
                    }
                }

                // Determine if the photo is a panorama
                $isPano = false;
                ForEach($photo->sizes->size as $photoSize){
                    if($photoSize->label == "VR 4K"){
                        $isPano = true;
                    }
                }

                // Render the image holder DIV
                $htmlBack .= "<div class=\"imageHolder\">";
                
                // If the image isn't a panorama - link it to the lightbox with caption text and link it to the lightbox
                if($isPano == false){
                    $caption = "";
                    if(isset($photo->info->title->_content) && trim($photo->info->title->_content) != ""){
                        $caption .= "<strong>" . $photo->info->title->_content . "</strong>";
                    }
                    if(isset($photo->info->description->_content) && trim($photo->info->description->_content) != ""){
                        $caption .= ($caption != "" ? "<br />" : "") . $photo->info->description->_content;
                    }
                    $htmlBack .= "<a data-fslightbox=\"lightbox" . $fullPhotoset->id . "\" href=\"" . $photoOriginal . "\" data-caption=\"" . $caption . "\">";
                }

                // If the image is a panorama, just link directly to Flickr so it can render it 360
                if($isPano == true){
                    $htmlBack .= "<a href=\"" . $photo->info->urls->url[0]->_content . "\" target=\"_blank\">";
                }

                // Render the slide-down text that appears over the image
                if(isset($photo->info->title->_content) && trim($photo->info->title->_content) != ""){
                    $htmlBack .= " <div class=\"imageText\">";
                    $htmlBack .= "     <div class=\"imageTitle\">" . trim($photo->info->title->_content) . "</div>";
                    if(isset($photo->info->description->_content) && trim($photo->info->description->_content) != ""){
                        $htmlBack .= "     <div class=\"imageDescription\">" . trim($photo->info->description->_content) . "</div>";
                    }
                    $htmlBack .= " </div>";
                }

                // Render the imaage
                $htmlBack .= " <div class=\"image " . $photoShape . "\" style=\"background-image: url(" . $photoThumbnail . ");\"></div>";

                // Close tags
                $htmlBack .= "</a>";
                $htmlBack .= "</div>";
            }
        }
        $htmlBack .= "</section>";

        return $htmlBack;
    }

    // Function to render the photos out as a list
    function renderList($fullPhotoset){
        $htmlBack = "";

        // Holder for the overall list of images
        $htmlBack .= "<section class=\"imageList list\"><ul>";

        // Loop through the photos
        ForEach($fullPhotoset->photos as $photo){
            
            // Ensure it's a public photo
            if($photo->ispublic == 1){

                // Determine the thumbnails and source
                unset($photoThumbnail);
                unset($photoOriginal);
                ForEach($photo->sizes->size as $photoSize){
                    if($photoSize->label == "Medium"){
                        $photoThumbnail = $photoSize->source;
                    }
                    if($photoSize->label == "Large"){
                        $photoOriginal = $photoSize->source;
                    }
                }

                // Determine if the photo is a panorama
                $isPano = false;
                ForEach($photo->sizes->size as $photoSize){
                    if($photoSize->label == "VR 4K"){
                        $isPano = true;
                    }
                }

                // Render the image holder DIV
                $htmlBack .= "<li class=\"itemHolder\">";
                
                // If the image isn't a panorama - link it to the lightbox with caption text and link it to the lightbox
                if($isPano == false){
                    $caption = "";
                    if(isset($photo->info->title->_content) && trim($photo->info->title->_content) != ""){
                        $caption .= "<strong>" . $photo->info->title->_content . "</strong>";
                    }
                    if(isset($photo->info->description->_content) && trim($photo->info->description->_content) != ""){
                        $caption .= ($caption != "" ? "<br />" : "") . $photo->info->description->_content;
                    }
                    $htmlBack .= "<a data-fslightbox=\"lightbox" . $fullPhotoset->id . "\" href=\"" . $photoOriginal . "\" data-caption=\"" . $caption . "\">";
                }

                // If the image is a panorama, just link directly to Flickr so it can render it 360
                if($isPano == true){
                    $htmlBack .= "<a href=\"" . $photo->info->urls->url[0]->_content . "\" target=\"_blank\">";
                }
                
                $htmlBack .= "<div class=\"imageHolder\"><div class=\"image\" style=\"background-image: url(" . $photoThumbnail . ");\"></div></div>";

                $htmlBack .= "<div class=\"textHolder\">";
                if(isset($photo->info->title->_content) && $photo->info->title->_content != ""){
                    $htmlBack .= "<div class=\"textTitle\">" . trim($photo->info->title->_content) . "</div>";
                }

                // Get the date taken
                $metaTakenDate = "";
                if(isset($photo->info->dates->taken) && $photo->info->dates->taken != ""){
                    $metaTakenDate = date('F j, Y \a\t H:i',strtotime($photo->info->dates->taken));
                }
                $metaTakenDate = "<div class=\"textTaken\">" . trim($metaTakenDate) . "</div>";

                // Get the location parts
                $metaTakenLocation = "";
                if(isset($photo->info->location->neighbourhood->_content) && $photo->info->location->neighbourhood->_content != ""){
                    if(trim($metaTakenLocation) != "" || substr(trim($metaTakenLocation),-1) == ","){ $metaTakenLocation .= ", "; }
                    $metaTakenLocation .= $photo->info->location->neighbourhood->_content;
                }
                if(isset($photo->info->location->locality->_content) && $photo->info->location->locality->_content != ""){
                    if(trim($metaTakenLocation) != "" || substr(trim($metaTakenLocation),-1) == ","){ $metaTakenLocation .= ", "; }
                    $metaTakenLocation .= $photo->info->location->locality->_content;
                }
                if(isset($photo->info->location->county->_content) && $photo->info->location->county->_content != ""){
                    if(trim($metaTakenLocation) != "" || substr(trim($metaTakenLocation),-1) == ","){ $metaTakenLocation .= ", "; }
                    $metaTakenLocation .= $photo->info->location->county->_content;
                }
                if(isset($photo->info->location->region->_content) && $photo->info->location->region->_content != "" && $photo->info->location->region->_content != "England"){
                    if(trim($metaTakenLocation) != "" || substr(trim($metaTakenLocation),-1) == ","){ $metaTakenLocation .= ", "; }
                    $metaTakenLocation .= $photo->info->location->region->_content;
                }
                if(isset($photo->info->location->country->_content) && $photo->info->location->country->_content != ""){
                    if(trim($metaTakenLocation) != "" || substr(trim($metaTakenLocation),-1) == ","){ $metaTakenLocation .= ", "; }
                    $metaTakenLocation .= $photo->info->location->country->_content;
                }
                if(isset($photo->info->location->latitude) && $photo->info->location->latitude != "" && isset($photo->info->location->longitude) && $photo->info->location->longitude != ""){
                    $metaTakenLocation = "<a href=\"https://www.google.com/maps/search/?api=1&query=" . $photo->info->location->latitude . "," . $photo->info->location->longitude . "\" target=\"_blank\" title=\"Open in Google maps\">" . $metaTakenLocation . "</a>";
                }
                $metaTakenLocation = "<div class=\"textLocation\">" . trim($metaTakenLocation) . "</div>";
                              
                // Deal with the meta information
                $htmlBack .= "<div class=\"textMeta\">";
                $htmlBack .= $metaTakenDate;
                $htmlBack .= $metaTakenLocation;
                $htmlBack .= "</div>";

                if(isset($photo->info->description->_content) && $photo->info->description->_content != ""){
                    $htmlBack .= "<div class=\"textDescription\">" . trim($photo->info->description->_content) . "</div>";
                }
                $htmlBack .= "</div>";
                // Close tags
                $htmlBack .= "</a>";
                $htmlBack .= "</li>";
            }
        }
        $htmlBack .= "</ul></section>";

        return $htmlBack;
    }