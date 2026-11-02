<!DOCTYPE html>
<html lang="id">
<head>
    <!-- Basic -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">   
   
    <!-- Mobile Metas -->
    <meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
 
     <!-- Site Metas -->
    <title><?= esc($identity['site_title'] ?? 'Dominic') ?></title>
    <meta name="keywords" content="<?= esc($identity['meta_keywords'] ?? '') ?>">
    <meta name="description" content="<?= esc($identity['meta_description'] ?? '') ?>">
    <meta name="author" content="<?= esc($identity['meta_author'] ?? '') ?>">

    <!-- Site Icons -->
    <link rel="shortcut icon" href="<?= base_url($identity['favicon'] ?? 'assets/portal/images/favicon.ico') ?>" type="image/x-icon" />
    <link rel="apple-touch-icon" href="<?= base_url($identity['apple_touch_icon'] ?? 'assets/portal/images/apple-touch-icon.png') ?>">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/portal/css/bootstrap.min.css') ?>">
    <!-- Site CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/portal/style.css') ?>">
    <!-- Responsive CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/portal/css/responsive.css') ?>">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/portal/css/custom.css') ?>">
	<script src="<?= base_url('assets/portal/js/modernizr.js') ?>"></script> <!-- Modernizr -->

    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>
<body id="page-top" class="politics_version">

    <?php if (($identity['show_preloader'] ?? '1') === '1'): ?>
    <!-- LOADER -->
    <div id="preloader">
        <div id="main-ld">
			<div id="loader"></div>  
		</div>
    </div><!-- end loader -->
    <!-- END LOADER -->
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNav">
      <div class="container">
        <a class="navbar-brand js-scroll-trigger" href="#page-top">
			<img class="img-fluid" src="<?= base_url($identity['logo'] ?? 'assets/portal/images/logo.png') ?>" alt="<?= esc($identity['site_name'] ?? '') ?>" />
		</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
          Menu
          <i class="fa fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
          <ul class="navbar-nav text-uppercase ms-auto">
            <li class="nav-item">
              <a class="nav-link js-scroll-trigger active" href="#home">Home</a>
            </li>
            <?php if ($aboutEnabled): ?>
            <li class="nav-item">
              <a class="nav-link js-scroll-trigger" href="#about">About Us</a>
            </li>
            <?php endif; ?>
            <?php if ($servicesEnabled): ?>
            <li class="nav-item">
              <a class="nav-link js-scroll-trigger" href="#services">Services</a>
            </li>
            <?php endif; ?>
            <?php if ($portfolioEnabled): ?>
            <li class="nav-item">
              <a class="nav-link js-scroll-trigger" href="#portfolio">Portfolio</a>
            </li>
            <?php endif; ?>
            <?php if ($testimonialsEnabled): ?>
            <li class="nav-item">
              <a class="nav-link js-scroll-trigger" href="#testimonials">Testimonials</a>
            </li>
            <?php endif; ?>
			<li class="nav-item">
              <a class="nav-link js-scroll-trigger" href="#blog">Blog</a>
            </li>
			<li class="nav-item">
              <a class="nav-link js-scroll-trigger" href="#contact">Contect Us</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
	
	<section id="home" class="main-banner parallaxie" style="background: url('<?= base_url($hero['background_image'] ?? 'assets/portal/uploads/banner-01.jpg') ?>')">
		<div class="heading">
			<h1><?= nl2br(esc($hero['heading'] ?? "hello i'm Dominic")) ?></h1>
			<p><?= nl2br(esc($hero['subheading'] ?? '')) ?></p>
            <?php if (($hero['use_rotating_words'] ?? true) && ! empty($hero['rotating_words'])): ?>
			<h3 class="cd-headline clip is-full-width">
				<span class="cd-words-wrapper">
                    <?php foreach ($hero['rotating_words'] as $idx => $word): ?>
					    <b class="<?= $idx === 0 ? 'is-visible' : '' ?>"><?= esc($word) ?></b>
                    <?php endforeach; ?>
				</span>
			</h3>
            <?php endif; ?>
            <?php if (! empty($hero['button_label'])): ?>
                <a href="<?= esc($hero['button_link'] ?: '#') ?>" class="sim-btn btn-hover-new mt-3" data-text="<?= esc($hero['button_label']) ?>"><span><?= esc($hero['button_label']) ?></span></a>
            <?php endif; ?>
		</div>
	</section>

	<svg id="clouds" class="d-none d-sm-block" xmlns="http://www.w3.org/2000/svg" version="1.1" width="100%" height="100" viewBox="0 0 85 100" preserveAspectRatio="none">
        <path d="M-5 100 Q 0 20 5 100 Z
            M0 100 Q 5 0 10 100
            M5 100 Q 10 30 15 100
            M10 100 Q 15 10 20 100
            M15 100 Q 20 30 25 100
            M20 100 Q 25 -10 30 100
            M25 100 Q 30 10 35 100
            M30 100 Q 35 30 40 100
            M35 100 Q 40 10 45 100
            M40 100 Q 45 50 50 100
            M45 100 Q 50 20 55 100
            M50 100 Q 55 40 60 100
            M55 100 Q 60 60 65 100
            M60 100 Q 65 50 70 100
            M65 100 Q 70 20 75 100
            M70 100 Q 75 45 80 100
            M75 100 Q 80 30 85 100
            M80 100 Q 85 20 90 100
            M85 100 Q 90 50 95 100
            M90 100 Q 95 25 100 100
            M95 100 Q 100 15 105 100 Z">
        </path>
    </svg>

    <?php if ($aboutEnabled): ?>
    <div id="about" class="section wb">
        <div class="container">
            <div class="row align-items-center">
                <?php
                $aboutLayout = $about['layout'] ?? 'image_right';
                $textColClass = 'col-md-12';
                $imageColClass = 'col-md-6';
                if (in_array($aboutLayout, ['image_right', 'image_left'], true)) {
                    $textColClass = 'col-md-6';
                }

                $textBlock = function () use ($about) {
                    $body = nl2br(esc($about['body'] ?? ''));
                    $buttonLabel = $about['button_label'] ?? '';
                    $buttonLink = $about['button_link'] ?? '#';
                    ob_start();
                    ?>
                    <div class="message-box">
                        <h2><?= esc($about['heading'] ?? 'About') ?></h2>
                        <p><?= $body ?></p>
                        <?php if (! empty($buttonLabel)): ?>
                            <a href="<?= esc($buttonLink) ?>" class="sim-btn btn-hover-new" data-text="<?= esc($buttonLabel) ?>"><span><?= esc($buttonLabel) ?></span></a>
                        <?php endif; ?>
                    </div>
                    <?php
                    return ob_get_clean();
                };

                $imageBlock = function () use ($about) {
                    if (($about['layout'] ?? 'image_right') === 'text_only') {
                        return '';
                    }
                    ob_start();
                    ?>
                    <div class="right-box-pro wow fadeIn">
                        <img src="<?= base_url($about['image'] ?? 'assets/portal/uploads/about_04.jpg') ?>" alt="<?= esc($about['image_alt'] ?? '') ?>" class="img-fluid rounded">
                    </div>
                    <?php
                    return ob_get_clean();
                };

                if ($aboutLayout === 'image_left'): ?>
                    <div class="<?= $imageColClass ?> order-md-1"><?= $imageBlock() ?></div>
                    <div class="<?= $textColClass ?> order-md-2"><?= $textBlock() ?></div>
                <?php elseif ($aboutLayout === 'image_right'): ?>
                    <div class="<?= $textColClass ?> order-md-1"><?= $textBlock() ?></div>
                    <div class="<?= $imageColClass ?> order-md-2"><?= $imageBlock() ?></div>
                <?php else: ?>
                    <div class="<?= $textColClass ?>"><?= $textBlock() ?></div>
                <?php endif; ?>
            </div><!-- end row -->
        </div><!-- end container -->
    </div><!-- end section -->
    <?php endif; ?>
	
    <?php if ($servicesEnabled): ?>
    <div id="services" class="section lb">
        <div class="container">
            <div class="section-title text-start">
                <h3><?= esc($services['title'] ?? 'Services') ?></h3>
                <?php if (! empty($services['subtitle'])): ?>
                <p><?= esc($services['subtitle']) ?></p>
                <?php endif; ?>
            </div><!-- end title -->

            <div class="row">
            <?php
            $servicesColClass = (($services['layout'] ?? 'grid_3_columns') === 'grid_2_columns') ? 'col-md-6' : 'col-md-4';
            foreach ($servicesItems as $card): ?>
                <div class="<?= $servicesColClass ?>">
                    <div class="services-inner-box">
                        <div class="ser-icon">
                            <i class="<?= esc($card['icon'] ?? '') ?>"></i>
                        </div>
                        <h2><?= esc($card['title'] ?? '') ?></h2>
                        <p><?= esc($card['description'] ?? '') ?></p>
                    </div>
                </div><!-- end col -->
            <?php endforeach; ?>
            </div><!-- end row -->
        </div><!-- end container -->
    </div><!-- end section -->
    <?php endif; ?>
	
	<?php if ($portfolioEnabled): ?>
	<div id="portfolio" class="section lb">
		<div class="container">
			<div class="section-title text-start">
                <h3><?= esc($portfolio['title'] ?? 'Portfolio') ?></h3>
                <?php if (! empty($portfolio['subtitle'])): ?>
                <p><?= esc($portfolio['subtitle']) ?></p>
                <?php endif; ?>
            </div><!-- end title -->
			
			<div class="gallery-menu row">
				<div class="col-md-12">
					<div class="button-group filter-button-group text-start">
						<button class="active" data-filter="*">All</button>
						<?php foreach ($portfolioCategories as $cat): ?>
						<button data-filter=".<?= esc($cat['filter_class']) ?>"><?= esc($cat['name']) ?></button>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			
			<div class="gallery-list row">
				<?php
				$portfolioColClass = (($portfolio['layout'] ?? 'masonry_3_columns') === 'grid_2_columns') ? 'col-md-6 col-sm-6' : 'col-md-4 col-sm-6';
				$categoryClassMap = [];
				foreach ($portfolioCategories as $cat) {
					$categoryClassMap[(int) $cat['id']] = $cat['filter_class'];
				}
				foreach ($portfolioItems as $item):
					$itemClasses = [];
					foreach ($item['category_ids'] as $cid) {
						if (isset($categoryClassMap[(int) $cid])) {
							$itemClasses[] = $categoryClassMap[(int) $cid];
						}
					}
					$classAttr = esc(implode(' ', $itemClasses));
					$titleAttr = ! empty($item['title']) ? ' title="' . esc($item['title']) . '"' : '';
				?>
				<div class="<?= $portfolioColClass ?> gallery-grid <?= $classAttr ?>">
					<div class="gallery-single fix">
						<img src="<?= base_url($item['image']) ?>" class="img-fluid" alt="<?= esc($item['image_alt'] ?? '') ?>">
						<div class="img-overlay">
							<?php if (! empty($item['link'])): ?>
							<a href="<?= esc($item['link']) ?>" class="hoverbutton global-radius"><i class="fa fa-picture-o"></i></a>
							<?php else: ?>
							<a href="<?= base_url($item['image']) ?>" data-rel="prettyPhoto[gal]" class="hoverbutton global-radius"<?= $titleAttr ?>><i class="fa fa-picture-o"></i></a>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			</div>
		</div>
	</div>
	<?php endif; ?>
