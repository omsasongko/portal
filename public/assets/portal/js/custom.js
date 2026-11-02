/******************************************
    Version: 1.0
/****************************************** */

(function ($) {
  "use strict";

  var $scrollRoot = $('html, body');
  var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Bootstrap 5 applies `scroll-behavior: smooth` to `:root` when motion is not
  // reduced. That causes each per-frame `scrollTop` write from jQuery's animate()
  // to start its own native smooth scroll, which makes navigation feel delayed
  // and janky. Force instant programmatic scrolling so our JS animation stays
  // fully controllable and responsive.
  document.documentElement.style.scrollBehavior = 'auto';

  // Smooth scrolling using jQuery easing
  $('a.js-scroll-trigger[href*="#"]:not([href="#"])').click(function (e) {
    if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname) {
      var target = $(this.hash);
      target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
      if (target.length) {
        e.preventDefault();
        var top = Math.max(target.offset().top - 54, 0);

        // Cancel any in-progress or queued animation so rapid clicks respond
        // immediately and only one navigation action is ever running.
        $scrollRoot.stop(true);

        if (prefersReducedMotion) {
          // Respect reduced-motion: jump straight to the target.
          $scrollRoot.scrollTop(top);
        } else {
          // Short, snappy scroll that starts moving on the first frame.
          $scrollRoot.animate({ scrollTop: top }, 300, 'easeOutCubic');
        }
      }
    }
  });

  // Closes responsive menu when a scroll trigger link is clicked
  $('.js-scroll-trigger').click(function () {
    var navbarCollapse = document.querySelector('.navbar-collapse');
    if (navbarCollapse) {
      bootstrap.Collapse.getOrCreateInstance(navbarCollapse).hide();
    }
  });

  // Activate scrollspy to add active class to navbar items on scroll
  new bootstrap.ScrollSpy(document.body, {
    target: '#mainNav',
    offset: 56
  });

  // Collapse Navbar
  var navbarCollapse = function () {
    if ($("#mainNav").offset().top > 100) {
      $("#mainNav").addClass("navbar-shrink");
    } else {
      $("#mainNav").removeClass("navbar-shrink");
    }
  };
  // Collapse now if page is not at top
  navbarCollapse();
  // Collapse the navbar when page is scrolled
  $(window).scroll(navbarCollapse);

  // Hide navbar when modals trigger
  $('.portfolio-modal').on('show.bs.modal', function (e) {
    $(".navbar").addClass("d-none");
  })
  $('.portfolio-modal').on('hidden.bs.modal', function (e) {
    $(".navbar").removeClass("d-none");
  })

  // Scroll to top  		
  if ($('#scroll-to-top').length) {
    var scrollTrigger = 100, // px
      backToTop = function () {
        var scrollTop = $(window).scrollTop();
        if (scrollTop > scrollTrigger) {
          $('#scroll-to-top').addClass('show');
        } else {
          $('#scroll-to-top').removeClass('show');
        }
      };
    backToTop();
    $(window).on('scroll', function () {
      backToTop();
    });
    $('#scroll-to-top').on('click', function (e) {
      e.preventDefault();
      $scrollRoot.stop(true);
      if (prefersReducedMotion) {
        $scrollRoot.scrollTop(0);
      } else {
        $scrollRoot.animate({ scrollTop: 0 }, 300, 'easeOutCubic');
      }
    });
  }



  // Banner 

  $('.heading').height($(window).height());
  $('.parallaxie').parallaxie();

  // LOADER
  $(window).load(function () {
    $("#preloader").on(500).fadeOut();
    $(".preloader").on(600).fadeOut("slow");
  });

  // Gallery Filter
  var Container = $('.container');
  Container.imagesLoaded(function () {
    var portfolio = $('.gallery-menu');
    portfolio.on('click', 'button', function () {
      $(this).addClass('active').siblings().removeClass('active');
      var filterValue = $(this).attr('data-filter');
      $grid.isotope({
        filter: filterValue
      });
    });
    var $grid = $('.gallery-list').isotope({
      itemSelector: '.gallery-grid'
    });

  });

  // FUN FACTS   

  function count($this) {
    var current = parseInt($this.html(), 10);
    current = current + 50; /* Where 50 is increment */
    $this.html(++current);
    if (current > $this.data('count')) {
      $this.html($this.data('count'));
    } else {
      setTimeout(function () {
        count($this)
      }, 30);
    }
  }
  $(".stat_count, .stat_count_download").each(function () {
    $(this).data('count', parseInt($(this).html(), 10));
    $(this).html('0');
    count($(this));
  });

  // CONTACT
  jQuery(document).ready(function () {
    $('#contactform').submit(function () {
      var action = $(this).attr('action');
      $("#message").slideUp(750, function () {
        $('#message').hide();
        $('#submit')
          .after('<img src="images/ajax-loader.gif" class="loader" />')
          .attr('disabled', 'disabled');
        $.post(action, {
          first_name: $('#first_name').val(),
          last_name: $('#last_name').val(),
          email: $('#email').val(),
          phone: $('#phone').val(),
          select_service: $('#select_service').val(),
          select_price: $('#select_price').val(),
          comments: $('#comments').val(),
          verify: $('#verify').val()
        },
          function (data) {
            document.getElementById('message').innerHTML = data;
            $('#message').slideDown('slow');
            $('#contactform img.loader').fadeOut('slow', function () {
              $(this).remove()
            });
            $('#submit').removeAttr('disabled');
            if (data.match('success') != null) $('#contactform').slideUp('slow');
          }
        );
      });
      return false;
    });
  });

  // WOW animations
  if (typeof WOW !== 'undefined') {
    new WOW({
      boxClass: 'wow',
      animateClass: 'animated',
      offset: 0,
      mobile: false,
      live: true,
      callback: function (box) {},
      scrollContainer: null
    }).init();
  }

  // Lightbox
  $('a[data-gal]').each(function () {
    $(this).attr('rel', $(this).data('gal'));
  });
  if ($.fn.prettyPhoto) {
    $("a[data-rel^='prettyPhoto']").prettyPhoto({
      animationSpeed: 'slow',
      theme: 'light_square',
      slideshow: true,
      overlay_gallery: true,
      social_tools: false,
      deeplinking: false
    });
  }

  // Owl Carousels
  if ($.fn.owlCarousel) {
    $('.owl-watch').owlCarousel({
      loop: false,
      margin: 0,
      dots: false,
      nav: true,
      responsiveClass: true,
      navText: [
        "<i class='fa fa-angle-left effect-1'></i>",
        "<i class='fa fa-angle-right effect-1'></i>"
      ],
      responsive: {
        0: { nav: false, items: 1 },
        600: { items: 3 },
        1000: { items: 4 }
      }
    });

    $('.owl-services').owlCarousel({
      loop: false,
      margin: 30,
      dots: false,
      nav: true,
      responsiveClass: true,
      navText: [
        "<i class='fa fa-angle-left effect-1'></i>",
        "<i class='fa fa-angle-right effect-1'></i>"
      ],
      responsive: {
        0: { nav: false, items: 1 },
        600: { items: 2 },
        1000: { items: 3 }
      }
    });

    $('.testi-carousel').each(function () {
      var perSlide = parseInt($(this).attr('data-per-slide') || '2', 10);
      if (isNaN(perSlide) || perSlide < 1) {
        perSlide = 2;
      }
      $(this).owlCarousel({
        loop: true,
        margin: 30,
        dots: true,
        nav: true,
        responsiveClass: true,
        navText: [
          "<i class='fa fa-angle-left effect-1'></i>",
          "<i class='fa fa-angle-right effect-1'></i>"
        ],
        responsive: {
          0: { items: 1, nav: true },
          600: { items: perSlide === 1 ? 1 : 2, nav: true },
          1000: { items: perSlide, loop: true },
          1200: { items: perSlide, loop: true }
        }
      });
    });

    $('.owl-screenshots').owlCarousel({
      loop: false,
      margin: 30,
      dots: false,
      nav: true,
      responsiveClass: true,
      navText: [
        "<i class='fa fa-angle-left effect-1'></i>",
        "<i class='fa fa-angle-right effect-1'></i>"
      ],
      responsive: {
        0: { items: 1 },
        600: { items: 3 },
        1000: { items: 4 }
      }
    });

    $('.owl-app').owlCarousel({
      loop: true,
      margin: 0,
      autoplay: true,
      autoplayTimeout: 3000,
      autoplayHoverPause: true,
      dots: false,
      animateIn: 'fadeIn',
      animateOut: 'fadeOut',
      nav: false,
      responsiveClass: true,
      navText: [
        "<i class='fa fa-angle-left effect-1'></i>",
        "<i class='fa fa-angle-right effect-1'></i>"
      ],
      responsive: {
        0: { items: 1 },
        600: { items: 1 },
        1000: { items: 1 },
        1200: { items: 1 }
      }
    });
  }

})(jQuery);