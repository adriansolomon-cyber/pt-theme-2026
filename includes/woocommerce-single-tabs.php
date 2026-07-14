<div class="product_tabs">
    <div class="tabheaders">
        <div class="tab_header active" data-content="overview">Overview</div>
        <div class="tab_header" data-content="specifications">Specifications</div>
    </div>
  <div class="description-container">
    <div id="overview" class="tab_content">
        <div class="product_description">
        <?php the_content(); ?>
        </div>
            <div class="overview-content-wrapper">
                <?php if (get_field('description-toggle')) : ?>
                    <div class="parent loglap-windowed-garden-shed garden-shed">
                        <?php if (get_field('description-features-toggle')) : ?>
                        
                            <div class="features">
                                <!-- DESCRIPTION -->
                                <div class="text one">
                                    <p><?php the_field('description-features-description'); ?></p>
                                </div>
                                <?php $left = true; ?>
                                <?php while (have_rows('description-features-rows')) : the_row(); ?>
                                    <div class="feature <?php echo $left ? 'left' : 'right'; ?> ">
                                        <?php $title = get_sub_field('title'); ?>
                                        <?php $description = get_sub_field('description'); ?>
                                        <?php $image = get_sub_field('image'); ?>
                                        <?php $icon = get_sub_field('icon'); ?>
                                        <?php $iconClass = get_sub_field('icon-class'); ?>
                                        <?php $include_type = get_sub_field('include_type'); ?>
                                        <div class="line horizontal one"></div>
                                        <div class="image" style="background-image: url('<?php echo $image['url']; ?>');">
                                            <?php if ($icon || $iconClass) : ?>
                                                <i class="fa <?php echo strlen($icon) > 0 ? 'fa ' . $icon : ''; ?> <?php echo $iconClass ?? ''; ?>"></i>
                                            <?php endif; ?>
                                            <?php if ($include_type != 'None') { ?>
                                                <div class="included">
                                                    <?php
                                                    if ($include_type == 'Upgrade') {
                                                        $includeVal = $include_type;
                                                        $includeIcon = 'icons8-plus-math-filled';
                                                    } else if ($include_type == 'Optional') {
                                                        $includeVal = $include_type;
                                                        $includeIcon = 'icons8-plus-math-filled';
                                                    } else if ($include_type == 'Include') {
                                                        $includeVal = 'Included';
                                                        $includeIcon = 'icons8-checkmark-filled';
                                                    }
                                                    ?>
                                                    <div class="text"><?php echo $includeVal; ?></div>
                                                    <i class="<?php echo $includeIcon; ?>"></i>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <h3><?php echo $title; ?></h3>
                                        <p><?php echo $description; ?></p>
                                    </div>
                                    <?php $left = !$left; ?>
                                    <?php if ($left) : ?>
                                        <div class="clear-space"></div>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (get_field('description-shelf-toggle')) : ?>
                            <div class="step two">
                                <div class="circle">
                                    <h4>Shelf</h4>
                                </div>
                            </div>
                            <div class="roof">
                                <h3>A choice of shelf options for your preference and budget</h3>
                                <div class="fix">
                                    <?php while (have_rows('description-shelf-rows')) : the_row(); ?>
                                        <div class="column one-fourth">
                                            <?php $title = get_sub_field('title'); ?>
                                            <?php $image = get_sub_field('image'); ?>
                                            <?php $included = get_sub_field('included'); ?>
                                            <div class="image" style="background-image: url('<?php echo $image['url']; ?>');"></div>
                                            <?php if ($included) : ?>
                                                <div class="included">
                                                    <div class="text">Included</div>
                                                    <i class="icons8-checkmark-filled"></i>
                                                </div>
                                            <?php else : ?>
                                                <div class="upgrade">
                                                    <div class="text">Upgrade</div>
                                                    <div class="plus">+</div>
                                                </div>
                                            <?php endif; ?>
                                            <h3><?php echo $title; ?></h3>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (get_field('description-floor-toggle')) : ?>
                            <div class="step two">
                                <div class="circle">
                                    <h4>Floor</h4>
                                </div>
                            </div>
                            <div class="floor">
                                <?php $i = 0; ?>
                                <?php while (have_rows('description-floor-rows')) : the_row(); ?>
                                    <div class="column one-third <?php if ($i + 1 === count(get_field('description-floor-rows'))) echo 'last'; ?>">
                                        <?php $title = get_sub_field('title'); ?>
                                        <?php $image = get_sub_field('image'); ?>
                                        <?php $included = get_sub_field('included'); ?>
                                        <img src="<?php echo $image['url']; ?>" alt="" width="100%">
                                        <?php if ($included) : ?>
                                            <div class="included">
                                                <div class="text">Included</div>
                                                <i class="icons8-checkmark-filled"></i>
                                            </div>
                                        <?php else : ?>
                                            <div class="upgrade">
                                                <div class="text">Upgrade</div>
                                                <div class="plus">+</div>
                                            </div>
                                        <?php endif; ?>
                                        <h3><?php echo $title; ?></h3>
                                    </div>
                                    <?php $i++; ?>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (get_field('description-roof-cover-toggle')) : ?>
                            <div class="step four">
                                <div class="circle">
                                    <h4>Roof Cover</h4>
                                </div>
                            </div>
                            <div class="roof-cover">
                                <h3>A choice of shelf options for your preference and budget</h3>
                                <?php $i = 0; ?>
                                <?php while (have_rows('description-roof-cover-rows')) : the_row(); ?>
                                    <div class="column one-fourth <?php if ($i + 1 === count(get_field('description-roof-cover-rows'))) echo 'last'; ?>">
                                        <?php $title = get_sub_field('title'); ?>
                                        <?php $image = get_sub_field('image'); ?>
                                        <?php $included = get_sub_field('included'); ?>
                                        <div class="image" style="background-image: url('<?php echo $image['url']; ?>');"></div>
                                        <?php if ($included) : ?>
                                            <div class="included">
                                                <div class="text">Included</div>
                                                <i class="icons8-checkmark-filled"></i>
                                            </div>
                                        <?php else : ?>
                                            <div class="upgrade">
                                                <div class="text">Upgrade</div>
                                                <div class="plus">+</div>
                                            </div>
                                        <?php endif; ?>
                                        <h3><?php echo $title; ?></h3>
                                    </div>
                                    <?php $i++; ?>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
   
    <div id="specifications" class="tab_content" style="display:none;">
        <div class="select-size">
            <div class="text"><span>Select</span> Size</div>
            <div class="boxes">
                <?php
                    foreach($all_sizes_products as $size)
                    {    ?>
                        <div class="size_tab"><?php echo get_the_title($size)?></div>
                    <?php }
                    
                ?>
            </div>
        </div>
        <div class="select-measurements">
            <div class="text"><span>Select</span> Measurements</div>
            <div class="boxes">
                <div class="box active">Metric</div>
                <div class="box">Imperial</div>
            </div>
        </div>
        <div class="clear-space"></div>
        <div class="column one-half overall-dimensions">
            <div class="title">Overall Dimensions</div>
            <div class="divider one"></div>
            <div class="clear-space"></div>
            <div class="left">
                <div class="text">Overall Width</div>
                <div class="value">Value</div>
                <div class="text">Overall Depth</div>
                <div class="value">Value</div>
            </div>
            <div class="right">
                <div class="text">Width <span>Internal</span></div>
                <div class="value">Value</div>
                <div class="text">Depth <span>Internal</span></div>
                <div class="value">Value</div>
            </div>
        </div>
        <div class="column one-half last eaves-ridge">
            <div class="title">Eaves & Ridge</div>
            <div class="divider one"></div>
            <div class="clear-space"></div>
            <div class="left">
                <div class="text">Eaves Height <span>Inc. Floor</span></div>
                <div class="value">Value</div>
                <div class="clear-space"></div>
                <div class="text">Eaves Height <span>Excl. Floor</span></div>
                <div class="value">Value</div>
            </div>
            <div class="center">
                <div class="text">Ridge Height <span>Inc. Floor</span></div>
                <div class="value">Value</div>
                <div class="clear-space"></div>
                <div class="text">Ridge Height <span>Excl. Floor</span></div>
                <div class="value">Value</div>
            </div>
            <div class="right">
                <div class="text">Eaves Height <span>Internal</span></div>
                <div class="value">Value</div>
                <div class="clear-space"></div>
                <div class="text">Ridge Height <span>Internal</span></div>
                <div class="value">Value</div>
            </div>
        </div>
        <div class="clear-space"></div>
        <div class="column one-third doors">
            <div class="title">Doors</div>
            <div class="divider one"></div>
            <div class="clear-space"></div>
            <div class="left">
                <div class="text">Door Height</div>
                <div class="value">Value</div>
                <div class="text">Door Width</div>
                <div class="value">Value</div>
            </div>
            <div class="right">
                <div class="text">Door Opening Size <span>H x W</span></div>
                <div class="value">Value</div>
            </div>
        </div>
        <div class="column one-third fix windows">
            <div class="title">Windows</div>
            <div class="divider one"></div>
            <div class="clear-space"></div>
            <div class="left">
                <div class="text">Window Dimensions <span>W x H</span></div>
                <div class="value">Value</div>
            </div>
            <div class="right">
                <div class="text">Glazing Thickness</div>
                <div class="value">Value</div>
                <div class="text">Frame Thickness <span>H x W</span></div>
                <div class="value">Value</div>
            </div>
        </div>
        <div class="column one-third last floor-base">
            <div class="title">Floor & Base</div>
            <div class="divider one"></div>
            <div class="clear-space"></div>
            <div class="left">
                <div class="text">Overall Floor Size <span>W x D</span></div>
                <div class="value">Value</div>
            </div>
            <div class="right">
                <div class="text">Base Size</div>
                <div class="value">Value</div>
            </div>
        </div>
        <div class="clear-space"></div>
        <div class="column one-third edit materials">
            <div class="title">Materials</div>
            <div class="divider one"></div>
            <div class="clear-space"></div>
            <div class="full">
                <div class="text">Floor Material</div>
                <div class="value">Value</div>
                <div class="clear-space"></div>
                <div class="text">Material</div>
                <div class="value">Value</div>
                <div class="clear-space"></div>
                <div class="text">Roof Material</div>
                <div class="value">Value</div>
                <div class="clear-space"></div>
                <div class="text">Roof Covering Material</div>
                <div class="value">Value</div>
                <div class="clear-space"></div>
                <div class="text">Glazing Material</div>
                <div class="value">Value</div>
            </div>
        </div>
        <div class="column two-thirds last edit features">
            <div class="title">Features</div>
            <div class="divider one"></div>
            <div class="clear-space"></div>
            <div class="left">
                <div class="text">Windows</div>
                <div class="value">Value</div>
                <div class="clear-space"></div>
                <div class="text">Shed Type</div>
                <div class="value">Value</div>
                <div class="clear-space"></div>
                <div class="text">Project Timber Range</div>
                <div class="value">Value</div>
                <div class="clear-space"></div>
                <div class="text">Roof Style</div>
                <div class="value">Value</div>
                <div class="clear-space"></div>
                <div class="text">Fixtures & Fittings</div>
                <div class="value">Value</div>
                <?php if ($post->ID == '86288' || $post->ID == '101281') { ?>
                    <div class="clear-space"></div>
                    <div class="text">U-Values of Metal Roof</div>
                    <div class="value">Value</div>
                <?php } ?>
            </div>
            <div class="right">
                <div class="text">Cladding Style</div>
                <div class="value">Value</div>
                <div class="clear-space"></div>
                <div class="text">Locking System</div>
                <div class="value">Value</div>
                <div class="clear-space"></div>
                <div class="text">Interchangeable Windows</div>
                <div class="value">Value</div>
                <div class="clear-space"></div>
                <div class="text">Basecoat Treatment</div>
                <div class="value">Value</div>
                <div class="clear-space"></div>
                <div class="text">Pre-Assembled Side Panels</div>
                <div class="value">Value</div>
                <?php if ($post->ID == '86288' || $post->ID == '101281') { ?>
                    <div class="clear-space"></div>
                    <div class="text">U-Values of Walls and Floor</div>
                    <div class="value">Value</div>
                <?php } ?>
            </div>
        </div>
     
    </div>
</div>