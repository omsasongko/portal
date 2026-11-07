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

  // ------------------------------------------------------------------
  // Section navigation — single source of truth for the homepage.
  //   click  -> smooth-scroll past the fixed header + pushState (new entry)
  //   manual scroll -> ScrollSpy drives .active; we mirror it to the URL with
  //                    replaceState (no history spam)
  //   Back/Forward / direct hash -> restore the matching section
  // ------------------------------------------------------------------

  // Headers tagged "top" (the brand logo uses #page-top, the body id) resolve
  // to the very top of the page and are recorded as #home in the URL.
  var isTopHash = function (hash) {
    return !hash || hash === '#' || hash === '#page-top';
  };

  var canonicalHash = function (hash) {
    return isTopHash(hash) ? '#home' : hash;
  };

  // Fixed-header height, used BOTH to offset the scroll target and to configure
  // ScrollSpy so the landing position and the "active" line always agree. The
  // `.navbar-shrink` style is a couple of pixels taller, so use the un-shrunk
  // height (68) as a stable reference; the section's own 70px top padding keeps
  // headings fully visible either way.
  var HEADER_OFFSET = (function () {
    var nav = document.getElementById('mainNav');
    var h = nav ? nav.offsetHeight : 0;
    return h >= 64 ? h : 68; // ignore pre-load / toggler-only heights
  })();

  var resolveTarget = function (hash) {
    if (isTopHash(hash)) {
      return { top: 0 };
    }
    var target = $(hash);
    if (!target.length) {
      target = $('[name=' + hash.slice(1) + ']');
    }
    if (!target.length) {
      return null;
    }
    // Leave a small visual gap (8px) so the section heading is not glued to
    // the navbar and ScrollSpy's active zone is clearly entered.
    return { top: Math.max(target.offset().top - HEADER_OFFSET + 8, 0) };
  };

  // True while a link-driven animation is in flight, so the ScrollSpy
  // "activate" handler does not rewrite the URL mid-animation.
  var programmaticScroll = false;

  // The hash the current programmatic scroll is driving toward. ScrollSpy may
  // briefly mark other sections active while the page is moving; we ignore any
  // activate event whose href matches the one we are already heading to.
  var suppressingHash = null;

  // Set the matching nav link active and deactivate its siblings. Used as a
  // reliable fallback when Bootstrap ScrollSpy's IntersectionObserver hasn't
  // caught up after a programmatic jump.
  var setNavActive = function (hash) {
    var href = canonicalHash(hash);
    $('#mainNav .nav-link').removeClass('active');
    var link = document.querySelector('#mainNav .nav-link[href="' + href + '"]');
    if (link) {
      $(link).addClass('active');
      // Also mark parent list items for Bootstrap ScrollSpy compatibility.
      $(link).parents('li').addClass('active');
    }
  };

  var scrollToHash = function (hash, push) {
    var resolved = resolveTarget(hash);
    if (!resolved) return;

    programmaticScroll = true;
    var fragment = canonicalHash(hash);
    suppressingHash = fragment;

    var settle = function () {
      // Force the correct active item so the nav always reflects the target
      // even when ScrollSpy's direction-aware observer skips the jump.
      setNavActive(hash);
      programmaticScroll = false;
      suppressingHash = null;
      // Refresh ScrollSpy so subsequent manual scrolling remains in sync.
      var spy = bootstrap.ScrollSpy.getInstance(document.body);
      if (spy) spy.refresh();
    };

    if (prefersReducedMotion) {
      $scrollRoot.scrollTop(resolved.top);
      settle();
    } else {
      $scrollRoot.stop(true).animate({ scrollTop: resolved.top }, 300, 'easeOutCubic', settle);
    }

    if (push) {
      history.pushState(null, '', fragment);
    } else {
      history.replaceState(null, '', fragment);
    }
  };

  // Smooth scrolling for in-page section links (and the brand logo).
  $('a.js-scroll-trigger[href*="#"]:not([href="#"])').click(function (e) {
    if (location.pathname.replace(/^\//, '') !== this.pathname.replace(/^\//, '') ||
        location.hostname !== this.hostname) {
      return; // cross-page link -> let the browser navigate natively
    }
    e.preventDefault();
    scrollToHash(this.hash, true);
  });

  // Closes responsive menu when a scroll trigger link is clicked
  $('.js-scroll-trigger').click(function () {
    var navbarCollapse = document.querySelector('.navbar-collapse');
    if (navbarCollapse) {
      // toggle:false stops getOrCreateInstance from auto-showing a closed
      // (or never-opened) menu; hide() then just collapses an open one.
      bootstrap.Collapse.getOrCreateInstance(navbarCollapse, { toggle: false }).hide();
    }
  });

  // Activate scrollspy to add the active class to navbar items on scroll.
  new bootstrap.ScrollSpy(document.body, {
    target: '#mainNav',
    offset: HEADER_OFFSET
  });

  // Track whether the user has scrolled at all, so a plain initial load of "/"
  // (no fragment) is not silently rewritten to "/#home".
  var hasScrolled = false;
  $(window).on('scroll.navhash', function () { hasScrolled = true; });

  // Mirror ScrollSpy's choice to the URL (in place, no new history entry)
  // whenever the active section changes from a real user scroll. We only write
  // the hash once the user has actually scrolled, so a direct load like /#about
  // (or the initial top-of-page "Home" state) is never rewritten to #home.
  $(document.body).on('activate.bs.scrollspy', function (e) {
    if (programmaticScroll) return;          // a link/logo animation owns the URL
    if (!hasScrolled) return;                // initial load / direct hash -> leave URL alone
    var link = e.relatedTarget;
    if (!link || !link.classList || !link.classList.contains('nav-link')) return;
    var href = link.getAttribute('href');
    if (!href || href.charAt(0) !== '#') return;
    var fragment = canonicalHash(href);
    if (fragment === suppressingHash) return; // we are already driving to this hash
    history.replaceState(null, '', fragment);
  });

  // Take over scroll restoration so Back/Forward and direct fragments always
  // land below the fixed header.
  if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
  }

  var restoreFromHash = function () {
    var hash = location.hash;
    if (hash && hash !== '#') {
      scrollToHash(hash, false);
    } else {
      // No fragment: return to the top (Home).
      programmaticScroll = true;
      var settle = function () { programmaticScroll = false; };
      if (prefersReducedMotion) {
        $scrollRoot.scrollTop(0);
        settle();
      } else {
        $scrollRoot.stop(true).animate({ scrollTop: 0 }, 300, 'easeOutCubic', settle);
      }
    }
  };
  window.addEventListener('popstate', restoreFromHash);
  window.addEventListener('hashchange', restoreFromHash);

  // Direct load with a fragment (or a cross-page "/#section" link landing
  // here): once images/layout settle, move below the fixed header without
  // creating a history entry (the fragment is already in the URL).
  $(window).on('load', function () {
    var hash = location.hash;
    if (hash && hash !== '#') {
      scrollToHash(hash, false);
    }
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
      // Scroll to the top and reflect it in the URL as #home (replaceState,
      // so the "back to top" utility never adds a history entry).
      scrollToHash('#page-top', false);
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