<?php if ($testimonialsEnabled): ?>
<div id="testimonials" class="section wb">
        <div class="container">
            <div class="section-title text-start">
                <h3><?= esc($testimonials['title'] ?? 'Testimonials') ?></h3>
                <?php if (! empty($testimonials['subtitle'])): ?>
                <p><?= esc($testimonials['subtitle']) ?></p>
                <?php endif; ?>
            </div><!-- end title -->

            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <?php $perSlide = (($testimonials['layout'] ?? 'carousel_2_per_slide') === 'carousel_1_per_slide') ? 1 : 2; ?>
                    <div class="testi-carousel owl-carousel owl-theme" data-per-slide="<?= $perSlide ?>">
                        <?php foreach ($testimonialItems as $t): ?>
                        <div class="testimonial clearfix">
                            <div class="desc">
                                <h3><i class="fa fa-quote-left"></i> <?= esc($t['quote_heading'] ?? '') ?></h3>
                                <p class="lead"><?= esc($t['quote_body'] ?? '') ?></p>
                            </div>
                            <div class="testi-meta">
                                <?php if (! empty($t['avatar'])): ?>
                                <img src="<?= base_url($t['avatar']) ?>" alt="" class="img-fluid alignleft">
                                <?php endif; ?>
                                <h4><?= esc($t['author_name'] ?? '') ?><?php if (! empty($t['author_role'])): ?> <small>- <?= esc($t['author_role']) ?></small><?php endif; ?></h4>
                            </div>
                            <!-- end testi-meta -->
                        </div>
                        <!-- end testimonial -->
                        <?php endforeach; ?>
                    </div><!-- end carousel -->
                </div><!-- end col -->
            </div><!-- end row -->
        </div><!-- end container -->
    </div><!-- end section -->
    <?php endif; ?>
