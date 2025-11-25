jQuery(document).ready(function ($) {
  // Scroll To Top
  const upArrow = $('.social-icons .elementor-social-icon-angle-up');
  upArrow.on('click', function (e) {
    e.preventDefault();
    window.scrollTo({
      top: 0,
      behavior: 'smooth'  // Native smooth scroll (much faster)
    });
  });
});

jQuery(document).ready(function ($) {
  // Handle role and timeline button clicks
  $(document).on('click', '.role-btn, .timeline-btn', function () {
    const $this = $(this);
    const targetSelector = $this.data('target');
    const $targetRadio = $(targetSelector);

    if ($targetRadio.length) {
      // Uncheck all radios in the same group
      $targetRadio.closest('.gfield_radio')
        .find('input[type="radio"]').prop('checked', false);

      // Check the clicked one
      $targetRadio.prop('checked', true).trigger('change');
    }

    // Highlight selected button within its group wrapper
    if ($this.hasClass('role-btn')) {
      $this.closest('.role-buttons').find('.role-btn').removeClass('selected');
    } else if ($this.hasClass('timeline-btn')) {
      $this.closest('.timeline-btns').find('.timeline-btn').removeClass('selected');
    }

    $this.addClass('selected');
  });
});

// --- Goal - Checkbox Script
jQuery(document).ready(function ($) {
  function initGoalButtons() {
    const $buttons = $("#field_3_17 .goal-btn").not(".select-all");
    const $selectAllBtn = $("#field_3_17 .goal-btn.select-all");

    // Remove any previous bindings (important when moving between pages)
    $buttons.off("click");
    $selectAllBtn.off("click");

    // Toggle individual buttons
    $buttons.on("click", function () {
      const $checkbox = $($(this).data("target"));
      if ($checkbox.length) {
        $checkbox.prop("checked", !$checkbox.prop("checked"));
        $(this).toggleClass("selected", $checkbox.prop("checked"));
        updateSelectAllButton();
      }
      $(this).blur(); // remove focus
    });

    // Handle Select All / Deselect All
    $selectAllBtn.on("click", function () {
      const allSelected = $buttons.toArray().every((btn) => {
        return $($(btn).data("target")).prop("checked");
      });

      $buttons.each(function () {
        const $checkbox = $($(this).data("target"));
        if ($checkbox.length) {
          $checkbox.prop("checked", !allSelected);
          $(this).toggleClass("selected", !allSelected);
        }
      });

      updateSelectAllButton();
      $(this).blur(); // remove focus
    });

    function updateSelectAllButton() {
      const allSelected = $buttons.toArray().every((btn) => {
        return $($(btn).data("target")).prop("checked");
      });

      $selectAllBtn.toggleClass("selected", allSelected);
      $selectAllBtn.text(allSelected ? "Deselect All" : "Select All");
    }
  }

  // Init on page load
  initGoalButtons();

  // Re-init whenever a new GF page loads
  $(document).on("gform_page_loaded", function () {
    initGoalButtons();
  });
});



// -- Script - that direct user to the home page without messing scroll and reload effect
jQuery(document).ready(function($) {
    $('a[href*="#"]').on('click', function(e) {
        var targetId = $(this).attr('href').split('#')[1]; // get section id
        if (!targetId) return; // no section

        var currentUrl = window.location.href.split('#')[0]; 
        var homeUrl = "https://" + location.hostname; // your homepage

        if (currentUrl !== homeUrl) {
            // If we are on booking or thank-you → redirect to homepage with hash
            e.preventDefault();
            window.location.href = homeUrl + "#" + targetId;
        } else {
            // Already on homepage → smooth scroll
            e.preventDefault();
            var target = $("#" + targetId);
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top
                }, 600);
            }
        }
    });
});


