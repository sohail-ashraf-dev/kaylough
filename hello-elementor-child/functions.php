<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );
if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;


// END ENQUEUE PARENT ACTION
add_action( 'wp_enqueue_scripts', 'child_theme_configurator_css', 10 );
if ( !function_exists( 'child_theme_configurator_css' ) ):
    function child_theme_configurator_css() {
        wp_enqueue_style( 'chld_thm_cfg_child', trailingslashit( get_stylesheet_directory_uri() ) . 'style.css', array( 'hello-elementor','hello-elementor-theme-style','hello-elementor-header-footer' ) );

        // Enqueue child theme JS
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'sha256', 'https://cdnjs.cloudflare.com/ajax/libs/jsSHA/3.3.1/sha256.min.js'
        );
        wp_enqueue_script(
            'slw-script',
            get_stylesheet_directory_uri() . '/assets/js/slw-script.js',
            array('jquery'), 
            //'filemtime( get_stylesheet_directory() . '/assets/js/slw-script.js' )', // cache busting (disabled on staging)
            '',
            true
        );
        
        // Localize the script with AJAX URL + nonce
        wp_localize_script( 'slw-script', 'slw_ajax_obj', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'slw_nonce' ),
        ) );
    }
endif;


add_action('wp_ajax_kaychat', 'handle_kaychat');
add_action('wp_ajax_nopriv_kaychat', 'handle_kaychat');
function handle_kaychat() {
    check_ajax_referer( 'slw_nonce', 'security' );

    $business_id = sanitize_text_field($_POST['business_id'] ?? '');
    $session_id  = sanitize_text_field($_POST['session_id'] ?? '');
    $message     = sanitize_text_field($_POST['message'] ?? '');
    $user        = json_decode(stripslashes($_POST['user'] ?? '{}'), true);

    $endpoint = KEY_CHAT_URL;

    // Prepare payload for external API
    $payload = array(
        'business_id' => $business_id,
        'session_id'  => $session_id,
        'message'     => $message,
        'user'        => $user
    );

    $response = wp_remote_post($endpoint, array(
        'headers' => array('Content-Type' => 'application/json'),
        'body'    => wp_json_encode($payload),
        'timeout' => 30,
    ));

    if (is_wp_error($response)) {
        wp_send_json_error(array(
            'answer' => '⚠️ Error contacting assistant (WP error).'
        ));
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    // Assume external API returns { "answer": "..." }
    if (!empty($body['answer'])) {
        wp_send_json_success(array(
            'answer' => $body['answer']
        ));
    } else {
        wp_send_json_error(array(
            'answer' => '⚠️ No answer from assistant.'
        ));
    }
}


// Form Submittion Ajax
// Hook into AJAX
add_action("wp_ajax_consultaion_form_submit", "callback_consultaion_form_submit");
add_action("wp_ajax_nopriv_consultaion_form_submit", "callback_consultaion_form_submit");
function callback_consultaion_form_submit() {
    // Grab posted data
    $data = isset($_POST['data']) ? $_POST['data'] : [];

    $business_partner_id = "2274316583026321"; // Kay Business Partner ID
    $event_name          = "Lead Form Submit";
    $dt                  = new DateTime();
    $msgTimeStamp        = $dt->format('Y-m-d H:i:s');

    // Build payload
    $payload = [
        'businessPartnerID' => $business_partner_id,
        'eventName'         => $event_name,
        'platform'          => "google",
        'userData'          => [
            "fbclid"     => sanitize_text_field($data["fbclid"] ?? ""),
            "gclid"      => sanitize_text_field($data["gclid"] ?? ""),
            "wbraid"      => sanitize_text_field($data["wbraid"] ?? ""),
            "gbraid"      => sanitize_text_field($data["gbraid"] ?? ""),
            "source"     => sanitize_text_field($data["source"] ?? ""),
            "first_name" => sanitize_text_field($data["first_name"] ?? ""),
            "last_name"  => sanitize_text_field($data["last_name"] ?? ""),
            "email"      => sanitize_email($data["email"] ?? ""),
            "phone"      => sanitize_text_field($data["phone"] ?? ""),
            "role"       => sanitize_text_field($data["role"] ?? ""),
            "goal"       => sanitize_text_field($data["goal"] ?? ""),
            "timeline"   => sanitize_text_field($data["timeline"] ?? ""),
            "message"    => sanitize_textarea_field($data["message"] ?? ""),
            "conversion_time" => $msgTimeStamp,
            "finalSubmit" => sanitize_text_field($data["finalSubmit"]),
        ]
    ];

    // Send to remote server
    $response = wp_remote_post(EVENT_ROUT, [
        'headers' => [
            'Content-Type' => 'application/json',
            'X-API-KEY'     => EVENT_ROUT_KEY,
        ],
        'body'        => wp_json_encode($payload),
        'method'      => 'POST',
        'data_format' => 'body',
    ]);

    // Send back response for JS
    wp_send_json([
        "success"  => true,
        "payload"  => $payload,
        "response" => $response,
    ]);
}

add_action('gform_post_paging', 'slw_send_email_on_page_1', 10, 3);
function slw_send_email_on_page_1($form, $source_page_number, $current_page_number) {

    // Target only Form ID 3
    if ( (int) $form['id'] !== 3 ) {
        return;
    }

    $entry = GFFormsModel::get_current_lead();

    // Trigger only when moving from Page 1 -> Page 2
    if ( $current_page_number == 1 ) {

        $first_name = rgar($entry, '6.3'); // First Name
        $last_name  = rgar($entry, '6.6'); // Last Name
        $email      = rgar($entry, '7');   // Email
        $phone      = rgar($entry, '8');   // Phone
        $source     = rgar($entry, '4');   // Source (utm_source)
        $gclid      = rgar($entry, '3');   // Google Click ID

        // === Build message ===
        // $to      = get_option('admin_email');
        $to         = "abc@mail.com";
        $subject = '🚀 First Lead Captured - Page 1 Completed';
        $message = "Hello Admin,\n\n";
        $message .= "A new lead came from " . $source . " and started filling the form.\n\n";
        $message .= "📋 Lead Details (Partial Submission):\n";
        $message .= "----------------------------------------\n";
        $message .= "First Name: " . $first_name . "\n";
        $message .= "Last Name: " . $last_name . "\n";
        $message .= "Email: " . $email . "\n";
        $message .= "Phone: " . $phone . "\n";
        $message .= "Source: " . $source . "\n";
        $message .= "----------------------------------------\n\n";
        $message .= "This is an automated notification from your Gravity Form.\n";

        // Send email (uncomment to enable)
        wp_mail($to, $subject, $message);
    }

}


// Hook into Gravity Forms after submission (Form ID = 1)
add_action('gform_after_submission_1', 'slw_contact_form', 10, 2);
function slw_contact_form($entry, $form) {

    // Fields Values
    $email = rgar($entry, "1");
    $message = rgar($entry, "2");

    $business_partner_id = "46734534576456345";
    $event_name          = "Contact Form Submit";
    $dt                  = new DateTime();
    $msgTimeStamp        = $dt->format('Y-m-d H:i:s');

    // Build payload
    $payload = [
        'businessPartnerID' => $business_partner_id,
        'eventName'         => $event_name,
        'platform'          => "google",
        'userData'          => [
            "email"      => sanitize_email($email ?? ""),
            "message"    => sanitize_textarea_field($message ?? ""),
            "conversion_time" => $msgTimeStamp,
        ]
    ];

    // Send to remote server
    wp_remote_post(EVENT_ROUT, [
        'headers' => [
            'Content-Type' => 'application/json',
            'X-API-KEY'     => EVENT_ROUT_KEY),
        ],
        'body'        => wp_json_encode($payload),
        'method'      => 'POST',
        'data_format' => 'body',
    ]);

    ?>
    <script>
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({
        'event': 'gravityContactFormSubmit',
        'contactFormId': '<?php echo esc_js($form['id']); ?>',
        'email': '<?php echo esc_js($email); ?>'
        'message': '<?php echo esc_js($message); ?>'
      });
    </script>
    <?php
}

