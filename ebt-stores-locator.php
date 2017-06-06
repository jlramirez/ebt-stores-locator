<?php

/**
 * Plugin Name: EBT Stores Locator
 * Plugin URI: http://gokalugo.com/ebt
 * Description: Add a map into your Wordpress posts and/or pages of available EBT Stores using shortcode.
 * Version: 1.0
 * Author: Joel Ramirez
 * Author URI: http://gokalugo.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') or die("No script kiddies please!");

function enqueue_ebt_assets()
{
    global $post;

    if(is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'ebt') )
    {

        wp_enqueue_style('ebt-leaflet', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.0.3/leaflet.css');
        wp_enqueue_style('ebt-clusterer', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.0.5/MarkerCluster.css');
        wp_enqueue_style('ebt-clusterer-default', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.0.5/MarkerCluster.Default.css');
        wp_enqueue_style('ebt-custom-leaflet', plugin_dir_url(__FILE__) . 'css/custom-leaflet.css');

        wp_enqueue_script('ebt-jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js');
        wp_enqueue_script('ebt-leaflet-lib', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.0.3/leaflet.js');
        wp_enqueue_script('ebt-leaflet-markercluster-lib', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.0.5/leaflet.markercluster.js');
    }

}
add_action('wp_enqueue_scripts', 'enqueue_ebt_assets');

function ebt_shortcode( $atts)
{

    // Set default shortcode values
    $default = shortcode_atts(array(
        'search' => 'Deli',
        'lat' => '',
        'lon' => '',
        'radius' => '',
        'zip_code' => '',
        'width' => '100%',
        'height' => '500px',
    ), $atts);

    $params = array();
    $params['searchTerm'] = $default['search'];

    if (!empty($default['lat']))
    {
        $params['latitude'] = $default['lat'];
    }

    if (!empty($default['lon']))
    {
        $params['longitude'] = $default['lon'];
    }

    if (!empty($default['radius']))
    {
        $params['radius'] = $default['radius'];
    }

    if (!empty($default['zip_code']))
    {
        $params['zipCode'] = $default['zip_code'];
    }

    $query_string = '';
    foreach ($params as $key => $value)
    {
        if (!empty($query_string))
        {
            $query_string .= '&';
        }

        $query_string .= "$key=$value";
    }

    $query_string .= '&limit=1000';

    // API Request
    $api_url = "https://blacknectar.api.blacksource.tech:9399/stores?$query_string";

    $response = wp_remote_get($api_url);
    $status_code = wp_remote_retrieve_response_code($response);

    ob_start(); ?>

    <div id='ebt_map' style="width:<?php echo $default['width']; ?>; height: <?php echo $default['height']; ?>;"></div>

    <script>

        var map = L.map('ebt_map').setView([0, 0], 2);

        var tileLayer = L.tileLayer('http://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ',
            maxZoom: 16
        }).addTo(map);

        var markerCluster = L.markerClusterGroup();

        <?php if ($status_code === 200) : ?>

            var json = <?php echo wp_remote_retrieve_body($response); ?>;

            if (json.length > 0)
            {
                for (var i = 0; i < json.length; i++)
                {
                    var content = "";

                    if (json[i]['main_image_url'])
                    {
                        content += "<div class='ebt-image-container'><img src='" + json[i]['main_image_url'] + "'></div>";
                    }

                    content += "<h4 class='leaflet-popup-store-name'>" + json[i]['store_name'] + "</h4>";

                    if (json[i]['address']['address_line_1'] || json[i]['address']['city'] || json[i]['address']['state'] || json[i]['address']['zip_code'])
                    {
                        var address = "";

                        if (json[i]['address']['address_line_1'])
                        {
                            address += "<p>" + json[i]['address']['address_line_1'] + "</p>";
                        }

                        address += "<p>";

                        if (json[i]['address']['city'])
                        {
                            address += json[i]['address']['city'];
                        }

                        if (json[i]['address']['state'])
                        {
                            address += ", " + json[i]['address']['state'];
                        }

                        if (json[i]['address']['zip_code'])
                        {
                            address += " " + json[i]['address']['zip_code'];
                        }

                        address += "</p>";
                        content += address;
                    }

                    if (json[i]['is_farmers_market'])
                    {
                        content += "<p>Is farmers market: Yes</p>";
                    }

                    else
                    {
                        content += "<p>Is farmers market: No</p>";
                    }

                    var marker = new L.marker([json[i]['location']['latitude'], json[i]['location']['longitude']]).bindPopup(content);
                    markerCluster.addLayer(marker);
                }

                map.addLayer(markerCluster);
                map.fitBounds(markerCluster.getBounds());
            }

            else
            {
                var emptyPopup = L.popup().setLatLng([0, 0]).setContent("<p>Sorry, no stores were found to match your search.</p>").openOn(map);
            }

        <?php else : ?>

            <?php $errorMessage = str_replace("tech.sirwellington.alchemy.arguments.FailedAssertionException: ","", wp_remote_retrieve_body($response)); ?>

            var errorMessage = "<?php echo $errorMessage; ?>";
            var errorPopup = L.popup().setLatLng([0, 0]).setContent("<p>Sorry, an error occurred. " + errorMessage + "</p>").openOn(map);

        <?php endif; ?>

    </script>

<?php

	return ob_get_clean();
}
add_shortcode('ebt', 'ebt_shortcode');

?>