// Send GF data when user on different form pages
jQuery(document).ready(function($) {

  // --- Utility: SHA256 Hash (lowercase, trimmed)
  function sha256Hash(str) {
    if (!str) return "";
    let shaObj = new jsSHA("SHA-256", "TEXT", { encoding: "UTF8" });
    shaObj.update(str.trim().toLowerCase());
    return shaObj.getHash("HEX");
  }
  
   // Save query params into cookies (dlv_ prefix) 
  function setCookie(name, value, days) {
      var expires = "";
      if (days) {
          var date = new Date();
          date.setTime(date.getTime() + (days*24*60*60*1000));
          expires = "; expires=" + date.toUTCString();
      }
      document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/";
  }
  
  // --- Get cookie helper
  function getCookie(name) {
    let match = document.cookie.match(new RegExp("(^| )" + name + "=([^;]+)"));
    return match ? decodeURIComponent(match[2]) : "";
  }

  // Process query params on page load
  var queryString = window.location.search.substring(1);
  if (queryString) {
      var params = new URLSearchParams(queryString);
      params.forEach(function(value, key) {
        // Only set cookies for selected keys
        var allowedKeys = ["fname", "lname", "email", "phone", "gclid", "utm_source", "fbclid", "wbraid", "gbraid"];
        if (allowedKeys.includes(key)) {
            // Always overwrite cookie with latest value
            setCookie("dlv_" + key, value, 30);
        }
      });
  }

  // --- Listen for clicks on GF form pages + submit (Form ID 3)
  $(document).on("click", "#gform_next_button_3_18, #gform_next_button_3_19, #gform_submit_button_3", function() {
    let isSubmit = $(this).attr("id") === "gform_submit_button_3";

    
    // --- Get values from cookies or fallback to GF fields
    var fbclid_cookie = getCookie("dlv_fbclid");
    var gclid_cookie  = getCookie("dlv_gclid");
    var wbraid        = getCookie("wbraid");
    var gbraid        = getCookie("gbraid");
    
    var fbclid = fbclid_cookie || $("#input_3_5").val() || "";
    var gclid  = gclid_cookie  || $("#input_3_3").val() || "";
    
    // --- Ensure GF hidden fields are set so they submit
    if (fbclid) {
      $("#input_3_5").val(fbclid);
    }
    if (gclid) {
      $("#input_3_3").val(gclid);
    }

    // Collect values
    var formID    = "3";
    // var fbclid    = $("#input_3_5").val() || "";
    // var gclid     = $("#input_3_3").val() || "";
    var firstName = $("#input_3_6_3").val() || "";
    var lastName  = $("#input_3_6_6").val() || "";
    var email     = $("#input_3_7").val() || "";
    var phone     = $("#input_3_8").val() || "";
    var role      = $("input[name='input_9']:checked").val() || "";
    var goal      = $("#input_3_11 input:checked").map(function(){ return $(this).val(); }).get().join(", ");
    var timeline  = $("input[name='input_12']:checked").val() || "";
    var message   = $("#input_3_14").val() || "";

    // Determine source
    var source = gclid ? "google" : (fbclid ? "meta" : "website");
    
    // Check if required fields are empty
    if (firstName.trim() === "" || email.trim() === "" || phone.trim() === "") {
        return; // stop execution if any required field is empty
    }

    // Push safe data to dataLayer for GA4
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: "gravityFormSubmit",
      form_id: formID,
      source: source,
      role: role,
      goal: goal,
      timeline: timeline,
      message: message,
      // Safe hashed values (SHA256)
      email: email,
      phone: phone,
      first_name: firstName,
      last_name: lastName,
      // Keep raw click IDs (these are allowed)
      gclid: gclid,
      fbclid: fbclid,
      wbraid: wbraid,
      gbraid: gbraid
    });
    

    // Optional: also fire AJAX to WordPress (non-PII raw data handling)
    $.ajax({
      url: slw_ajax_obj.ajax_url,
      type: "POST",
      data: {
        action: "form_submit",
        _ajax_nonce: slw_ajax_obj.nonce,
        data: {
          formID: formID,
          fbclid: fbclid,
          gclid:  gclid,
          wbraid: wbraid,
          gbraid: gbraid,
          source: source,
          first_name: firstName,
          last_name: lastName,
          email: email,
          phone: phone,
          role: role,
          goal: goal,
          timeline: timeline,
          message: message,
          finalSubmit: isSubmit,
        },
      },
      success: function (response) {
        console.log("Form data sent");
      },
      error: function (xhr, status, error) {
        console.error("AJAX error:", error);
      },
    });
  });

});


// -- script - Gravity Form Loader
jQuery(document).ready(function($) {
  $(document).on("click", ".gform_next_button", function () {
    var $btn = $(this);

    // Avoid multiple spinners
    if ($btn.siblings(".gform-loader").length === 0) {
      $btn.after('<span class="gform-loader"></span>');
    }

    // Remove loader once page AJAX completes
    $(document).one("gform_page_loaded", function() {
      $(".gform-loader").remove();
    });
  });
});



// -- Script - Widget Scrolling (Desktop Only)
jQuery(document).ready(function($) {
    var $widget = $('.social-icons'); // change selector to your widget
    var desktopBreakpoint = 1024; // tablet starts from 1024px

    $(window).on('scroll resize', function() {
        if ($(window).width() >= desktopBreakpoint) {
            var scrollBottom = $(document).height() - $(window).scrollTop() - $(window).height();

            if (scrollBottom <= 80) {
                // Close to bottom -> smoothly move widget up to 80px
                $widget.stop().animate({ bottom: '80px' }, 300);
            } else {
                // Reset to 10px when scrolling up
                $widget.stop().animate({ bottom: '10px' }, 300);
            }
        } else {
            // Reset to default for tablet/mobile
            $widget.css('bottom', '');
        }
    });
});

