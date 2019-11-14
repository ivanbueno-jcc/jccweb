(function ($, Drupal) {
  Drupal.behaviors.uswds_base_jcc = {
    attach: function (context, settings) {

    /****** Smooth scroll ******/
    // Select all links with hashes
    $('a[href*="#"]')
    // Remove links that don't actually link to anything
    .not('[href="#"]')
    .not('[href="#0"]')
    .on('click', function(event) {
        // On-page links
        if (
            location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '')
            &&
            location.hostname == this.hostname
            ) {
            // Figure out element to scroll to
            var target = $(this.hash);
            target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
            // Does a scroll target exist?
            if (target.length) {
                // Only prevent default if animation is actually gonna happen
                event.preventDefault();
                $('html, body').animate({
                scrollTop: target.offset().top
                }, 300, function() {
                // Callback after animation
                // Must change focus!
                var $target = $(target);
                $target.focus();
                if ($target.is(":focus")) { // Checking if the target was focused
                    return false;
                } else {
                    $target.attr('tabindex','-1'); // Adding tabindex for elements not focusable
                    $target.focus(); // Set focus again
                };
                });
            }
        }
    });

    /****** Return to Top ******/
    const scrollToTopButton = document.getElementById('js-top');

    // Let's set up a function that shows our scroll-to-top button if we scroll beyond the height of the initial window.
    const scrollFunc = function() {
        // Get the current scroll value
        let y = window.pageYOffset || document.documentElement.scrollTop;

        // If the scroll value is greater than the window height, let's add a class to the scroll-to-top button to show it!
        if (scrollToTopButton) {
          if (y > 0) {
            scrollToTopButton.className = "top-link show";
          }
          else {
            scrollToTopButton.className = "top-link hide";
          }
        }
    };
    window.addEventListener("scroll", scrollFunc);


    } // end attach function
  };
})(jQuery, Drupal);
