<?php
if ( ! function_exists( 'build_leaky_paywall_default_restriction_row' ) ) :
    function build_leaky_paywall_default_restriction_row($restriction = array(), $row_key = '')
    {


        $settings = get_leaky_paywall_settings();

        if (empty($restriction)) {
            $restriction = array(
                'post_type' 	=> '',
                'taxonomy'	=> '',
                'allowed_value' => '0',
            );
        }

        if (!isset($restriction['taxonomy'])) {
            $restriction['taxonomy'] = 'all';
        }

        // $return  = '<div class="issuem-leaky-paywall-restriction-row">';
        echo '<tr class="issuem-leaky-paywall-restriction-row">';
        $hidden_post_types = array( 'revision', 'nav_menu_item', 'lp_transaction', 'custom_css');
        $post_types = get_post_types(array('public' => true), 'objects');
        // $return .= '<label for="restriction-post-type-' . $row_key . '">' . __( 'Number of', 'leaky-paywall' ) . '</label> ';
        echo '<td><select class="leaky-paywall-restriction-post-type" id="restriction-post-type-' . $row_key . '" name="restrictions[post_types][' . $row_key . '][post_type]">';
        foreach ($post_types as $post_type) {

            if (in_array($post_type->name, $hidden_post_types)) {
                continue;
            }

            echo '<option value="' . $post_type->name . '" ' . selected($post_type->name, $restriction['post_type'], false) . '>' . $post_type->labels->name . '</option>';
        }

        echo '</select></td>';

        // get taxonomies for this post type
        echo '<td><select style="width: 100%;" name="restrictions[post_types][' . $row_key . '][taxonomy]">';
        $tax_post_type = $restriction['post_type'] ? $restriction['post_type'] : 'post';
        $taxes = get_object_taxonomies($tax_post_type, 'objects');
        $hidden_taxes = apply_filters('leaky_paywall_settings_hidden_taxonomies', array('post_format', 'yst_prominent_words'));

        echo '<option value="all" ' . selected('all', $restriction['taxonomy'], false) . '>All</option>';

        foreach ($taxes as $tax) {

            if (in_array($tax->name, $hidden_taxes)) {
                continue;
            }

            // create option group for this taxonomy
            echo '<optgroup label="' . $tax->label . '">';

            // create options for this taxonomy
            $terms = get_terms(array(
                'taxonomy' => $tax->name,
                'hide_empty'	=> false
            ));

            foreach ($terms as $term) {
                echo '<option value="' . $term->term_id . '" ' . selected($term->term_id, $restriction['taxonomy'], false) . '>' . $term->name . '</option>';
            }

            echo '</optgroup>';
        }
        echo '</select></td>';

        echo '<td>';

        if ('on' == $settings['enable_combined_restrictions']) {

            echo '<p class="allowed-number-helper-text" style="color: #555; font-size: 12px;">Using combined restrictions.</p>';
            echo '<input style="display: none;" id="restriction-allowed-' . $row_key . '" type="number" class="small-text restriction-allowed-number-setting" name="restrictions[post_types][' . $row_key . '][allowed_value]" value="' . $restriction['allowed_value'] . '" />';
        } else {
            echo '<p class="allowed-number-helper-text" style="color: #555; font-size: 12px; display: none;">Using combined restrictions.</p>';
            echo '<input id="restriction-allowed-' . $row_key . '" type="number" class="small-text restriction-allowed-number-setting" name="restrictions[post_types][' . $row_key . '][allowed_value]" value="' . $restriction['allowed_value'] . '" />';
        }

        echo '</td>';

        echo '<td><span class="delete-x delete-restriction-row">&times;</span></td>';

        echo '</tr>';
    }
endif;
