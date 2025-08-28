<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * News Listing Shortcode handler.
 * Compatible with WP 4.9.8 / PHP 7.2.1.
 */
class NLS_Shortcode {

    /**
     * Register shortcode.
     */
    public static function init() {
        add_shortcode( 'news_listing', array( __CLASS__, 'render' ) );
    }

    /**
     * Render the shortcode.
     *
     * @param array $atts
     * @return string
     */
    public static function render( $atts ) {
        $defaults = array(
            'layout'        => 'grid',      // 'grid' | 'carousel'
            'category'      => '',          // comma-separated slugs
            'count'         => 9,           // max posts
            'category_icon' => 'true',      // 'true' | 'false'
            'tags_badges'   => 'true',      // 'true' | 'false'
        );

        $atts = shortcode_atts( $defaults, is_array( $atts ) ? $atts : array(), 'news_listing' );

        $layout        = strtolower( trim( (string) $atts['layout'] ) );
        if ( $layout !== 'grid' && $layout !== 'carousel' ) {
            $layout = 'grid';
        }

        $category_slugs = array();
        if ( is_string( $atts['category'] ) && strlen( $atts['category'] ) > 0 ) {
            $parts = explode( ',', $atts['category'] );
            foreach ( $parts as $slug ) {
                $s = sanitize_title( $slug );
                if ( $s !== '' ) {
                    $category_slugs[] = $s;
                }
            }
        }

        $count = absint( $atts['count'] );
        if ( $count <= 0 ) {
            $count = 9;
        }

        $category_icon = ( $atts['category_icon'] === true || $atts['category_icon'] === 'true' || $atts['category_icon'] === '1' );
        $tags_badges   = ( $atts['tags_badges'] === true || $atts['tags_badges'] === 'true' || $atts['tags_badges'] === '1' );

        // Build query args. When category slugs provided, OR them using category_name CSV (WP handles OR for CSV in category_name).
        $paged = 1;
        if ( get_query_var( 'paged' ) ) {
            $paged = (int) get_query_var( 'paged' );
        } elseif ( get_query_var( 'page' ) ) {
            $paged = (int) get_query_var( 'page' );
        }
        if ( $paged < 1 ) {
            $paged = 1;
        }

        $args = array(
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => $count,
            'ignore_sticky_posts' => true,
            'paged'               => ( 'grid' === $layout ? $paged : 1 ),
        );

        if ( ! empty( $category_slugs ) ) {
            $args['category_name'] = implode( ',', $category_slugs ); // OR across slugs.
        }

        $q = new WP_Query( $args );

        // Enqueue assets only when rendering.
        wp_enqueue_style( 'nls-styles', NLS_URL . 'assets/css/news-listing.css', array(), NLS_VERSION );
        wp_enqueue_script( 'nls-scripts', NLS_URL . 'assets/js/news-listing.js', array(), NLS_VERSION, true );

        ob_start();
        $container_classes = 'nlp-wrapper nlp-layout-' . esc_attr( $layout );
        echo '<div class="' . $container_classes . '" data-layout="' . esc_attr( $layout ) . '">';

        if ( 'carousel' === $layout ) {
            echo '<button class="nlp-nav nlp-nav--prev" type="button" aria-label="' . esc_attr__( 'Previous', 'news-listing' ) . '">&#10094;</button>';
            echo '<div class="nlp-carousel" tabindex="0">';
        } else {
            echo '<div class="nlp-grid">';
        }

        if ( $q->have_posts() ) {
            while ( $q->have_posts() ) {
                $q->the_post();
                $post_id = get_the_ID();

                // Thumbnail wrapper (3:2). Use fallback if no thumbnail.
                $thumb_html = '';
                if ( has_post_thumbnail( $post_id ) ) {
                    $thumb_html = get_the_post_thumbnail(
                        $post_id,
                        'large',
                        array(
                            'class'   => 'nlp-thumb__img',
                            'loading' => 'lazy',
                            'decoding'=> 'async',
                        )
                    );
                } else {
                    $thumb_html = '<span class="nlp-thumb__placeholder" aria-hidden="true"></span>';
                }

                // Tags badges (cap 3).
                $badges_html = '';
                if ( $tags_badges ) {
                    $post_tags = get_the_tags( $post_id );
                    if ( $post_tags && is_array( $post_tags ) ) {
                        $slice = array_slice( $post_tags, 0, 3 );
                        $badges_html .= '<div class="nlp-badges" aria-hidden="true">';
                        foreach ( $slice as $t ) {
                            $badges_html .= '<span class="nlp-badge">' . esc_html( $t->name ) . '</span>';
                        }
                        $badges_html .= '</div>';
                    }
                }

                // Category icons (soft ACF dep).
                $icons_html = '';
                if ( $category_icon ) {
                    $cats = get_the_category( $post_id );
                    if ( $cats ) {
                        $icons_html .= '<div class="nlp-icons">';
                        foreach ( $cats as $cat ) {
                            $url = '';
                            if ( function_exists( 'get_field' ) ) {
                                // ACF Term field key: category_icon (image URL).
                                $url = (string) get_field( 'category_icon', 'category_' . $cat->term_id );
                            }
                            if ( ! $url ) {
                                // Optional fallback to term meta if a URL is stored there.
                                $url = (string) get_term_meta( $cat->term_id, 'category_icon', true );
                            }
                            $url = esc_url( $url );
                            if ( $url ) {
                                $icons_html .= '<img class="nlp-icon" src="' . $url . '" alt="' . esc_attr( $cat->name ) . '" />';
                            }
                        }
                        $icons_html .= '</div>';
                    }
                }

                echo '<article class="nlp-item">';

                echo '<a class="nlp-thumb" href="' . esc_url( get_permalink( $post_id ) ) . '">';
                echo $thumb_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- from core helper already escaped.
                echo $badges_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- constructed with esc_html.
                echo '</a>';

                echo '<h3 class="nlp-title"><a href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html( get_the_title( $post_id ) ) . '</a></h3>';

                $excerpt = wp_trim_words( get_the_excerpt( $post_id ), 22, '&hellip;' );
                echo '<div class="nlp-excerpt">' . wp_kses_post( $excerpt ) . '</div>';

                echo $icons_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

                echo '</article>';
            }
            wp_reset_postdata();
        } else {
            echo '<p class="nlp-empty">' . esc_html__( 'No posts found.', 'news-listing' ) . '</p>';
        }

        echo '</div>'; // grid or carousel container.

        if ( 'carousel' === $layout ) {
            echo '<button class="nlp-nav nlp-nav--next" type="button" aria-label="' . esc_attr__( 'Next', 'news-listing' ) . '">&#10095;</button>';
        }

        // Pagination (grid only): placeholder to be improved in later tasks.
        if ( 'grid' === $layout && $q->max_num_pages > 1 ) {
            $big = 999999999; // need an unlikely integer.
            $links = paginate_links( array(
                'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                'format'    => '?paged=%#%',
                'current'   => max( 1, $paged ),
                'total'     => (int) $q->max_num_pages,
                'type'      => 'list',
                'prev_text' => '&#10094;',
                'next_text' => '&#10095;',
            ) );
            if ( $links ) {
                echo '<nav class="nlp-pagination" aria-label="' . esc_attr__( 'News pagination', 'news-listing' ) . '">';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links returns HTML.
                echo $links;
                echo '</nav>';
            }
        }

        echo '</div>'; // wrapper.

        return ob_get_clean();
    }
}
