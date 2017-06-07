<?php

/**
 * Plugin Name: EBT Stores Locator
 * Plugin URI: https://github.com/jlramirez/ebt-stores-locator/
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

function ebt_shortcode( $atts )
{

    // Set default shortcode values
    $default = shortcode_atts(array(
        'search'   => 'Deli',
        'lat'      => '',
        'lon'      => '',
        'radius'   => '',
        'zip_code' => '',
        'width'    => '100%',
        'height'   => '500px',
    ), $atts);

    $query_string = "";

    foreach ( $default as $key => $value )
    {
        if ( $key != 'width' && $key != 'height' && !empty( $value ) )
        {
            switch ( $key )
            {
                case 'search':
                    $query_string .= "searchTerm=$value&";
                    break;
                case 'lat':
                    $query_string .= "latitude=$value&";
                    break;
                case 'lon':
                    $query_string .= "longitude=$value&";
                    break;
                case 'zip_code':
                    $query_string .= "zipCode=$value&";
                    break;
                default:
                    $query_string .= "$key=$value&";
                    break;
            }
        }
    }

    $query_string .= 'limit=1000';

    // API Request
    $api_url = "https://blacknectar.api.blacksource.tech:9399/stores?$query_string";
    $response = wp_remote_get( $api_url );
    $status_code = wp_remote_retrieve_response_code( $response );

    ob_start(); ?>

    <div id='ebt_map' style="width:<?php echo $default['width']; ?>; height: <?php echo $default['height']; ?>;"></div>

    <script>

    var map = L.map('ebt_map').setView([0, 0], 2);

    var tileLayer = L.tileLayer('http://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ',
        maxZoom: 16
    }).addTo(map);

    var markerCluster = L.markerClusterGroup();

    <?php if ( $status_code === 200 ) : ?>

    var stores = <?php echo wp_remote_retrieve_body( $response ); ?>;

    var totalStores = stores.length;

    if ( totalStores > 0 )
    {
        var i;

        for ( i = 0; i < totalStores; i++ )
        {
            var content = "";

            if ( stores[i]['main_image_url'] )
            {
                content += "<div class='ebt-image-container'><img src='" + stores[i]['main_image_url'] + "'></div>";
            }

            content += "<h4 class='leaflet-popup-store-name'>" + stores[i]['store_name'] + "</h4>";
            content += "<p>" + stores[i]['address']['address_line_1'] + "</p>";
            content += "<p>" + stores[i]['address']['city'] + ", " + stores[i]['address']['state'] + " " + stores[i]['address']['zip_code'] + "</p>";

            if ( stores[i]['is_farmers_market'] )
            {
                content += "<p>Is farmers market: Yes</p>";
            }

            else
            {
                content += "<p>Is farmers market: No</p>";
            }
            
            var marker = new L.marker([stores[i]['location']['latitude'], stores[i]['location']['longitude']]).bindPopup( content );
            markerCluster.addLayer( marker );
        }

        map.addLayer( markerCluster );
        map.fitBounds( markerCluster.getBounds() );
    }

    else
    {
        var emptyPopup = L.popup().setLatLng([0, 0]).setContent("<p>Sorry, no stores were found to match your search.</p>").openOn(map);
    }

    <?php else : ?>

    <?php $error_message = str_replace("tech.sirwellington.alchemy.arguments.FailedAssertionException: ","", wp_remote_retrieve_body($response)); ?>

    var errorMessage = "<?php echo $error_message; ?>";
    var errorPopup = L.popup().setLatLng([0, 0]).setContent("<p>Sorry, an error occurred. " + errorMessage + "</p>").openOn(map);

    <?php endif; ?>

    </script>

<?php

	return ob_get_clean();
}
add_shortcode('ebt', 'ebt_shortcode');

?>
