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
     * Return default shortcode attributes.
     *
     * @return array
     */
    public static function get_default_atts() {
        return array(
            'layout'        => 'grid',      // 'grid' | 'carousel'
            'category'      => '',          // comma-separated slugs
            'count'         => 9,           // max posts
            'visible_posts' => '',          // carousel: number of visible cards (optional; defaults to count)
            'category_icon' => 'true',      // 'true' | 'false'
            'tags_badges'   => 'true',      // 'true' | 'false'
        );
    }

    /**
     * Normalize layout value.
     *
     * @param mixed $layout
     * @return string 'grid' or 'carousel'
     */
    private static function normalize_layout( $layout ) {
        $value = strtolower( trim( (string) $layout ) );
        if ( $value !== 'grid' && $value !== 'carousel' ) {
            return 'grid';
        }
        return $value;
    }

    /**
     * Parse comma-separated category slugs and sanitize each.
     *
     * @param mixed $csv
     * @return array
     */
    public static function parse_category_slugs( $csv ) {
        $category_slugs = array();
        if ( is_string( $csv ) && strlen( $csv ) > 0 ) {
            $parts = explode( ',', $csv );
            foreach ( $parts as $slug ) {
                $sanitized = sanitize_title( $slug );
                if ( $sanitized !== '' ) {
                    $category_slugs[] = $sanitized;
                }
            }
        }
        return $category_slugs;
    }

    /**
     * Normalize the count attribute to a positive integer, fallback to default (9).
     *
     * @param mixed $value
     * @return int
     */
    public static function normalize_count( $value ) {
        $count = absint( $value );
        if ( $count <= 0 ) {
            $count = 9;
        }
        return $count;
    }

    /**
     * Normalize common truthy string/boolean values to boolean.
     *
     * @param mixed $value
     * @return bool
     */
    public static function normalize_boolean( $value ) {
        return ( $value === true || $value === 'true' || $value === '1' || $value === 1 );
    }

    /**
     * Resolve the current paged value from query vars.
     * Grid uses pagination; carousel does not.
     *
     * @return int
     */
    public static function get_paged() {
        $paged = 1;
        if ( get_query_var( 'paged' ) ) {
            $paged = (int) get_query_var( 'paged' );
        } elseif ( get_query_var( 'page' ) ) {
            $paged = (int) get_query_var( 'page' );
        }
        if ( $paged < 1 ) {
            $paged = 1;
        }
        return $paged;
    }

    /**
     * Build WP_Query arguments based on inputs.
     *
     * @param string $layout
     * @param array  $category_slugs
     * @param int    $count
     * @param int    $paged
     * @return array
     */
    public static function build_query_args( $layout, $category_slugs, $count, $paged ) {
        $args = array(
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => (int) $count,
            'ignore_sticky_posts' => true,
            'paged'               => ( 'grid' === $layout ? max( 1, (int) $paged ) : 1 ),
        );

        if ( ! empty( $category_slugs ) ) {
            // OR across slugs via category_name CSV.
            $args['category_name'] = implode( ',', $category_slugs );
        }

        return $args;
    }

    /**
     * Render the shortcode.
     *
     * @param array $atts
     * @return string
     */
    public static function render( $atts ) {
        $defaults = self::get_default_atts();
        $atts = shortcode_atts( $defaults, is_array( $atts ) ? $atts : array(), 'news_listing' );

        $layout         = self::normalize_layout( $atts['layout'] );
        $category_slugs = self::parse_category_slugs( $atts['category'] );
        $count          = self::normalize_count( $atts['count'] );
        $visible_posts  = isset( $atts['visible_posts'] ) && $atts['visible_posts'] !== '' ? self::normalize_count( $atts['visible_posts'] ) : $count;
        $category_icon  = self::normalize_boolean( $atts['category_icon'] );
        $tags_badges    = self::normalize_boolean( $atts['tags_badges'] );

        // Build query args. When category slugs provided, OR them using category_name CSV (WP handles OR for CSV in category_name).
        $paged = self::get_paged();
        $args  = self::build_query_args( $layout, $category_slugs, $count, $paged );

        $q = new WP_Query( $args );

        // Enqueue assets only when rendering.
        wp_enqueue_style( 'nls-styles', NLS_URL . 'assets/css/news-listing.css', array(), NLS_VERSION );
        wp_enqueue_script( 'nls-scripts', NLS_URL . 'assets/js/news-listing.js', array(), NLS_VERSION, true );

        ob_start();
        $container_classes = 'nlp-wrapper nlp-layout-' . esc_attr( $layout );
        $data_count = ( 'carousel' === $layout ) ? (int) $visible_posts : (int) $count;
        echo '<div class="' . $container_classes . '" data-layout="' . esc_attr( $layout ) . '" data-count="' . esc_attr( $data_count ) . '">';

        if ( 'carousel' === $layout ) {
            echo '<div class="nlp-nav nlp-nav--prev" role="button" tabindex="0" aria-label="' . esc_attr__( 'Previous', 'news-listing' ) . '">'
                . '<svg width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
                . '<path fill="currentColor" d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>'
                . '</div>';
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
            echo '<div class="nlp-nav nlp-nav--next" role="button" tabindex="0" aria-label="' . esc_attr__( 'Next', 'news-listing' ) . '">'
                . '<svg width="1em" height="1em" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
                . '<path fill="currentColor" d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6z"/></svg>'
                . '</div>';
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