<div id="blog" class="section lb">
		<div class="container">
			<div class="section-title text-start">
                <h3>Blog</h3>
                <p>Quisque eget nisl id nulla sagittis auctor quis id. Aliquam quis vehicula enim, non aliquam risus.</p>
            </div><!-- end title -->
			
			<div class="row">
				<div class="col-md-4 col-sm-6 col-lg-4">
					<div class="post-box">
						<div class="post-thumb">
							<img src="<?= base_url('assets/portal/uploads/blog-01.jpg') ?>" class="img-fluid" alt="post-img" />
							<div class="date">
								<span>06</span>
								<span>Aug</span>
							</div>
						</div>
						<div class="post-info">
							<h4>Quisque auctor lectus interdum nisl accumsan venenatis.</h4>
							<ul>
                                <li>by admin</li>
                                <li>Apr 21, 2018</li>
                                <li><a href="#"><b> Comments</b></a></li>
                            </ul>
							<p>Etiam materials ut mollis tellus, vel posuere nulla. Etiam sit amet massa sodales aliquam at eget quam. Integer ultricies et magna quis.</p>
						</div>
					</div>
				</div>
				<div class="col-md-4 col-sm-6 col-lg-4">
					<div class="post-box">
						<div class="post-thumb">
							<img src="<?= base_url('assets/portal/uploads/blog-02.jpg') ?>" class="img-fluid" alt="post-img" />
							<div class="date">
								<span>06</span>
								<span>Aug</span>
							</div>
						</div>
						<div class="post-info">
							<h4>Quisque auctor lectus interdum nisl accumsan venenatis.</h4>
							<ul>
                                <li>by admin</li>
                                <li>Apr 21, 2018</li>
                                <li><a href="#"><b> Comments</b></a></li>
                            </ul>
							<p>Etiam materials ut mollis tellus, vel posuere nulla. Etiam sit amet massa sodales aliquam at eget quam. Integer ultricies et magna quis.</p>
						</div>
					</div>
				</div>
				<div class="col-md-4 col-sm-6 col-lg-4">
					<div class="post-box">
						<div class="post-thumb">
							<img src="<?= base_url('assets/portal/uploads/blog-03.jpg') ?>" class="img-fluid" alt="post-img" />
							<div class="date">
								<span>06</span>
								<span>Aug</span>
							</div>
						</div>
						<div class="post-info">
							<h4>Quisque auctor lectus interdum nisl accumsan venenatis.</h4>
							<ul>
                                <li>by admin</li>
                                <li>Apr 21, 2018</li>
                                <li><a href="#"><b> Comments</b></a></li>
                            </ul>
							<p>Etiam materials ut mollis tellus, vel posuere nulla. Etiam sit amet massa sodales aliquam at eget quam. Integer ultricies et magna quis.</p>
						</div>
					</div>
				</div>
			</div>
			
		</div>
	</div>

    <div id="contact" class="section db">
        <div class="container">
            <div class="section-title text-start">
                <h3>Contact</h3>
                <p>Quisque eget nisl id nulla sagittis auctor quis id. Aliquam quis vehicula enim, non aliquam risus.</p>
            </div><!-- end title -->

            <div class="row">
                <div class="col-md-12">
                    <div class="contact_form">
                        <div id="message"></div>
                        <form id="contactForm" name="sentMessage" novalidate="novalidate">
							<div class="row">
								<div class="col-md-6">
									<div class="mb-3">
										<input class="form-control" id="name" type="text" placeholder="Your Name" required="required" data-validation-required-message="Please enter your name.">
										<p class="form-text text-danger"></p>
									</div>
									<div class="mb-3">
										<input class="form-control" id="email" type="email" placeholder="Your Email" required="required" data-validation-required-message="Please enter your email address.">
										<p class="form-text text-danger"></p>
									</div>
									<div class="mb-3">
										<input class="form-control" id="phone" type="tel" placeholder="Your Phone" required="required" data-validation-required-message="Please enter your phone number.">
										<p class="form-text text-danger"></p>
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<textarea class="form-control" id="message" placeholder="Your Message" required="required" data-validation-required-message="Please enter a message."></textarea>
										<p class="form-text text-danger"></p>
									</div>
								</div>
								<div class="clearfix"></div>
								<div class="col-lg-12 text-center">
									<div id="success"></div>
									<button id="sendMessageButton" class="sim-btn btn-hover-new" data-text="Send Message" type="submit">Send Message</button>
								</div>
							</div>
						</form>
                    </div>
                </div><!-- end col -->
            </div><!-- end row -->
        </div><!-- end container -->
    </div><!-- end section -->

    <div class="copyrights">
        <div class="container">
            <div class="footer-distributed">
                <div class="footer-left">
                    <p class="footer-links">
                        <a href="#">Home</a>
                        <a href="#">Blog</a>
                        <a href="#">Pricing</a>
                        <a href="#">About</a>
                        <a href="#">Faq</a>
                        <a href="#">Contact</a>
                    </p>
                    <p class="footer-company-name">All Rights Reserved. &copy; 2018 <a href="#">Dominic</a> Design By : 
					<a href="https://html.design/">html design</a></p>
                </div>
            </div>
        </div><!-- end container -->
    </div><!-- end copyrights -->

    <?php if (($identity['show_scroll_to_top'] ?? '1') === '1'): ?>
    <a href="#" id="scroll-to-top" class="dmtop global-radius"><i class="fa fa-angle-up"></i></a>
    <?php endif; ?>

    <!-- CORE -->
    <script src="<?= base_url('assets/portal/js/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/portal/js/bootstrap.bundle.min.js') ?>"></script>
    <!-- TEMPLATE PLUGINS -->
    <script src="<?= base_url('assets/portal/js/animate.js') ?>"></script>
    <script src="<?= base_url('assets/portal/js/scroll.js') ?>"></script>
    <script src="<?= base_url('assets/portal/js/owl.carousel.js') ?>"></script>
    <script src="<?= base_url('assets/portal/js/isotope.min.js') ?>"></script>
    <script src="<?= base_url('assets/portal/js/images-loded.min.js') ?>"></script>
    <script src="<?= base_url('assets/portal/js/jquery.prettyPhoto.js') ?>"></script>
    <script src="<?= base_url('assets/portal/js/jquery.easing.1.3.js') ?>"></script>
    <script src="<?= base_url('assets/portal/js/parallaxie.js') ?>"></script>
    <script src="<?= base_url('assets/portal/js/headline.js') ?>"></script>
    <script src="<?= base_url('assets/portal/js/jqBootstrapValidation.js') ?>"></script>
    <script src="<?= base_url('assets/portal/js/contact_me.js') ?>"></script>
    <script src="<?= base_url('assets/portal/js/custom.js') ?>"></script>
    <script src="<?= base_url('assets/portal/js/jquery.vide.js') ?>"></script>
    <script src="<?= base_url('assets/portal/js/retina.js') ?>"></script>

</body>
</html>