// Footer Script Enqueues
add_action( 'wp_footer', function () { ?>
    <!-- Slick CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css" />

    <!-- Slick JS -->
    <script src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>

    <script>
       jQuery(document).ready(function($) {
            $('#testimonial-slider').slick({
                slidesToShow: 3,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 3000,
                prevArrow: '<button type="button" class="slick-prev custom-arrow"><i class="fas fa-arrow-left"></i></button>',
                nextArrow: '<button type="button" class="slick-next custom-arrow"><i class="fas fa-arrow-right"></i></button>',
                responsive: [
                    { breakpoint: 1024, settings: { slidesToShow: 2 } },
                    { breakpoint: 768, settings: { slidesToShow: 1 } }
                ]
            });
        });
    </script>
<?php });

// Shortcode to display consultation info dynamically
add_shortcode('consultation', 'consultation_shortcode');
function consultation_shortcode($atts) {
    // Shortcode attributes
    $atts = shortcode_atts(
        array(
            'field' => '', // default is empty
        ),
        $atts,
        'consultation'
    );

    // Get URL parameters safely
    $fname  = isset($_GET['fname']) ? sanitize_text_field($_GET['fname']) : '';
    $lname  = isset($_GET['lname']) ? sanitize_text_field($_GET['lname']) : '';
    $full_name = trim($fname . ' ' . $lname);
    $email = isset($_GET['email']) ? sanitize_email($_GET['email']) : '';
    $phone = isset($_GET['phone']) ? sanitize_text_field($_GET['phone']) : '';

    // Return value based on field attribute
    if ($atts['field'] === 'full-name') {
        if (empty($full_name)) {
            echo '<style>.full-name-grid{display:none !important;}</style>';
        }
        return $full_name ?: ''; // return empty if not set
    } elseif ($atts['field'] === 'fname') {
        return $fname ?: '';
    } elseif ($atts['field'] === 'lname') {
        return $lname ?: '';
    } elseif ($atts['field'] === 'email') {
        if (empty($email)) {
            echo '<style>.email-grid{display:none !important;}</style>';
        }
        return $email ?: '';
    } elseif ($atts['field'] === 'phone') {
        if (empty($phone)) {
            echo '<style>.phone-grid{display:none !important;}</style>';
        }
        return $phone ?: '';
    }

    return ''; // default empty
}

// For adding script in head
add_action('wp_head', 'head_script_enqueue_call_back', 1);
function head_script_enqueue_call_back() {
    ?>
    <!-- Google Tag Manager -->
    <script>
    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-12345678');
    </script>
    <!-- End Google Tag Manager -->
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
    
      // ✅ Consent defaults must come first
      gtag('consent', 'default', {
        'ad_storage': 'granted',
        'analytics_storage': 'granted',
        'functionality_storage': 'granted',
        'personalization_storage': 'granted',
        'security_storage': 'granted',
        'ad_user_data': 'granted',
        'ad_personalization': 'granted'
      });

    </script>

    <?php
    
}

// For adding script in body
add_action('wp_body_open', 'body_script_enqueue_call_back', 10);
function body_script_enqueue_call_back() {
    ?>
     <!--Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-1234567"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
 <!--End Google Tag Manager (noscript) -->
    <?php
}

