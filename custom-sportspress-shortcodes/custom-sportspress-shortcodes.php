<?php
/*
Plugin Name: Custom SportsPress Shortcodes
Description: Add custom shortcodes for SportsPress functionality.
Version: 1.0
Author: Edgardo Martinez
*/

add_theme_support( 'sportspress' );

/**
 * Shortcode to display player nationality as an image
 *
 * @param array $atts Shortcode attributes
 * @param string $content Shortcode content
 * @param string $tag Shortcode tag
 * @return string Nationality image HTML
 */


// Function to add player-nationality to body class
function add_player_class_to_body($classes) {
    global $post;

    // Check if the current post type is a player or staff
    if (is_singular(array('sp_player', 'sp_staff'))) {
        // Get post ID from the current post
        $post_id = $post->ID;

        // Make sure post ID is provided
        if (!$post_id) {
            return $classes;
        }

        // Instantiate the appropriate class instance
        $class_instance = (get_post_type($post_id) === 'sp_player') ? new SP_Player($post_id) : new SP_Staff($post_id);

        // Get country name from nationalities array
        $nationalities = $class_instance->nationalities();
        $country_name = $nationalities[0] ?? '';

        // Convert country name to lowercase and replace spaces with hyphens
        $country_slug = strtolower(str_replace(' ', '-', $country_name));

        // Add the country slug as a class to the body
        if (!empty($country_slug)) {
            $classes[] = 'player-' . $country_slug;
        }
    }

    return $classes;
}
add_filter('body_class', 'add_player_class_to_body');



// Change title tag for players and staffs
function custom_title_for_specific_post_types($title_parts) {
    // Check if it's a single post of specific post types
    if (is_singular(array('sp_player', 'sp_staff'))) {
        global $post;

        // Get the custom fields short_first_name and short_last_name
        $short_first_name = get_post_meta($post->ID, 'short_first_name', true);
        $short_last_name = get_post_meta($post->ID, 'short_last_name', true);

        // Construct the custom title
        $custom_title = '';

        if ($short_first_name) {
            $custom_title .= $short_first_name;
        }

        if ($short_last_name) {
            // Add space if first name exists
            if ($short_first_name) {
                $custom_title .= ' ';
            }
            $custom_title .= $short_last_name;
        }

        // Update the title parts with the custom title
        $title_parts['title'] = $custom_title;
    }

    return $title_parts;
}
add_filter('document_title_parts', 'custom_title_for_specific_post_types');




 // Shortcode for nationality flag
function sp_player_country_image_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts);

    // Get player ID from shortcode attribute
    $player_id = get_the_ID();
    $player = new SP_Player( $player_id );

    // Make sure player ID is provided
    if (!$player_id) {
        return 'Player ID is required';
    }

    // Get country name from nationalities array
    $nationalities = $player->nationalities();
    $country_name = $nationalities[0];

    // Convert country name to lowercase and replace spaces with hyphens
    $country_slug = strtolower(str_replace(' ', '-', $country_name));

    // Image source URL
    $image_src = plugins_url('images/flags-hexa/' . $country_slug . '.png', __FILE__);

    // Check if the image file exists
    $image_path = plugin_dir_path(__FILE__) . 'images/flags-hexa/' . $country_slug . '.png';
    if (file_exists($image_path)) {
        // Generate HTML image tag
        $image_tag = '<img src="' . esc_url($image_src) . '" alt="' . esc_attr($country_name) . '">';
        return '<div class="icon-bg-hexagon"></div>' . $image_tag;
    } else {
        return 'Image not found for ' . $country_name;
    }
}
add_shortcode('sp_player_country_image', 'sp_player_country_image_shortcode');



 // Shortcode for customized player profile
function sp_player_profile_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts);

    // Get player ID from shortcode attribute
    $player_id = get_the_ID();
    $player = new SP_Player( $player_id );

    // Make sure player ID is provided
    if (!$player_id) {
        return 'Player ID is required';
    }

    // Get player data
    $full_name = get_the_title();
    $date_of_birth = get_field('date_of_birth');
    $place_of_birth = get_field('place_of_birth');
    $is_alive = get_field('is_alive');
    $international_titles = get_field('international_titles');
    $individual_honours = get_field('individual_honours');
    $age = '';

    //Start container
    $profile = "<div class='sp_player_profile flex_row'>";
    if ($international_titles || $individual_honours) {
        $profile .= "<div class='sp_player_profile_data col_9'>";
    } else {
        $profile .= "<div class='sp_player_profile_data col_12'>";
    }

    $profile .= "<h6>Full Name</h6><h5>" . $full_name . "</h5>";

    if (is_singular('sp_player')) {
        $positions = $player->positions();
        $position_names = array();
        foreach ($positions as $position) {
            $position_names[] = $position->name;
        }
        // Convert the array of position names to a comma-separated string
        $positions_string = implode(', ', $position_names);
        $profile .= "<h6>Position</h6><h5>" . $positions_string . "</h5>";
    }

    // Format date of birth
    if ($date_of_birth) {
        list($day, $month, $year) = explode('/', $date_of_birth);

        // Create a DateTime object from the date components
        $date_of_birth_obj = DateTime::createFromFormat('d/m/Y', $date_of_birth);
    
        // Check if DateTime object creation was successful
        if ($date_of_birth_obj !== false) {
            // Format the date if DateTime object creation was successful
            $formatted_dob = $date_of_birth_obj->format('F j, Y');
        } else {
            // Debugging: Output any errors encountered during DateTime object creation
            $errors = DateTime::getLastErrors();
            var_dump($errors);
        }
    }

    if ($date_of_birth) {
        $profile .= "<h6>Date of Birth</h6><h5>" . $formatted_dob . "</h5>";
    }

    if ($place_of_birth) {
        $profile .= "<h6>Place of Birth</h6><h5>" . $place_of_birth . "</h5>";
    }

    $date_of_birth_obj = DateTime::createFromFormat('d/m/Y', $date_of_birth);
    $today = new DateTime();

    if ($is_alive === 'yes' && $date_of_birth) {
        $age = $date_of_birth_obj->diff($today)->y;
        $profile .= "<h6>Age</h6><h5>" . $age . "</h5>";
    } else if ($is_alive === 'no') {
        $date_of_death = get_field('date_of_death');
        $place_of_death = get_field('place_of_death');

        if ($date_of_death) {
            // Format date of death
            list($day, $month, $year) = explode('/', $date_of_death);

            // Create a DateTime object from the date components
            $date_of_death_obj = DateTime::createFromFormat('d/m/Y', $date_of_death);

            // Check if DateTime object creation was successful
            if ($date_of_death_obj !== false) {
                // Format the date if DateTime object creation was successful
                $formatted_dod = $date_of_death_obj->format('F j, Y');
            } else {
                // Debugging: Output any errors encountered during DateTime object creation
                $errors = DateTime::getLastErrors();
                var_dump($errors);
            }

            $aged = $date_of_birth_obj->diff($date_of_death_obj)->y;
            $profile .= "<h6>Date of Death</h6><h5>" . $formatted_dod . " (aged " . $aged . ")</h5>";
        }
        if ($place_of_death) {
            $profile .= "<h6>Place of Death</h6><h5>" . $place_of_death . "</h5>";
        }
    }

    if ($international_titles || $individual_honours) {
        $profile .= "</div><div class='sp_player_profile_honours col_3'>";
        if ($international_titles) {
            $profile .= "<h6>International Titles</h6>";
            foreach ($international_titles as $international_title) {
                $profile .= "<h5>". $international_title["team"] . "</h5>";
                $profile .= "<div class='sp_player_profile_titles'>";
                $titles = $international_title["titles"];
                foreach ($titles as $title) {
                    $trophy = $title["trophy_image"];

                    $profile .= <<<HTML
                    <div class='sp_player_profile_title'>
                        <div class='sp_player_profile_title_hexagons'>
                            <div class='sp_player_profile_title_trophy'>
                                <img src='{$trophy["url"]}' alt='{$trophy["alt"]}' loading='lazy' width='60' height='auto'>
                            </div>
                            <div class='icon-bg-hexagon'>
                                <div class='sp_player_profile_title_number'>
                                    {$title['number_of_titles']}
                                </div>
                            </div>
                        </div>
                        <div class='sp_player_profile_title_text'>
                            <h6>{$title['tournament']}</h6>
                            <p>{$title['years']}</p>
                        </div>
                    </div>
                    HTML;
                }
                $profile .= "</div>";
            }
        }

        if ($individual_honours) {
            $profile .= "<h6>Individual Honours</h6>" . $individual_honours;
        }
    }

    //Close container
    $profile .= "</div></div>";

    return $profile;
}
add_shortcode('sp_player_profile', 'sp_player_profile_shortcode');



// Shortcode for player stats
function sp_player_statistics_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts);

    // Get player ID from shortcode attribute
    $player_id = get_the_ID();

    // Get player object
    $player = new SP_Player($player_id);
    $positions = $player->positions();
    $position_slug = array();
    foreach ($positions as $position) {
        $position_slug[] = $position->slug;
    }

    // Make sure player ID is provided
    if (!$player_id || !$player) {
        return 'Player ID is required';
    }

    // Get player statistics
    $statistics = $player->statistics();
    // Format the statistics data as needed
    $output = '<div class="player_stats">';

    // Get career total table
    $ligas = array_filter( (array) get_the_terms( $player_id, 'sp_league' ) );
    $section_order = array( -1 => null );
    if ( is_array( $ligas ) ) {
        foreach ( $section_order as $section_id => $section_label ) {
            $data = array(
                'data' => $player->data( 0, false, $section_id ),
            );
            $totalHeaderRow = '';
            $totalDataRows = '';

            $output .= '<h3>International Career Totals</h3>';
            foreach ($data['data'] as $key => $stat) {
                $columnOrder = array('name', 'team', 'appearances', 'wins', 'draws', 'losses', 'goals', 'goalspergame', 'minutespergoal', 'assists', 'minutesplayed', 'started', 'substitutein', 'substituteout', 'onthebench', 'yellowcards', 'redcards', 'owngoals');
                // Check if the player's positions include 'goalkeeper'
                $isGoalkeeper = false;
                $gkGoals = 0;
                $gkAssists = 0;
                $keysToSkip = array('goals', 'goalspergame', 'minutespergoal');

                if (in_array('goalkeeper', $position_slug)) {
                    $isGoalkeeper = true;
                    // Add goalkeeper-specific column keys after 'losses' and before 'goals'
                    array_splice($columnOrder, array_search('losses', $columnOrder) + 1, 0, array('goalsreceived', 'goalsreceivedpergame', 'cleansheets'));
                    if ($key === -1) {
                        $goalsTemp = $stat['goals'];
                        $gkGoals = (int)$goalsTemp;
                        $assistsTemp = $stat['assists'];
                        $gkAssists = (int)$assistsTemp;
                    }
                }

                if ($key === 0) {
                    // Generate header row for career totals
                    $totalHeaderRow .= '<tr>';
                    foreach ($columnOrder as $headerKey) {
                        if ( 'team' == $headerKey ) {
                            continue;
                        }
                        if ($isGoalkeeper && $gkGoals === 0 && in_array($headerKey, $keysToSkip)) {
                            continue;
                        }
                        if ( $isGoalkeeper && $gkAssists === 0 && 'assists' === $headerKey ) {
                            continue;
                        }
                        $headerValue = ($headerKey === 'name') ? 'Stats' : $stat[$headerKey];
                        $totalHeaderRow .= '<th class="cell-' . $headerKey . '">' . $headerValue . '</th>';
                    }
                    $totalHeaderRow .= '</tr>';
                } elseif ($key === -1) {
                    // Generate data rows for career totals
                    $totalDataRows .= '<tr>';
                    foreach ($columnOrder as $bodyKey) {
                        if ( 'team' == $bodyKey ) {
                            continue;
                        }
                        if ($isGoalkeeper && $gkGoals === 0 && in_array($bodyKey, $keysToSkip)) {
                            continue;
                        }
                        if ( $isGoalkeeper && $gkAssists === 0 && 'assists' === $bodyKey ) {
                            continue;
                        }
                        $totalDataRows .= '<td class="player_stats-total_cell cell-'. $bodyKey .'">' . $stat[$bodyKey] . '</td>';
                    }
                }
                $totalDataRows .= '</tr>';
            }
        }
        $output .= '<div class="table-scroll"><table class="player_stats-table player_stats-total-table">';
        $output .= $totalHeaderRow;
        $output .= $totalDataRows;
        $output .= '</table></div>';
    }

    $parent_league_ids = array(31, 32);

    $output .= '<h3>Detailed International Career Stats</h3>';
    $output .= '<div class="player_stats-accordion accordion">';

    foreach ($parent_league_ids as $parent_id) {
        $parent_term = get_term($parent_id);
        $parent_name = $parent_term ? $parent_term->name : 'Unknown Tournament';
        
        // Get statistics for the parent league (if available)
        $parent_statistics = isset($statistics[$parent_id]) ? $statistics[$parent_id] : array();
        
        // Get child leagues of the parent league
        $child_leagues = get_terms(array(
            'taxonomy' => 'sp_league',
            'parent' => $parent_id,
            'hide_empty' => false,
        ));
        // Check if there are child statistics available for the current parent league
        $has_child_statistics = false;
        foreach ($child_leagues as $child_league) {
            if (isset($statistics[$child_league->term_id]) && !empty($statistics[$child_league->term_id])) {
                $has_child_statistics = true;
                break;
            }
        }

        if ($has_child_statistics) {
            $output .= '<input type="checkbox" id="accordion' . $parent_id . '" name="accordion">';
            $output .= '<label for="accordion' . $parent_id . '">' . $parent_name . '</label>';
            $output .= '<div class="accordion-content">';
        
            $parentHeaderRow = '';
            $parentDataRows = '';
            $parentTotalRows = '';
            $headerRow = '';
            $dataRows = '';
            $totalRows = '';

            $columnOrder = array('name', 'team', 'appearances', 'wins', 'draws', 'losses', 'goals', 'goalspergame', 'minutespergoal', 'assists', 'minutesplayed', 'started', 'substitutein', 'substituteout', 'onthebench', 'yellowcards', 'redcards', 'owngoals');
            $isGoalkeeper = false;
            $gkGoals = 0;
            $gkAssists = 0;
            $keysToSkip = array('goals', 'goalspergame', 'minutespergoal');

            if (in_array('goalkeeper', $position_slug)) {
                $isGoalkeeper = true;
                // Add goalkeeper-specific column keys after 'losses' and before 'goals'
                array_splice($columnOrder, array_search('losses', $columnOrder) + 1, 0, array('goalsreceived', 'goalsreceivedpergame', 'cleansheets'));
                if ($key === -1) {
                    $goalsTemp = $stat['goals'];
                    $gkGoals = (int)$goalsTemp;
                    $assistsTemp = $stat['assists'];
                    $gkAssists = (int)$assistsTemp;
                }
            }

            // Generate HTML code for the table of the parent league stats
            if (isset($statistics[$parent_id])) {
                if (!empty($parent_statistics)) {
                    foreach ($parent_statistics as $key => $stat) {
                        if ($key === 0) {
                            $parentHeaderRow .= '<tr>';
                            foreach ($columnOrder as $headerKey) {
                                if ($isGoalkeeper && $gkGoals === 0 && in_array($headerKey, $keysToSkip)) {
                                    continue;
                                }
                                if ( $isGoalkeeper && $gkAssists === 0 && 'assists' === $headerKey ) {
                                    continue;
                                }
                                $parentHeaderRow .= '<th class="cell-'. $headerKey .'">' . $stat[$headerKey] . '</th>';
                            }
                            $parentHeaderRow .= '</tr>';
                        }
                        if ($key > 0) {
                            $parentDataRows .= '<tr>';
                            foreach ($columnOrder as $bodyKey) {
                                if ($isGoalkeeper && $gkGoals === 0 && in_array($bodyKey, $keysToSkip)) {
                                    continue;
                                }
                                if ( $isGoalkeeper && $gkAssists === 0 && 'assists' === $bodyKey ) {
                                    continue;
                                }
                                $parentDataRows .= '<td class="player_stats-value_cell cell-'. $bodyKey .'">' . $stat[$bodyKey] . '</td>';
                            }
                            $parentDataRows .= '</tr>';
                        }
                        if ($key === -1) {
                            $parentTotalRows .= '<tr>';
                            foreach ($columnOrder as $totalKey) {
                                if ($isGoalkeeper && $gkGoals === 0 && in_array($totalKey, $keysToSkip)) {
                                    continue;
                                }
                                if ( $isGoalkeeper && $gkAssists === 0 && 'assists' === $totalKey ) {
                                    continue;
                                }
                                $parentTotalRows .= '<td class="player_stats-total_cell cell-'. $totalKey .'">' . $stat[$totalKey] . '</td>';
                            }
                            $parentTotalRows .= '</tr>';
                        }
                    }
                    $output .= '<h4>Totals for ' . $parent_name . '</h4>';
                    $output .= '<div class="table-scroll"><table class="player_stats-table">';
                    $output .= $parentHeaderRow;
                    $output .= $parentDataRows;
                    $output .= $parentTotalRows;
                    $output .= '</table></div>';
                }
            }

            foreach ($child_leagues as $child_league) {
                // Get the statistics for the child league
                $child_statistics = isset($statistics[$child_league->term_id]) ? $statistics[$child_league->term_id] : array();
                
                if (!empty($child_statistics)) {
                    $leagueName = $child_league ? $child_league->name : 'Unknown Tournament';
                    $output .= '<h4>' . $leagueName . '</h4>'; 

                    // Generate HTML code for the table
                    foreach ($child_statistics as $key => $stat) {
                        if ($key === 0) {
                            $headerRow .= '<tr>';
                            foreach ($columnOrder as $headerKey) {
                                if ($isGoalkeeper && $gkGoals === 0 && in_array($headerKey, $keysToSkip)) {
                                    continue;
                                }
                                if ( $isGoalkeeper && $gkAssists === 0 && 'assists' === $headerKey ) {
                                    continue;
                                }
                                $headerRow .= '<th class="cell-'. $headerKey .'">' . $stat[$headerKey] . '</th>';
                            }
                            $headerRow .= '</tr>';
                        }
                        if ($key > 0) {
                            $dataRows .= '<tr>';
                            foreach ($columnOrder as $bodyKey) {
                                if ($isGoalkeeper && $gkGoals === 0 && in_array($bodyKey, $keysToSkip)) {
                                    continue;
                                }
                                if ( $isGoalkeeper && $gkAssists === 0 && 'assists' === $bodyKey ) {
                                    continue;
                                }
                                if ('team' === $bodyKey) {
                                    // Get the ID of the team
                                }
                                $dataRows .= '<td class="player_stats-value_cell cell-'. $bodyKey .'">' . $stat[$bodyKey] . '</td>';
                            }
                            $dataRows .= '</tr>';
                        }
                        if ($key === -1) {
                            $totalRows .= '<tr>';
                            foreach ($columnOrder as $totalKey) {
                                if ($isGoalkeeper && $gkGoals === 0 && in_array($totalKey, $keysToSkip)) {
                                    continue;
                                }
                                if ( $isGoalkeeper && $gkAssists === 0 && 'assists' === $totalKey ) {
                                    continue;
                                }
                                $totalRows .= '<td class="player_stats-total_cell cell-'. $totalKey .'">' . $stat[$totalKey] . '</td>';
                            }
                            $totalRows .= '</tr>';
                        }
                    }
                    $output .= '<div class="table-scroll"><table class="player_stats-table">';
                    $output .= $headerRow;
                    $output .= $dataRows;
                    $output .= $totalRows;
                    $output .= '</table></div>';
                }
            }
            $output .= '</div>';
        }
    }

    $output .= '</div></div>';

    return $output;
}
add_shortcode('sp_player_statistics', 'sp_player_statistics_shortcode');



// Shortcode for player matches
function sp_event_list_shortcode($atts) {
    $current_post_id = get_the_ID();
    $player_id = '';
    $staff_id = '';
    $team_id = '';
    // Get the current post type
    $current_post_type = get_post_type($current_post_id);
    if ($current_post_type === "sp_player") {
        $player_id = $current_post_id;
    }
    if ($current_post_type === "sp_staff") {
        $staff_id = $current_post_id;
    }
    if ($current_post_type === "sp_team") {
        $team_id = $current_post_id;
    }

    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'team' => $team_id, // Team ID or slug
        'player' => $player_id, // Player ID
        'league' => '', // League ID or slug
        'season' => '', // Season ID or slug
        'venue' => '', // Venue ID or slug
        'date' => '', // Date (YYYY-MM-DD format)
        'staff' => $staff_id, // Staff ID
        'order' => 'date', // Order by: date, time, title, or default (event date and time)
        'order_by' => 'DESC', // Sorting order: ASC or DESC
        'limit' => -1, // Number of events to display. Use -1 for unlimited.
    ), $atts);

    // Prepare query arguments
    $args = array(
        'post_type' => 'sp_event',
        'posts_per_page' => $atts['limit'],
        'orderby' => $atts['order'],
        'order' => $atts['order_by'],
    );

    // Filter by team
    if (!empty($atts['team'])) {
        $args['meta_query'][] = array(
            'key' => 'sp_team',
            'value' => $atts['team'],
        );
    }

    // Filter by player
    if (!empty($atts['player'])) {
        $args['meta_query'][] = array(
            'key' => 'sp_player',
            'value' => $atts['player'],
        );
    }

    // Filter by league
    if (!empty($atts['league'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'sp_league',
            'field' => is_numeric($atts['league']) ? 'term_id' : 'slug',
            'terms' => $atts['league'],
        );
    }

    // Filter by season
    if (!empty($atts['season'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'sp_season',
            'field' => is_numeric($atts['season']) ? 'term_id' : 'slug',
            'terms' => $atts['season'],
        );
    }

    // Filter by venue
    if (!empty($atts['venue'])) {
        $args['meta_query'][] = array(
            'key' => 'sp_venue',
            'value' => $atts['venue'],
        );
    }

    // Filter by date
    if (!empty($atts['date'])) {
        $args['meta_query'][] = array(
            'key' => 'sp_date',
            'value' => $atts['date'],
            'compare' => '=',
        );
    }

    // Filter by staff
    if (!empty($atts['staff'])) {
        $args['meta_query'][] = array(
            'key' => 'sp_staff',
            'value' => $atts['staff'],
            'compare' => '=',
        );
    }

    $icons = array(
        'goals' => '<i class="sp-icon-soccerball" title="Goal" style="color:#222222 !important"></i>',
        'assists' => '<i class="sp-icon-shoe" title="Assist" style="color:#222222 !important"></i>',
        'yellowcards' => '<i class="sp-icon-card" title="Card" style="color:#f4d014  !important"></i>',
        'redcards' => '<i class="sp-icon-card" title="Card" style="color:#d4000f !important"></i>',
        'owngoals' => '<i class="sp-icon-soccerball" title="Card" style="color:#d4000f !important"></i>',
        'goalsreceived' => '<img decoding="async" width="20" height="20" src="' . plugins_url('custom-sportspress-shortcodes/images/icon-goal.png') . '" alt="Goals Received icon" title="Goal Received">'
    );

    // Query events
    $events_query = new WP_Query($args);

    // Start output buffer
    ob_start();

    // Display events
    if ($events_query->have_posts()) {
        echo '<div class="paginated-table"><div class="table-scroll">';
        echo '<table class="player_stats-table" id="last-matches-table">';
        echo '<tr class="table-header"><th>Date</th><th>Tournament</th><th>Match</th><th>Result</th>';
        if ($current_post_type === "sp_player") {
            echo '<th>Performance</th>';
        }
        echo '</tr>';
        while ($events_query->have_posts()) {
            $events_query->the_post();
            $match_id = get_the_id();
            $match_date = get_the_date();
            $leagues = get_the_terms($match_id, 'sp_league');
            $teams = get_post_meta($match_id, 'sp_team');
            $results = get_post_meta($match_id, 'sp_results');
            $performance = get_post_meta($match_id, 'sp_players');
            echo '<tr>';
            echo '<td><a href="' . get_permalink() . '">' . $match_date . '</a></td>';
            echo '<td>';
            foreach ($leagues as $leagueList) {
                if ($leagueList->term_id === 31 || $leagueList->term_id === 32) {
                    continue;
                }
                echo $leagueList->name;
            }
            echo '</td><td>';
            if (!empty($teams)) {
                $team_info = array();
                foreach ($teams as $team_id) {
                    $team_name = get_the_title($team_id);
                    $team_logo = '';
            
                    // Check if the team has a logo
                    if (has_post_thumbnail($team_id)) {
                        $team_logo = get_the_post_thumbnail($team_id, 'sportspress-fit-mini');
                    }
            
                    // Add team name and logo to team info array
                    $team_info[] = array(
                        'name' => $team_name,
                        'logo' => $team_logo,
                    );
                }
            
                // Generate HTML for team names and logos
                $team_output = '<div class="teams-cells">';
                $team_output .= '<div class="team-badge">' . $team_info[0]['logo'] . '</div>';
                $team_output .= '<div class="team-name">' . esc_html($team_info[0]['name']) . '</div>';
                $team_output .= '<div class="team-name">-</div>';
                $team_output .= '<div class="team-name">' . esc_html($team_info[1]['name']) . '</div>';
                $team_output .= '<div class="team-badge">' . $team_info[1]['logo'] . '</div>';
                $team_output .= '</div>';
            
                // Add team names and logos to the output
                echo $team_output;
            }
            echo '</td><td>';
            if (!empty($results)) {
                $goals_combined = ''; // Initialize an empty string to store combined goals
                
                foreach ($results[0] as $team_result) {
                    // Concatenate the goals values with a dash
                    $goals_combined .= $team_result['goals'] . '-';
                }
                
                // Remove the trailing dash
                $goals_combined = rtrim($goals_combined, '-');
                
                echo '<a href="' . get_permalink() . '">' . $goals_combined . '</a>'; // Output the combined goals
            }
            echo '</td>';
            if ($current_post_type === "sp_player") {
            echo '<td>';
                foreach ($performance as $team_performances) {
                    foreach ($team_performances as $team_performance) {
                    // Check if the player's performance data exists in the team's data
                        if (isset($team_performance[$player_id])) {
                            // Get the player's performance data
                            $player_performance = $team_performance[$player_id];
                            // Iterate through each performance metric
                            foreach ($player_performance as $key => $value) {
                                // Check if the performance metric is not empty and it's not the player's ID
                                if ($key !== 'number' && $key !== 'position' && $key !== 'status' && $key !== 'sub') {
                                    if ($value !== 0 || $value !== '') {
                                    // Generate icons based on the number of occurrences
                                        $icon = str_repeat($icons[$key], intval($value));
                                        echo $icon;
                                    }
                                }
                            }
                        }
                    }
                }
                echo '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
        echo '</div></div>';
        wp_reset_postdata();
    } else {
        echo '<h4>No international matches found</h4>';
    }

    // End output buffer and return content
    $output = ob_get_clean();
    return $output;
}
add_shortcode('sp_event_list', 'sp_event_list_shortcode');



function sp_player_stats_against_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'type' => 'national', // Default to 'national' if type not provided. Club is the other option.
        ),
        $atts
    );

    // Extract attributes
    $player_id = isset($atts['player_id']) ? intval($atts['player_id']) : get_the_ID();
    $type = $atts['type'];

    global $wpdb;

    // Prepare the SQL query to retrieve matches where the player participated
    $query = $wpdb->prepare(
        "
        SELECT pm.post_id
        FROM {$wpdb->postmeta} AS pm
        INNER JOIN {$wpdb->term_relationships} AS tr ON pm.post_id = tr.object_id
        INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
        WHERE pm.meta_key = 'sp_players'
        AND pm.meta_value LIKE %s
        AND t.term_id = %d
        AND tt.taxonomy = 'sp_league'
        ",
        '%' . $player_id . '%',
        ($type === 'national' ? 31 : 32)
    );

    // Execute the query to get match IDs
    $match_ids = $wpdb->get_col($query);

    // Initialize arrays to store player's team and opponents
    $player_team_ids = array();
    $opponent_team_ids = array();

    // Iterate through match IDs to extract teams
    foreach ($match_ids as $match_id) {
        // Get teams for the current match
        $teams = get_post_meta($match_id, 'sp_team', false);

        // Get players' details for the current match
        $players = get_post_meta($match_id, 'sp_players', true);

        // Iterate through players' details to identify the player's team and opponents
        foreach ($players as $team_id => $player_details) {
            // Check if the player's ID exists in the player details array
            if (isset($player_details[$player_id])) {
                // Player's team
                $player_team_ids[] = $team_id;
            } else {
                // Opponent team
                $opponent_team_ids[] = $team_id;
            }
        }
    }

    // Remove duplicate team IDs
    $player_team_ids = array_unique($player_team_ids);
    $opponent_team_ids = array_unique($opponent_team_ids);

    // Start building the output
    /*$output = '<ul>';

    // Display player's team
    $output .= '<li><strong>Player\'s Team:</strong></li>';
    foreach ($player_team_ids as $team_id) {
        $team = get_post($team_id);
        if ($team) {
            $output .= '<li>' . esc_html($team->post_title) . '</li>';
        }
    }

    // Display opponent teams
    $output .= '<li><strong>Opponent Teams:</strong></li>';
    foreach ($opponent_team_ids as $opponent_team_id) {
        $team = get_post($opponent_team_id);
        if ($team) {
            $output .= '<li>' . esc_html($team->post_title) . '</li>';
        }
    }

    $output .= '</ul>';*/
    
    $player = new SP_Player($player_id);
    $positions = $player->positions();
    $position_slug = array();
    $isGoalkeeper = false;
    foreach ($positions as $position) {
        $position_slug[] = $position->slug;
    }
    if (in_array('goalkeeper', $position_slug)) {
        $isGoalkeeper = true;
    }
    // Start building the table if there are teams available
    if (!$opponent_team_ids) {
        // No matches found message
        return "<h4>No international matches found for " . ($type === 'national' ? 'national team' : 'club') . " tournaments.</h4>";
    }

    $output = '<div class="table-scroll"><table>';
    $firstIteration = true;

    // Iterate over each opponent team ID
    foreach ($opponent_team_ids as $opponent_team_id) {
        // Get the data of the opponent team
        $opponent_team_name = get_the_title($opponent_team_id);
        $opponent_team_link = get_post_permalink( $opponent_team_id );
        $opponent_team_logo = '';
		$opponent_team_name = '<a href="' . $opponent_team_link . '"><div class="team-name">' . esc_html($opponent_team_name) . '</div></a>';
        if ( has_post_thumbnail( $opponent_team_id ) ) {
            $opponent_team_logo = get_the_post_thumbnail( $opponent_team_id, 'sportspress-fit-mini' );
            $opponent_team_name = '<a href="' . $opponent_team_link . '"><div class="team-badge">' . $opponent_team_logo . '</div><div class="team-name">' . $opponent_team_name . '</div></a>';
        }

        $query = $wpdb->prepare(
            "
            SELECT post_id
            FROM {$wpdb->postmeta}
            WHERE meta_key = 'sp_players'
            AND meta_value LIKE %s
            AND post_id IN (
                SELECT post_id
                FROM {$wpdb->postmeta}
                WHERE meta_key = 'sp_team'
                AND meta_value = %d
            )
            ",
            '%' . $player_id . '%',
            $opponent_team_id
        );

        $match_ids = $wpdb->get_col($query);

        $matches = array(); 

        // Initialize variables to store player's stats against the opponent team
        $stats = array(
            'wins' => 0,
            'draws' => 0,
            'losses' => 0,
            'goals' => 0,
            'assists' => 0,
            'goalsreceived' => 0,
            // Add more stats as needed
        );

        // Iterate through match IDs to calculate player's stats against the opponent team
        foreach ($match_ids as $match_id) {
            // Get players' details for the current match
            $teams = get_post_meta($match_id, 'sp_team', false);
            $leagues = get_the_terms($match_id, 'sp_league');

            if ($leagues && (($type === 'national' && in_array(31, wp_list_pluck($leagues, 'term_id'))) || ($type === 'club' && in_array(32, wp_list_pluck($leagues, 'term_id'))))) {
                $players = get_post_meta($match_id, 'sp_players', true);
                $outcomes = get_post_meta($match_id, 'sp_results', true);

                // Check if the player played against the opponent team in the current match
                foreach ($players as $team_id => $player_details) {
                    // Check if the player's ID exists in the player details array
                    if (isset($player_details[$player_id])) {
                        // If the player played against the opponent team, update the stats
                        $stats['goals'] += intval($player_details[$player_id]['goals']);
                        $stats['assists'] += intval($player_details[$player_id]['assists']);
                        $stats['goalsreceived'] += intval($player_details[$player_id]['goalsreceived']);
                        // Update other stats as needed// Check the outcome for the team in which the player played
                        $team_outcome = $outcomes[$team_id]['outcome'][0];
                        
                        // Update stats based on the outcome
                        switch ($team_outcome) {
                            case 'win':
                                $stats['wins']++;
                                break;
                            case 'draw':
                                $stats['draws']++;
                                break;
                            case 'loss':
                                $stats['losses']++;
                                break;
                        }
                    }
                }
            }
        }

        if ($firstIteration) {        
            $output .= '<tr>';
            $output .= '<th>Rivals</th>';
            $output .= '<th>Matches</th>';
            $output .= '<th>Wins</th>';
            $output .= '<th>Draws</th>';
            $output .= '<th>Losses</th>';
            if ($isGoalkeeper) {
                $output .= '<th>Goals Received</th>';
            }
            if (!$isGoalkeeper || ($isGoalkeeper && $stats['goals'] > 0)) {
                $output .= '<th>Goals</th>';
            }
            if (!$isGoalkeeper || ($isGoalkeeper && $stats['assists'] > 0)) {
                $output .= '<th>Assists</th>';
            }
            $output .= '</tr>';
            $firstIteration = false;
        }

        $attendance = $stats['wins'] + $stats['draws'] + $stats['losses'];

        // Generate the table row for the opponent team's stats
        $output .= '<tr>';
        $output .= '<td class="teams-cells">' . $opponent_team_name . '</td>';
        $output .= '<td>' . $attendance . '</td>';
        $output .= '<td>' . $stats['wins'] . '</td>';
        $output .= '<td>' . $stats['draws'] . '</td>';
        $output .= '<td>' . $stats['losses'] . '</td>';
        if ($isGoalkeeper) {
            $output .= '<td>' . $stats['goalsreceived'] . '</td>';
        }
        if (!$isGoalkeeper || ($isGoalkeeper && $stats['goals'] > 0)) {
            $output .= '<td>' . $stats['goals'] . '</td>';
        }
        if (!$isGoalkeeper || ($isGoalkeeper && $stats['assists'] > 0)) {
            $output .= '<td>' . $stats['assists'] . '</td>';
        }
        // Add more columns for other stats
        $output .= '</tr>';
    }

    $output .= '</table></div>';

    return $output;
}
add_shortcode('sp_player_stats_against', 'sp_player_stats_against_shortcode');



function sp_staff_stats_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'staff_id' => get_the_ID(),
            'type' => 'national', // Default to 'national'. Options: 'national' or 'club'
        ),
        $atts
    );

    // Extract attributes
    $staff_id = intval($atts['staff_id']);
    $type = $atts['type'];

    global $wpdb;

    // Initialize variables to store staff stats
    $stats = array();

    // Prepare the SQL query to retrieve matches where the staff participated
    $query = $wpdb->prepare(
        "
        SELECT pm.post_id
        FROM {$wpdb->postmeta} AS pm
        INNER JOIN {$wpdb->term_relationships} AS tr ON pm.post_id = tr.object_id
        INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
        WHERE pm.meta_key = 'sp_staff'
        AND pm.meta_value = %s
        AND tt.taxonomy = 'sp_league'
        AND t.term_id = %d
        ",
        $staff_id,
        ($type === 'national' ? 31 : 32)
    );
    $match_ids = $wpdb->get_col($query);

    if (empty($match_ids)) {
        return "<h4>No international $type matches managed.</h4>";
    }

    $total_wins = 0;
    $total_draws = 0;
    $total_losses = 0;
    $total_results = 0;
    $team_ids = array();

    // Iterate through matches to calculate staff stats
    foreach ($match_ids as $match_id) {
        $staff_array = get_post_meta($match_id, 'sp_staff', false);
        $position = array_search($staff_id, $staff_array);
        $teams = get_post_meta($match_id, 'sp_team', false);
        
        // Get the team ID associated with the staff based on position and store it
        $team_id = 0;
        if ($position === 1) {
            $team_id = $teams[0];
        } elseif ($position === 3) {
            $team_id = $teams[1];
        }
        $team_ids[] = $team_id;

        // Fetch the sp_results array from post meta
        $results = get_post_meta($match_id, 'sp_results', true);

        // Check if the team ID exists in the results array
        if (isset($results[$team_id])) {
            // Get the outcome of the match for the team associated with the staff
            $outcome = $results[$team_id]['outcome'][0] ?? '';
            $total_results++;
            switch ($outcome) {
                case 'win':
                    $total_wins++;
                    break;
                case 'draw':
                    $total_draws++;
                    break;
                case 'loss':
                    $total_losses++;
                    break;
            }        

            $team_stats_array = array();
            
            // Iterate through array of teams to create stats per team
            foreach ($team_ids as $team) {
                // Initialize stats for the current team
                $team_stats = array(
                    'team_name' => get_the_title($team), // Get team name
                    'wins' => 0,
                    'draws' => 0,
                    'losses' => 0,
                    'total_matches' => 0
                );

                $query = $wpdb->prepare(
                    "
                    SELECT pm.post_id
                    FROM {$wpdb->postmeta} AS pm
                    INNER JOIN {$wpdb->term_relationships} AS tr ON pm.post_id = tr.object_id
                    INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                    INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
                    WHERE pm.meta_key = 'sp_staff'
                    AND pm.meta_value = %s
                    AND tt.taxonomy = 'sp_league'
                    AND t.term_id = %d
                    AND pm.post_id IN (
                        SELECT post_id
                        FROM {$wpdb->postmeta}
                        WHERE meta_key = 'sp_team'
                        AND meta_value = %d
                    )
                    ",
                    $staff_id,
                    ($type === 'national' ? 31 : 32),
                    $team
                );
                $team_matches_ids = $wpdb->get_col($query);

                // Process the retrieved matches as needed
                foreach ($team_matches_ids as $team_matches_id) {
                    $int_staff_array = get_post_meta($team_matches_id, 'sp_staff', false);
                    $int_position = array_search($staff_id, $int_staff_array);
                    $int_teams = get_post_meta($match_id, 'sp_team', false);


                    if ((($int_position === 1) && ($int_teams[0] === $team)) || (($int_position === 3) && ($int_teams[1] === $team))) {
                        $int_results = get_post_meta($team_matches_id, 'sp_results', true);
                        if (isset($int_results[$team])) {
                            // Get the outcome of the match for the team associated with the staff
                            $int_outcome = $int_results[$team]['outcome'][0] ?? '';
                            $team_stats['total_matches']++;
                            switch ($int_outcome) {
                                case 'win':
                                    $team_stats['wins']++;
                                    break;
                                case 'draw':
                                    $team_stats['draws']++;
                                    break;
                                case 'loss':
                                    $team_stats['losses']++;    
                                    break;
                            }
                        }
                    }
                }
                $team_stats_array[$team] = $team_stats;
            }
        }
    }
    $total_row = '<tr>';
    $total_row .= '<td class="player_stats-total_cell">Total</td>';
    $total_row .= '<td class="player_stats-total_cell">' . $total_results . '</td>';
    $total_row .= '<td class="player_stats-total_cell">' . $total_wins . '</td>';
    $total_row .= '<td class="player_stats-total_cell">' . $total_draws . '</td>';
    $total_row .= '<td class="player_stats-total_cell">' . $total_losses . '</td>';
    $total_row .= '</tr>';

    // Start building the output
    $output = '<div class="table-scroll"><table>';
    $output .= '<tr>';
    $output .= '<th>Team</th>';
    $output .= '<th>Matches</th>';
    $output .= '<th>Wins</th>';
    $output .= '<th>Draws</th>';
    $output .= '<th>Losses</th>';
    $output .= '</tr>';

    // Display stats
    foreach ($team_stats_array as $team_id => $team_stats) {
        $team_name = get_the_title( $team_id );
        $team_link = get_post_permalink( $team_id );
        $team_logo = '';
		$team_name = '<a href="' . $team_link . '"><div class="team-name">' . esc_html($team_name) . '</div></a>';
        if ( has_post_thumbnail( $team_id ) ) {
            $team_logo = get_the_post_thumbnail( $team_id, 'sportspress-fit-mini' );
            $team_name = '<a href="' . $team_link . '"><div class="team-badge">' . $team_logo . '</div><div class="team-name">' . $team_name . '</div></a>';
        }

        $output .= '<tr>';
        $output .= '<td class="teams-cells">' . $team_name . '</td>';
        $output .= '<td>' . $team_stats['total_matches'] . '</td>';
        $output .= '<td>' . $team_stats['wins'] . '</td>';
        $output .= '<td>' . $team_stats['draws'] . '</td>';
        $output .= '<td>' . $team_stats['losses'] . '</td>';
        $output .= '</tr>';
    }

    $output .= $total_row;
    $output .= '</table></div>';

    return $output;
}
add_shortcode('sp_staff_stats', 'sp_staff_stats_shortcode');



// Shortcode for head to heads
function sp_past_meetings_shortcode( $atts ) {
    global $wpdb;

    // Extract shortcode attributes
    $atts = shortcode_atts( array(
        'type' => '', // Team type: national, club
    ), $atts );

    // Prepare query args to retrieve teams
    $args = array(
        'post_type' => 'sp_team',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => 'publish',
        'meta_query' => array(),
    );

    // Filter teams by type if provided
    if ( ! empty( $atts['type'] ) ) {
        $args['meta_query'][] = array(
            'key' => 'team_type',
            'value' => $atts['type'],
            'compare' => '=',
        );
    }

    // Get teams
    $teams_query = new WP_Query( $args );

    // If no teams are found, return empty
    if ( $teams_query->have_posts() ) {
        // Build select options for teams
        $team_options = '';
        while ( $teams_query->have_posts() ) {
            $teams_query->the_post();
            $team_id = get_the_ID();
            $team_name = get_the_title();
            $team_options .= '<option value="' . esc_attr( $team_id ) . '">' . esc_html( $team_name ) . '</option>';
        }
        wp_reset_postdata();

        // Return the form with select elements
        $output = '<form method="get" class="head-to-head-form">';
        $output .= '<div class="head-to-head-form-team">';
        $output .= '<label for="team1">Team 1:</label>';
        $output .= '<select name="team1" id="team1">' . $team_options . '</select>';
        $output .= '</div><div class="head-to-head-form-team">';
        $output .= '<label for="team2">Team 2:</label>';
        $output .= '<select name="team2" id="team2">' . $team_options . '</select>';
        $output .= '</div><div class="head-to-head-form-submit">';
        $output .= '<input type="submit" value="Search">';
        $output .= '</div>';
        $output .= '</form>';

        // If both teams are the same, display a warning
        if ( isset( $_GET['team1'] ) && isset( $_GET['team2'] ) && $_GET['team1'] === $_GET['team2'] ) {
            $output .= '<p>Please select different teams for Team 1 and Team 2.</p>';
        }

        // If both teams are selected, execute the query and display results
        if ( isset( $_GET['team1'] ) && isset( $_GET['team2'] ) && $_GET['team1'] !== $_GET['team2'] ) {
            $team1_id = intval( $_GET['team1'] );
            $team2_id = intval( $_GET['team2'] );
            $team1_abbr = get_post_meta($team1_id, 'sp_abbreviation', true);
            $team2_abbr = get_post_meta($team2_id, 'sp_abbreviation', true);

            // Prepare the SQL query to select matches involving both teams
            $query = $wpdb->prepare(
                "
                SELECT p.*
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
                INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
                WHERE p.post_type = 'sp_event'
                AND p.post_status = 'publish'
                AND pm1.meta_key = 'sp_team'
                AND pm1.meta_value = %d
                AND pm2.meta_key = 'sp_team'
                AND pm2.meta_value = %d
                ORDER BY p.post_date DESC
                ",
                $team1_id,
                $team2_id
            );

            // Execute the query
            $results = $wpdb->get_results( $query );

            // If no results are found, display a message
            if ( empty( $results ) ) {
                $output .= '<p>No past meetings found between the selected teams.</p>';
            } else {
                // Display the results
                $team1_wins = $team1_draws = $team1_losses = $team1_goals = 0;
                $team2_wins = $team2_draws = $team2_losses = $team2_goals = 0;

                // Iterate through results to calculate totals
                foreach ( $results as $result ) {
                    $match_id = $result->ID;
                    $scores = get_post_meta($match_id, 'sp_results');
                    foreach ($scores as $score) {
                        foreach ($score as $team_id => $match_data) {
                            // Extract relevant data for easier access
                            $outcome = $match_data['outcome'][0];
                            $goals = intval($match_data['goals']);
                    
                            // Determine which team's data this is
                            if ($team_id == $team1_id) {
                                // Update team 1's stats
                                if ($outcome === 'win') {
                                    $team1_wins++;
                                } elseif ($outcome === 'draw') {
                                    $team1_draws++;
                                } elseif ($outcome === 'loss') {
                                    $team1_losses++;
                                }
                                $team1_goals += $goals;
                            } elseif ($team_id == $team2_id) {
                                // Update team 2's stats
                                if ($outcome === 'win') {
                                    $team2_wins++;
                                } elseif ($outcome === 'draw') {
                                    $team2_draws++;
                                } elseif ($outcome === 'loss') {
                                    $team2_losses++;
                                }
                                $team2_goals += $goals;
                            }
                        }
                    }
                }

                // Display the comparison in a table
                $output .= '<table class="comparison-table">';
                $output .= '<tr><th>' . esc_html($team1_abbr) . '</th><th></th><th>' . esc_html($team2_abbr) . '</th></tr>';
                $output .= '<tr><td>' . $team1_wins . '</td><td>Wins</td><td>' . $team2_wins . '</td></tr>';
                $output .= '<tr><td>' . $team1_draws . '</td><td>Draws</td><td>' . $team2_draws . '</td></tr>';
                $output .= '<tr><td>' . $team1_losses . '</td><td>Losses</td><td>' . $team2_losses . '</td></tr>';
                $output .= '<tr><td>' . $team1_goals . '</td><td>Goals</td><td>' . $team2_goals . '</td></tr>';
                $output .= '</table>';

                // Display the results
                $output .= '<div class="table-scroll"><table>';
                $output .= '<tr><th>Date</th><th>Tournament</th><th>Match</th><th>Score</th><th>Location</th></tr>';
                foreach ( $results as $result ) {
                    // Retrieve additional information for each match
                    $match_id = $result->ID;
                    $date = $result->post_date;
                    $leagues = get_the_terms($match_id, 'sp_league');
                    $venues = get_the_terms($match_id, 'sp_venue');
                    $teams = get_post_meta($match_id, 'sp_team');
                    $scores = get_post_meta($match_id, 'sp_results');

                    // Format the date
                    $formatted_date = date( 'F j, Y', strtotime( $date ) );

                    // Output match information
                    $output .= '<tr>';
                    $output .= '<td><a href="' . get_permalink() . '">' . esc_html( $formatted_date ) . '</a></td>';
                    foreach ($leagues as $leagueList) {
                        if ($leagueList->term_id === 31 || $leagueList->term_id === 32) {
                            continue;
                        }
                        $output .= '<td>' . $leagueList->name . '</a></td>';
                    }
                    if (!empty($teams)) {
                        $team_info = array();
                        foreach ($teams as $team_id) {
                            $team_name = get_the_title($team_id);
                            $team_logo = '';
                    
                            // Check if the team has a logo
                            if (has_post_thumbnail($team_id)) {
                                $team_logo = get_the_post_thumbnail($team_id, 'sportspress-fit-mini');
                            }
                    
                            // Add team name and logo to team info array
                            $team_info[] = array(
                                'name' => $team_name,
                                'logo' => $team_logo,
                            );
                        }
                    
                        // Generate HTML for team names and logos
                        $team_output = '<div class="teams-cells">';
                        $team_output .= '<div class="team-badge">' . $team_info[0]['logo'] . '</div>';
                        $team_output .= '<div class="team-name">' . esc_html($team_info[0]['name']) . '</div>';
                        $team_output .= '<div class="team-name">-</div>';
                        $team_output .= '<div class="team-name">' . esc_html($team_info[1]['name']) . '</div>';
                        $team_output .= '<div class="team-badge">' . $team_info[1]['logo'] . '</div>';
                        $team_output .= '</div>';
                    
                        // Add team names and logos to the output
                        $output .= '<td>' . $team_output . '</td>';
                    }
                    if (!empty($scores)) {
                        $goals_combined = ''; // Initialize an empty string to store combined goals
                        
                        foreach ($scores[0] as $team_result) {
                            // Concatenate the goals values with a dash
                            $goals_combined .= $team_result['goals'] . '-';
                        }
                        
                        // Remove the trailing dash
                        $goals_combined = rtrim($goals_combined, '-');
                        
                        $output .= '<td><a href="' . get_permalink() . '">' . $goals_combined . '</a></td>'; // Output the combined goals
                    }
                    // Output venue (ground)
                    if ( ! empty( $venues ) ) {
                        $venue_hierarchy = array(); // Array to store venue hierarchy
                        // Retrieve parent terms for the venue and add them to the hierarchy array
                        foreach ( $venues as $venue ) {
                            $parent_terms = get_ancestors( $venue->term_id, 'sp_venue' );
                            foreach ( $parent_terms as $parent_id ) {
                                $parent_term = get_term( $parent_id, 'sp_venue' );
                                $venue_hierarchy[] = $parent_term->name;
                            }
                        }
                        // Output parent venue names separated by comma
                        $output .= '<td>' . implode( ', ', $venue_hierarchy ) . '</td>';
                    } else {
                        $output .= '<td></td>'; // Output empty cell if no venue is found
                    }
                    $output .= '</tr>';
                }
                $output .= '</table></div>';
            }
        }
    } else {
        $output = 'No teams found.';
    }

    return $output;
}
add_shortcode( 'sp_past_meetings', 'sp_past_meetings_shortcode' );



// Add inline CSS styles for sp_team posts with custom link color
function add_inline_css_for_sp_team() {
    // Check if the current post is of type sp_team
    if ( is_singular( 'sp_team' ) ) {
        // Get the link color value from ACF
        $link_color = get_field( 'sp_colors', get_the_ID() )['link'];

        // Output inline CSS to set the --link-color variable
        echo '<style>:root { --link-color: ' . $link_color . '; }</style>';
    }
}
add_action( 'wp_head', 'add_inline_css_for_sp_team' );



// Shortcode for team title
function sp_team_title_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts);

    // Get player ID from shortcode attribute
    $team_id = get_the_ID();
    $team = new SP_team( $team_id );

    // Make sure team ID is provided
    if (!$team_id) {
        return 'Team ID is required';
    }
    
    $team_name = get_the_title();
    $team_logo = get_the_post_thumbnail($team_id, 'sportspress-fit-icon');
    $team_flag = get_field('hexagon_flag')['url'];
    $second_logo = get_field('second_logo');
    $primary_color = get_field('sp_colors')['primary'];
    $background_color = get_field('sp_colors')['background'];
    $heading_color = get_field('sp_colors')['heading'];
    $text_color = get_field('sp_colors')['text'];
    $team_type = get_field('team_type');
    $csv_file = '';

    if ($team_type === 'club') {
        $team_name = get_field('abbreviated_long_name');
    }

    $second = '';
    if (isset($second_logo) && !empty($second_logo)) {
        $second_logo = $second_logo['url'];
        $second = <<<HTML
        <div class="sp_team-second_logo">
            <div class='icon-bg-hexagon' style='color:{$background_color}'></div>
            <div class='icon-bg-hexagon hexagon-2' style='color:{$primary_color}'></div>
            <div class='sp_team-second_logo_img'>
                <img src='{$second_logo}' loading='lazy'>
            </div>
        </div>
        HTML;
    }

    $profile = <<<HTML
    <div class='sp_team-title'>
        <div class='sp_team-images'>
            <div class="sp_team-logo">
                <div class='icon-bg-hexagon' style='color:{$background_color}'></div>
                <div class='icon-bg-hexagon hexagon-2' style='color:{$primary_color}'></div>
                <div class='sp_team-logo_img'>
                    {$team_logo}
                </div>
            </div>
            {$second}
            <div class="sp_team-flag">
                <div class='icon-bg-hexagon' style='color:{$background_color}'></div>
                <img src='{$team_flag}' loading='lazy'>
            </div>
        </div>
        <div class='sp_team-name'>
            <div class='sp_team-name_container' style='background:{$primary_color}; border-bottom-color:{$heading_color}!important;'>
                <h1 style='color: {$text_color};'>{$team_name}</h1>
            </div>
        </div>
    </div>
    HTML;

    return $profile;
}
add_shortcode('sp_team_title', 'sp_team_title_shortcode');



// Shortcode for team title
function sp_team_profile_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts);

    // Get player ID from shortcode attribute
    $team_id = get_the_ID();
    $team = new SP_team( $team_id );

    // Make sure team ID is provided
    if (!$team_id) {
        return 'Team ID is required';
    }
    
    $long_name = get_field('long_name_or_federation_name');
    $foundation_date = get_field('foundation_date');
    $confederation = get_field('confederation');
    $team_type = get_field('team_type');
    $csv_file = '';
    $name_title = 'Federation Name';
    $grounds = get_the_terms( get_the_ID(), 'sp_venue' );
    $located = '';
    $home_kit = '';
    $away_kit = '';
    $third_kit = '';
    $fourth_kit = '';
    $primary_color = '';
    $background_color = '';
    $heading_color = '';
    $text_color = '';
    $titles = get_field('international_titles');

    if (isset(get_field('home_kit')['url'])) {
        $home_kit = '<img src="' . get_field('home_kit')['url'] . '" class="home-kit" height="150" width="85">';
    }
    if (isset(get_field('away_kit')['url'])) {
        $away_kit = '<img src="' . get_field('away_kit')['url'] . '" class="away-kit" height="150" width="85">';
    }
    if (isset(get_field('third_kit')['url'])) {
        $third_kit = '<img src="' . get_field('third_kit')['url'] . '" class="third-kit" height="150" width="85">';
    }
    if (isset(get_field('fourth_kit')['url'])) {
        $fourth_kit = '<img src="' . get_field('fourth_kit')['url'] . '" class="fourth-kit" height="150" width="85">';
    }

    if (isset(get_field('sp_colors')['primary'])) {
        $primary_color = get_field('sp_colors')['primary'];
    }
    if (isset(get_field('sp_colors')['background'])) {
        $background_color = get_field('sp_colors')['background'];
    }
    if (isset(get_field('sp_colors')['heading'])) {
        $heading_color = get_field('sp_colors')['heading'];
    }
    if (isset(get_field('sp_colors')['text'])) {
        $text_color = get_field('sp_colors')['text'];
    }

    $country = get_field('country');
    $city = get_field('city');
    $located = '';
    if (isset($city) && !empty($city)) {
        if (isset($city) && !empty($city)) {
            $located = <<<HTML
            <h6>City</h6>
            <h5>{$city}, {$country}</h5>
            HTML;
        } else {
            $located = <<<HTML
            <h6>City</h6>
            <h5>{$city}</h5>
            HTML;
        }
    }

    if ($team_type === 'club') {
        $csv_file = plugin_dir_path( __FILE__ ) . 'csv/club.csv';
        $name_title = 'Full Name';
    } else {
        $csv_file = plugin_dir_path( __FILE__ ) . 'csv/national.csv';
    }

    // Check if there are any grounds associated with the team
    if ( $grounds && ! is_wp_error( $grounds ) ) {
        // Loop through each ground
        foreach ( $grounds as $ground ) {
            // Get the name of the ground
            $ground_name = $ground->name;
        }
    }

    $stadium = '';
    if (!empty($ground_name)) {
        $stadium = <<<HTML
        <h6>Home Ground</h6>
        <h5>{$ground_name}</h5>
        HTML;
    }
    
    // Format date of foundation
    if ($foundation_date) {
        list($day, $month, $year) = explode('/', $foundation_date);

        // Create a DateTime object from the date components
        $foundation_date_obj = DateTime::createFromFormat('d/m/Y', $foundation_date);
    
        // Check if DateTime object creation was successful
        if ($foundation_date_obj !== false) {
            // Format the date if DateTime object creation was successful
            $formatted_dof = $foundation_date_obj->format('F j, Y');
        } else {
            // Debugging: Output any errors encountered during DateTime object creation
            $errors = DateTime::getLastErrors();
            var_dump($errors);
        }
    }
    
    $foundation_date_obj = DateTime::createFromFormat('d/m/Y', $foundation_date);
    $today = new DateTime();
    if ($foundation_date_obj !== false) {
        $age = $foundation_date_obj->diff($today)->y;
    }

    // Check if the file exists
    if ( file_exists( $csv_file ) ) {
        // Read the CSV file into an array
        $csv_data = array_map( 'str_getcsv', file( $csv_file ) );

        // Separate the header row from the data rows
        $header = array_shift( $csv_data );
        $id_index = array_search( 'ID', $header );

        if ( $id_index !== false ) {
            // Loop through each data row
            foreach ( $csv_data as $row ) {
                // Check if the value in the "ID" column matches the team ID
                if ( isset( $row[ $id_index ] ) && $row[ $id_index ] == $team_id ) {
                    // Extract values for position, points, best position, and last time in best position
                    $position = isset( $row[0] ) ? $row[0] : '';
                    $points = isset( $row[5] ) ? $row[5] : '';
                    $best_position = isset( $row[7] ) ? $row[7] : '';
                    $last_time_best_position = isset( $row[8] ) ? $row[8] : '';
                    $timestamp = DateTime::createFromFormat('d/m/Y', $last_time_best_position);
                    $last_time_best = $timestamp->format('F Y');
                }
            }
        } else {
            // Handle the case where the "ID" column is not found in the header row
            echo 'ID column not found in the CSV file.';
        }
    } else {
        // Handle the case where the file doesn't exist
        echo 'CSV file not found.';
    }

    $ranking_color = '#111111';
    switch ($position) :
        case 1:
            $ranking_color = 'goldenrod';
            break;
        case 2:
            $ranking_color = 'Silver';
            break;
        case 3:
            $ranking_color = '#CD7F32';
            break;
    endswitch;
    
    $ranking_size = 72;
    if ($position > 99 && $position < 1000) {
        $ranking_size = 50;
    } elseif ($position > 999) {
        $ranking_size = 38;
    }

    $international_titles = '';
    if (isset($titles) && !empty($titles)) {
        $international_titles = '<h6>International Titles</h6>';
        $international_titles .= '<div class="sp_team-titles">';
        foreach ($titles as $title) {
            $international_titles .= <<<HTML
            <div class="sp_team-title_box">
                <img src="{$title['trophy']['url']}" class="trophy-img">
                <div class="icon-bg-hexagon">
                    <div class="sp_team-title_number">
                    {$title['titles']}
                    </div>
                </div>
                <div class="sp_team-title_name">
                    <h5>{$title['tournament']}</h5>
                </div>
                <div class="sp_team-title_years">
                    {$title['years']}
                </div>
            </div>
            HTML;
        }
        $international_titles .= '</div>';
    }

    $profile = <<<HTML
    <div class="sp_team_profile flex_row">
        <div class="sp_team_profile_text col_9">
            <h6>{$name_title}</h6>
            <h5>{$long_name}</h5>
            <h6>Date of Foundation</h6>
            <h5>{$formatted_dof} ({$age} years)</h5>
            <h6>Confederation</h6>
            <h5>{$confederation}</h5>
            $stadium
            $located
            {$international_titles}
        </div>
        <div class="sp_team_profile_data col_3">
            <h6>Ranking IFDB</h6>
            <div class="sp_team_profile-ranking">
                <div class="sp_team_profile-current">
                    <div class="icon-bg-hexagon" style="color: {$ranking_color}"></div>
                    <div class="sp_team_profile-position" style="font-size: {$ranking_size}px">{$position}</div>
                    <div class="sp_team_profile-points">{$points} PTS</div>
                </div>
                <div class="sp_team_profile-historical">
                    Best Position
                    <div class="sp_team_profile-best">{$best_position}</div>
                    <div class="sp_team_profile-last">{$last_time_best}</div>
                </div>
            </div>
            <h6>Kits</h6>
            <div class="sp_team_profile-kits">
                {$home_kit}
                {$away_kit}
                {$third_kit}
                {$fourth_kit}
            </div>
        </div>
    </div>
    HTML;

    return $profile;
}
add_shortcode('sp_team_profile', 'sp_team_profile_shortcode');



function sp_team_stats_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts);

    // Get player ID from shortcode attribute
    $team_id = get_the_ID();
    $team = new SP_team( $team_id );

    // Make sure team ID is provided
    if (!$team_id) {
        return 'Team ID is required';
    }

    // Initialize arrays to store league IDs and opposing team IDs
    $league_ids = array();
    $opposing_team_ids = array();

    global $wpdb;

    // Query to retrieve match IDs where the team participated
    $events_query = $wpdb->prepare(
        "
        SELECT ID
        FROM {$wpdb->posts} AS p
        INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
        WHERE p.post_type = 'sp_event'
        AND pm.meta_key = 'sp_team'
        AND pm.meta_value = %d
        ",
        $team_id
    );

    $match_ids = $wpdb->get_col($events_query);

    foreach ($match_ids as $match_id) {
        // Index all tournaments
        $match_league_ids = wp_get_post_terms($match_id, 'sp_league', array('fields' => 'ids'));
        $filtered_league_ids = array_diff($match_league_ids, array(31, 32));
        $league_ids = array_merge($league_ids, $filtered_league_ids);

        // Index all opponents
        $match_team_ids = get_post_meta($match_id, 'sp_team', false);
        $opposing_team_id = array_diff($match_team_ids, array($team_id));
        $opposing_team_ids = array_merge($opposing_team_ids, $opposing_team_id);
    }

    $league_ids = array_unique($league_ids);

    // Output table header
    $output = '<div class="player_stats-accordion accordion">';
    $output .= '<input type="checkbox" id="accordion-tournament" name="accordion">';
    $output .= '<label for="accordion-tournament">Stats per Tournament</label>';
    $output .= '<div class="accordion-content">';
    
    $output .= '<table>';
    $output .= '<tr>';
    $output .= '<th>Tournament Name</th>';
    $output .= '<th>Matches</th>';
    $output .= '<th>Wins</th>';
    $output .= '<th>Draws</th>';
    $output .= '<th>Losses</th>';
    $output .= '<th>Effectiveness (3ppm)</th>';
    $output .= '<th>Goals For</th>';
    $output .= '<th>Goals Against</th>';
    $output .= '</tr>';

    // Initialize totals
    $total_matches = 0;
    $total_wins = 0;
    $total_draws = 0;
    $total_losses = 0;
    $total_goals_for = 0;
    $total_goals_against = 0;

    foreach ($league_ids as $league_id) {
        // Get tournament name
        $tournament_name = get_term($league_id, 'sp_league')->name;

        // Initialize statistics for current tournament
        $tournament_matches = 0;
        $tournament_wins = 0;
        $tournament_draws = 0;
        $tournament_losses = 0;
        $tournament_goals_for = 0;
        $tournament_goals_against = 0;

        // Query to retrieve matches for current tournament
        $tournament_matches_query = $wpdb->prepare(
            "
            SELECT p.ID, pm.meta_value AS team_id
            FROM {$wpdb->posts} AS p
            INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
            INNER JOIN {$wpdb->term_relationships} AS tr ON p.ID = tr.object_id
            INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
            WHERE p.post_type = 'sp_event'
            AND pm.meta_key = 'sp_team'
            AND pm.meta_value = %d
            AND tt.taxonomy = 'sp_league'
            AND t.term_id = %d
            ",
            $team_id,
            $league_id
        );

        $tournament_matches_results = $wpdb->get_col($tournament_matches_query);

        // Process matches for current tournament
        foreach ($tournament_matches_results as $match) {
            // Increment matches count
            $tournament_matches++;

            // Process match outcome
            $match_results = get_post_meta($match, 'sp_results', true);
            $team_ids = get_post_meta($match_id, 'sp_team', false);
            $opponent_team_ids = array_diff($team_ids, array($team_id));
            $opponent_team_id = reset($opponent_team_ids);
            // Increment wins, draws, losses, goals for, goals against based on match outcome
            if (isset($match_results[$team_id])) {
                // Get the outcome of the match for the team associated with the staff
                $outcome = $match_results[$team_id]['outcome'][0] ?? '';
                switch ($outcome) {
                    case 'win':
                        $tournament_wins++;
                        break;
                    case 'draw':
                        $tournament_draws++;
                        break;
                    case 'loss':
                        $tournament_losses++;
                        break;
                } 
                $goals = $match_results[$team_id]['goals'][0] ?? '';
                $tournament_goals_for += intval($goals);
            }
            
            if (isset($match_results[$opponent_team_id])) {
                $goals_a = $match_results[$opponent_team_id]['goals'][0] ?? '';
                $tournament_goals_against += intval($goals_a);
            }
        }

        // Add tournament statistics to totals
        $total_matches += $tournament_matches;
        $total_wins += $tournament_wins;
        $total_draws += $tournament_draws;
        $total_losses += $tournament_losses;
        $total_goals_for += $tournament_goals_for;
        $total_goals_against += $tournament_goals_against;

        $percentage = ((($tournament_wins * 3) + $tournament_draws) / ($tournament_matches * 3)) * 100;
        $tournament_percentage = bcdiv($percentage, '1', 2);

        $T_percentage = ((($total_wins * 3) + $total_draws) / ($total_matches * 3)) * 100;
        $total_percentage = bcdiv($T_percentage, '1', 2);

        // Output row for current tournament
        $output .= '<tr>';
        $output .= '<td>' . $tournament_name . '</td>';
        $output .= '<td>' . $tournament_matches . '</td>';
        $output .= '<td>' . $tournament_wins . '</td>';
        $output .= '<td>' . $tournament_draws . '</td>';
        $output .= '<td>' . $tournament_losses . '</td>';
        $output .= '<td>' . $tournament_percentage . ' %</td>';
        $output .= '<td>' . $tournament_goals_for . '</td>';
        $output .= '<td>' . $tournament_goals_against . '</td>';
        $output .= '</tr>';
    }

    // Output totals row
    $output .= '<tr>';
    $output .= '<td class="stats-total_cell">Total</td>';
    $output .= '<td class="stats-total_cell">' . $total_matches . '</td>';
    $output .= '<td class="stats-total_cell">' . $total_wins . '</td>';
    $output .= '<td class="stats-total_cell">' . $total_draws . '</td>';
    $output .= '<td class="stats-total_cell">' . $total_losses . '</td>';
    $output .= '<td class="stats-total_cell">' . $total_percentage . ' %</td>';
    $output .= '<td class="stats-total_cell">' . $total_goals_for . '</td>';
    $output .= '<td class="stats-total_cell">' . $total_goals_against . '</td>';
    $output .= '</tr>';

    $output .= '</table>';
    $output .= '</div>';

    $output .= '<input type="checkbox" id="accordion-rivals" name="accordion">';
    $output .= '<label for="accordion-rivals">Stats Against Rivals</label>';
    $output .= '<div class="accordion-content">';
    
    $output .= '<table>';
    $output .= '<tr>';
    $output .= '<th>Team</th>';
    $output .= '<th>Matches</th>';
    $output .= '<th>Wins</th>';
    $output .= '<th>Draws</th>';
    $output .= '<th>Losses</th>';
    $output .= '<th>Effectiveness (3ppm)</th>';
    $output .= '<th>Goals For</th>';
    $output .= '<th>Goals Against</th>';
    $output .= '</tr>';

    $opposing_team_ids = array_unique($opposing_team_ids);

    // Convert team IDs to team names
    foreach ($opposing_team_ids as $opposing_team_id) {
        $opposing_team_name = get_the_title($opposing_team_id);
        if ($opposing_team_name) {
            $opposing_team_names[] = $opposing_team_name;
        }
    }
    
    // Sort team names alphabetically
    if ($opposing_team_names){
        sort($opposing_team_names);
    }

    // Convert team names back to team IDs
    $opposing_team_ids_sorted = array();
    foreach ($opposing_team_names as $opposing_team_name) {
        $opposing_team_id = get_page_by_title($opposing_team_name, OBJECT, 'sp_team')->ID;
        if ($opposing_team_id) {
            $opposing_team_ids_sorted[] = $opposing_team_id;
        }
    }

    foreach ($opposing_team_ids_sorted as $opposing_team_id) {
        // Get tournament name
        $rival_name = get_the_title($opposing_team_id);

        // Initialize statistics for current rival
        $rival_matches = 0;
        $rival_wins = 0;
        $rival_draws = 0;
        $rival_losses = 0;
        $rival_goals_for = 0;
        $rival_goals_against = 0;

        // Query to retrieve matches for current rival
        $rival_matches_query = $wpdb->prepare(
            "
            SELECT p.ID, pm.meta_value AS team_id
            FROM {$wpdb->posts} AS p
            INNER JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id
            INNER JOIN {$wpdb->term_relationships} AS tr ON p.ID = tr.object_id
            INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
            WHERE p.post_type = 'sp_event'
            AND pm.meta_key = 'sp_team'
            AND pm.meta_value = %d
            AND tt.taxonomy = 'sp_league'
            AND t.term_id = %d
            ",
            $team_id,
            $league_id
        );

        $rival_matches_results = $wpdb->get_col($rival_matches_query);

        foreach ($rival_matches_results as $rival_match) {
            $rival_matches++;
            // Process match outcome
            $match_results = get_post_meta($rival_match, 'sp_results', true);
            // Increment wins, draws, losses, goals for, goals against based on match outcome
            if (isset($match_results[$team_id])) {
                // Get the outcome of the match for the team associated with the staff
                $outcome = $match_results[$team_id]['outcome'][0] ?? '';
                switch ($outcome) {
                    case 'win':
                        $rival_wins++;
                        break;
                    case 'draw':
                        $rival_draws++;
                        break;
                    case 'loss':
                        $rival_losses++;
                        break;
                } 
                $rival_goals = $match_results[$team_id]['goals'][0] ?? '';
                $rival_goals_for += intval($rival_goals);
            }
            
            if (isset($match_results[$opposing_team_id])) {
                $rival_goals_a = $match_results[$opponent_team_id]['goals'][0] ?? '';
                $rival_goals_against += intval($rival_goals_a);
            }
        }

        $percentage = ((($rival_wins * 3) + $rival_draws) / ($rival_matches * 3)) * 100;
        $rival_percentage = bcdiv($percentage, '1', 2);

        $output .= '<tr>';
        $output .= '<td>' . $rival_name . '</td>';
        $output .= '<td>' . $rival_matches . '</td>';
        $output .= '<td>' . $rival_wins . '</td>';
        $output .= '<td>' . $rival_draws . '</td>';
        $output .= '<td>' . $rival_losses . '</td>';
        $output .= '<td>' . $rival_percentage . ' %</td>';
        $output .= '<td>' . $rival_goals_for . '</td>';
        $output .= '<td>' . $rival_goals_against . '</td>';
        $output .= '</tr>';
    }

    // Output totals row
    $output .= '<tr>';
    $output .= '<td class="stats-total_cell">Total</td>';
    $output .= '<td class="stats-total_cell">' . $total_matches . '</td>';
    $output .= '<td class="stats-total_cell">' . $total_wins . '</td>';
    $output .= '<td class="stats-total_cell">' . $total_draws . '</td>';
    $output .= '<td class="stats-total_cell">' . $total_losses . '</td>';
    $output .= '<td class="stats-total_cell">' . $total_percentage . ' %</td>';
    $output .= '<td class="stats-total_cell">' . $total_goals_for . '</td>';
    $output .= '<td class="stats-total_cell">' . $total_goals_against . '</td>';
    $output .= '</tr>';

    $output .= '</table>';
    $output .= '</div>';

    $output .= '</div>';

    return $output;
}
add_shortcode('sp_team_stats', 'sp_team_stats_shortcode');



// Extend the player list shortcode for team template
function dynamic_team_player_list_shortcode($atts) {
    // Check if we're on a single team page
    if (is_singular('sp_team')) {
        // Get the current team ID
        $team_id = get_the_ID();
        // Add the team ID to the shortcode attributes
        $atts['team'] = $team_id;
        $type = get_field('team_type');
        $atts['type'] = $type;
    }

    // Return the existing player_list shortcode with the modified attributes
    return do_shortcode(build_shortcode_with_atts('player_list', $atts));
}

// Helper function to build shortcode string with attributes
function build_shortcode_with_atts($shortcode, $atts) {
    $shortcode_str = '[' . $shortcode;
    foreach ($atts as $key => $value) {
        $shortcode_str .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
    }
    $shortcode_str .= ']';

    return $shortcode_str;
}

// Register the new shortcode
add_shortcode('dynamic_player_list', 'dynamic_team_player_list_shortcode');



// Function to create shortcode to add actual staff to team
function sp_team_staff_shortcode( $atts ) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts);

    // Get player ID from shortcode attribute
    $team_id = get_the_ID();
    $team = new SP_team( $team_id );

    // Make sure team ID is provided
    if (!$team_id) {
        return 'Team ID is required';
    }

    // Retrieve staff members associated with the team
    $staff_members = get_posts( array(
        'post_type' => 'sp_staff',
        'meta_query' => array(
            array(
                'key' => 'sp_team', // Adjust this key to match your custom field that stores the team ID
                'value' => $team_id,
                'compare' => '='
            )
        ),
        'numberposts' => -1
    ) );

    if ( empty( $staff_members ) ) {
        return '<p>No staff members found.</p>';
    }

    $output = '';

    foreach ( $staff_members as $staff ) {
        $short_first_name = get_post_meta( $staff->ID, 'short_first_name', true );
        $short_last_name = get_post_meta( $staff->ID, 'short_last_name', true );

        if ( ! empty( $short_last_name ) ) {
            $name = $short_last_name;
            if ( ! empty( $short_first_name ) ) {
                $name = $short_first_name . ' ' . $short_last_name;
            }
        } else {
            $name = get_the_title( $staff->ID );
        }

        $staff_url = get_permalink( $staff->ID );
        $output .= '<h5>Manager: <a href="' . esc_url( $staff_url ) . '">' . esc_html( $name ) . '</a></h5>';
    }

    return $output;
}

// Register the new shortcode
add_shortcode('sp_team_staff', 'sp_team_staff_shortcode');



// Function to create shortcode to add actual staff to team
function sp_team_museum_shortcode( $atts ) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts);

    // Get player ID from shortcode attribute
    $team_id = get_the_ID();
    $team = new SP_team( $team_id );

    // Make sure team ID is provided
    if (!$team_id) {
        return 'Team ID is required';
    }

    $logos = get_field('logos');
    $kit_history = get_field('kit_history');

    $output = '<div class="sp_team-museum">';

    if (!empty($logos)) {
        $output .= '<h4>Logo History</h4>';
        $output .= '<div class="sp_team-museum_logos">';
        foreach ($logos as $logo) {
            $output .= '<div class="sp_team-museum_logo">';
            $output .= '<div class="sp_team-logo_container">';
            if (isset($logo['logo']['sizes']['sportspress-fit-icon'])) {
                $logo_url = $logo['logo']['sizes']['sportspress-fit-icon'];
                $output .= '<img src="' . esc_url($logo_url) . '">';
            } else {
                // Fallback to the full URL if the specific size is not available
                $logo_url = $logo['logo']['url'];
                $output .= '<img src="' . esc_url($logo_url) . '">';
            }
            $output .= '</div>';
            if (!empty($logo['from'])) {
                $output .= '<div class="sp_team-museum_dates">';
                $output .= $logo['from'];
                if (!empty($logo['to'])) {
                    $output .= ' - ' . $logo['to'];
                }
                $output .= '</div>';
            }
            $output .= '</div>';
        }
        $output .= '</div>';
    }

    if (!empty($kit_history)) {
        $output .= '<h4>Kit History</h4>';
        $output .= '<div class="sp_team-museum_kits">';
        foreach ($kit_history as $kit) {
            $output .= '<div class="sp_team-museum_kit">';
            $output .= '<div class="sp_team-kit_box">';

            if (isset($kit['home_kit']['sizes']['sportspress-fit-icon'])) {
                $home_kit = $kit['home_kit']['sizes']['sportspress-fit-icon'];
                $output .= '<img src="' . esc_url($home_kit) . '" class="home_kit">';
            } else if (!empty($kit['home_kit'])) {
                // Fallback to the full URL if the specific size is not available
                $home_kit = $kit['home_kit']['url'];
                $output .= '<img src="' . esc_url($home_kit) . '" class="home_kit">';
            }

            $away_kit_position = 'right_kit';
            if (!empty($kit['third_kit'])) {
                $away_kit_position = 'left_kit';
            }

            if (isset($kit['away_kit']['sizes']['sportspress-fit-icon'])) {
                $away_kit = $kit['away_kit']['sizes']['sportspress-fit-icon'];
                $output .= '<img src="' . esc_url($away_kit) . '" class="' . $away_kit_position . '">';
            } else if (!empty($kit['away_kit'])) {
                // Fallback to the full URL if the specific size is not available
                $away_kit = $kit['away_kit']['url'];
                $output .= '<img src="' . esc_url($away_kit) . '" class="' . $away_kit_position . '">';
            }

            if (isset($kit['third_kit']['sizes']['sportspress-fit-icon'])) {
                $third_kit = $kit['third_kit']['sizes']['sportspress-fit-icon'];
                $output .= '<img src="' . esc_url($third_kit) . '" class="right_kit">';
            } else if (!empty($kit['third_kit'])) {
                // Fallback to the full URL if the specific size is not available
                $third_kit = $kit['third_kit']['url'];
                $output .= '<img src="' . esc_url($third_kit) . '" class="right_kit">';
            }

            $output .= '</div>';

            if (!empty($kit['season'])) {
                $output .= '<div class="sp_team-kit_season">';
                $output .= $kit['season'];
                $output .= '</div>';
            }

            if (($kit['champion_kit'])) {
                $output .= '<div class="champion_kit">';
                $output .= '<i class="sp-icon-star-filled" style="color:goldenrod !important"></i>';
                $output .= '</div>';
            }

            $output .= '</div>';
        }
        $output .= '</div>';
        $output .= '<div style="font-size:12px; line-height: 14px; margin-top: 12px"><i class="sp-icon-star-filled" style="color:goldenrod !important;font-size:12px; line-height: 14px; height: 14px; width: 14px;"></i> = Champion kit</div>';
    }

    $output .= '</div>';

    return $output;
}

// Register the new shortcode
add_shortcode('sp_team_museum', 'sp_team_museum_shortcode');



// Function to create shortcode to add actual staff to team
function sp_match_title_shortcode( $atts ) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts);

    // Get event ID from shortcode attribute
    $event_id = get_the_ID();
    $event = new SP_Event( $event_id );

    // Make sure event ID is provided
    if (!$event_id) {
        return 'Event ID is required';
    }

    $data = $event->results();
    $labels = $data[0];
    unset($data[0]);

    $data = array_filter($data);

    if (empty($data)) {
        return false;
    }

    $link_teams    = get_option('sportspress_link_teams', 'no') == 'yes';
    $show_outcomes = array_key_exists('outcome', $labels);

    // Initialize variables for team details
    $team1_logo = $team1_flag_image = $team1_name = $team1_score = '';
    $team2_logo = $team2_flag_image = $team2_name = $team2_score = '';

    // Assuming there are only two teams
    $team_ids = array_keys($data);
    if (count($team_ids) < 2) {
        return 'Not enough teams data available';
    }

    // Get details for the first team
    $team1_id = $team_ids[0];
    $team1_logo = get_the_post_thumbnail($team1_id, 'sportspress-fit-icon');
    $team1_flag = get_post_meta($team1_id, 'hexagon_flag', true);
    $team1_flag_image = wp_get_attachment_image($team1_flag, 'sportspress-fit-icon');
    $team1_name = get_the_title($team1_id);
    $team1_abbr = sp_team_abbreviation($team1_id);
    $team1_score = isset($data[$team1_id]['goals']) ? $data[$team1_id]['goals'] : '&mdash;';
    if ($link_teams && sp_post_exists($team1_id)) {
        $team1_name = '<a href="' . get_post_permalink($team1_id) . '">' . $team1_name . '</a>';
    }
    $team1_colors = get_post_meta($team1_id, 'sp_colors', true);
    $team1_color = $team1_colors['primary'];
    $team1_replace_color = get_post_meta($event_id, 'home_team_setup_replace_team_color', true);
    if ($team1_replace_color) {
        $team1_color = get_post_meta($event_id, 'home_team_setup_team_color', true);
    }

    // Get details for the second team
    $team2_id = $team_ids[1];
    $team2_logo = get_the_post_thumbnail($team2_id, 'sportspress-fit-icon');
    $team2_flag = get_post_meta($team2_id, 'hexagon_flag', true);
    $team2_flag_image = wp_get_attachment_image($team2_flag, 'sportspress-fit-icon');
    $team2_name = get_the_title($team2_id);
    $team2_abbr = sp_team_abbreviation($team2_id);
    $team2_score = isset($data[$team2_id]['goals']) ? $data[$team2_id]['goals'] : '&mdash;';
    if ($link_teams && sp_post_exists($team2_id)) {
        $team2_name = '<a href="' . get_post_permalink($team2_id) . '">' . $team2_name . '</a>';
    }
    $team2_colors = get_post_meta($team2_id, 'sp_colors', true);
    $team2_color = $team2_colors['primary'];
    $team2_replace_color = get_post_meta($event_id, 'away_team_setup_replace_team_color', true);
    if ($team2_replace_color) {
        $team2_color = get_post_meta($event_id, 'away_team_setup_team_color', true);
    }
    
    $data = array();

    if ( 'yes' === get_option( 'sportspress_event_show_date', 'yes' ) ) {
        $date = date_i18n( 'l, F j, Y', strtotime( get_the_date( '', $event_id ) ) );
        $data[ esc_attr__( 'Date', 'sportspress' ) ] = $date;
    }

    if ( 'yes' === get_option( 'sportspress_event_show_time', 'yes' ) ) {
        $time = get_the_time( get_option( 'time_format' ), $event_id );
        $data[ esc_attr__( 'Time', 'sportspress' ) ] = apply_filters( 'sportspress_event_time', $time, $event_id );
    }

    $taxonomies = apply_filters(
        'sportspress_event_taxonomies',
        array(
            'sp_league' => null,
            'sp_season' => null,
        )
    );

    foreach ( $taxonomies as $taxonomy => $post_type ) :
        $terms = get_the_terms( $event_id, $taxonomy );
        if ( $terms ) :
            $obj = get_taxonomy( $taxonomy );
            $term = array_shift( $terms );
            $data[ $obj->labels->singular_name ] = $term->name;
        endif;
    endforeach;

    if ( 'yes' === get_option( 'sportspress_event_show_day', 'yes' ) ) {
        $day = get_post_meta( $event_id, 'sp_day', true );
        if ( '' !== $day ) {
            $data[ esc_attr__( 'Match Day', 'sportspress' ) ] = $day;
        }
    }
    
    if ( 'yes' === get_option( 'sportspress_event_show_full_time', 'yes' ) ) {
        $full_time = get_post_meta( $event_id, 'sp_minutes', true );
        if ( '' === $full_time ) {
            $full_time = get_option( 'sportspress_event_minutes', 90 );
        }
        $data[ esc_attr__( 'Full Time', 'sportspress' ) ] = $full_time . '\'';
    }

    $data = apply_filters( 'sportspress_event_details', $data, $event_id );

    if ( ! sizeof( $data ) ) {
        return;
    }

    $season = $data['Season'];

    // Function to get the correct logo based on season
    function get_team_logo_by_season($team_id, $season) {
        $logo_history = get_field('logos', $team_id);
        $selected_logo = null;
        
        if ($logo_history) {
            // First check for logos without a 'to' value
            foreach ($logo_history as $logo) {
                if (!empty($logo['from']) && empty($logo['to']) && $logo['from'] <= $season) {
                    $selected_logo = $logo['logo'];
                    break;
                }
            }

            // If no logo without 'to' was found, check for ranged logos
            if (!$selected_logo) {
                foreach ($logo_history as $logo) {
                    if (!empty($logo['from']) && !empty($logo['to']) && $logo['from'] <= $season && $logo['to'] > $season) {
                        $selected_logo = $logo['logo'];
                        break;
                    }
                }
            }
        }

        if ($selected_logo) {
            return wp_get_attachment_image($selected_logo['ID'], 'sportspress-fit-icon');
        } else {
            return get_the_post_thumbnail($team_id, 'sportspress-fit-icon');
        }
    }

    // Get correct logos for each team
    $team1_logo = get_team_logo_by_season($team1_id, $season);
    $team2_logo = get_team_logo_by_season($team2_id, $season);

    $tournament = $data['League'] . ' ' . $season;
    $tournament_slug = sanitize_title($tournament);
    $tournament_url = get_site_url() . '/' . $tournament_slug;

    $matchday = $data['Match Day'];

    $ground_terms = wp_get_post_terms($event_id, 'sp_venue');
    $stadium = '';
    if (!is_wp_error($ground_terms) && !empty($ground_terms)) {
        $ground = $ground_terms[0]; // Assuming only one ground term is assigned
        $ground_hierarchy = array($ground->name);

        while ($ground->parent != 0) {
            $ground = get_term($ground->parent, 'sp_venue');
            $ground_hierarchy[] = $ground->name;
        }

        $ground_name = array_shift($ground_hierarchy);
        $stadium = $ground_name . ' (' . implode(', ', $ground_hierarchy) . ')';
    }

    $score_aet = '';
    $extra_time = get_field('extra_time');
    if ($extra_time) {
        $score_aet = '<div class="sp_match_title-aet">AET</div>';
    }

    $prev_legs = '';
    $is_not_a_first_leg = get_field('is_not_a_first_leg');
    if ($is_not_a_first_leg) {
        $previous_legs = get_field('previous_legs');
        $global_result = get_field('global_result');
        $prev_legs = '<div class="sp_match_title-prev">';
        $prev_legs .= $previous_legs . '<br>';
        $prev_legs .= $global_result;
        $prev_legs .= '</div>';
    }

    $pk_shootout = '';
    $penalty_shootout = get_field('penalty_shootout');
    if ($penalty_shootout) {
        $home_team_shootout_score = get_field('home_team_shootout_score');
        $away_team_shootout_score = get_field('away_team_shootout_score');
        $pk_shootout = '<div class="sp_match_title-pk">(' . $home_team_shootout_score . '-' . $away_team_shootout_score . ')</div>';
    }

    // Construct output
    $output = '';
    $output .= <<<HTML
    <div class="sp_match_data">
        <h6>{$date}</h6>
        <h5><a href="{$tournament_url}">{$tournament} - {$matchday}</a></h5>
        <h5>{$stadium}</h5>
    </div>
    <div class="sp_match_title">
        <div class="sp_match_title-team sp_match_title-home">
            <div class="sp_match_title-images">
                <div class="sp_match_title-logos">
                    <div class="icon-bg-hexagon" style="color:#111"></div>
                    <div class="icon-bg-hexagon hexagon-2" style="color:{$team1_color}"></div>
                    <div class="sp_match_title-logo">{$team1_logo}</div>
                </div>
                <div class="sp_match_title-flags">
                    <div class="icon-bg-hexagon" style="color:#111"></div>
                    {$team1_flag_image}
                </div>
            </div>
            <div class="sp_match_title-name">
                <div class="sp_match_title-name_container" style="border-bottom-color:{$team1_color};">
                    <span class="fullname">{$team1_name}</span><span class="abbr">{$team1_abbr}</span>
                </div>
            </div>
        </div>
        <div class="sp_match_title-score">
            {$team1_score}-{$team2_score}
            {$pk_shootout}
        </div>
        <div class="sp_match_title-team sp_match_title-away">
            <div class="sp_match_title-name">
                <div class="sp_match_title-name_container" style="border-bottom-color:{$team2_color};">
                    <span class="fullname">{$team2_name}</span><span class="abbr">{$team2_abbr}</span>
                </div>
            </div>
            <div class="sp_match_title-images">
                <div class="sp_match_title-logos">
                    <div class="icon-bg-hexagon" style="color:#111"></div>
                    <div class="icon-bg-hexagon hexagon-2" style="color:{$team2_color}"></div>
                    <div class="sp_match_title-logo">{$team2_logo}</div>
                </div>
                <div class="sp_match_title-flags">
                    <div class="icon-bg-hexagon" style="color:#111"></div>
                    {$team2_flag_image}
                </div>
            </div>
        </div>
    </div>
    <div class="sp_match_title_adds">
        {$score_aet}
        {$prev_legs}
    </div>
    HTML;

    return $output;
}

// Register the new shortcode
add_shortcode('sp_match_title', 'sp_match_title_shortcode');



// Function to create shortcode to display match scorers
function sp_match_scorers_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts);

    // Get event ID from shortcode attribute or current post
    $event_id = !empty($atts['id']) ? intval($atts['id']) : get_the_ID();
    if (!$event_id) {
        return 'Event ID is required';
    }

    $event = new SP_Event($event_id);

    // Ensure the event object is valid by checking the title (or any other field)
    if (empty(get_the_title($event_id))) {
        return 'Invalid event ID';
    }

    $data = $event->results();
    $labels = $data[0];
    unset($data[0]);
    $data = array_filter($data);
    if (empty($data)) {
        return false;
    }
    // Assuming there are only two teams
    $team_ids = array_keys($data);
    if (count($team_ids) < 2) {
        return 'Not enough teams data available';
    }

    $data = array();

    $taxonomies = apply_filters(
        'sportspress_event_taxonomies',
        array(
            'sp_league' => null,
            'sp_season' => null,
        )
    );

    foreach ( $taxonomies as $taxonomy => $post_type ) :
        $terms = get_the_terms( $event_id, $taxonomy );
        if ( $terms ) :
            $obj = get_taxonomy( $taxonomy );
            $term = array_shift( $terms );
            $data[ $obj->labels->singular_name ] = $term->name;
        endif;
    endforeach;
    
    // Get details for the teams
    $team1 = $team_ids[0];
    $team1_kit = get_post_meta($team1, 'home_kit', true);
    $team1_kit_image = wp_get_attachment_image($team1_kit, 'sportspress-fit-icon');
    $team1_edit_kit = get_post_meta($event_id, 'home_team_setup_edit_team_kit', true);
    if ($team1_edit_kit) {
        $team1_kit_season = get_post_meta($event_id, 'home_team_setup_season', true);
        $team1_shirt = get_post_meta($event_id, 'home_team_setup_shirt', true);
        $team1_short = get_post_meta($event_id, 'home_team_setup_short', true);
        $team1_socks = get_post_meta($event_id, 'home_team_setup_socks', true);
        $kit_history = get_field('kit_history', $team1);
        foreach ($kit_history as $kit) {
            $season = $kit['season'];
            if ($team1_kit_season == $season) {
                $team1_home_kit = $kit['home_kit'];
                $team1_away_kit = $kit['away_kit'];
                $team1_third_kit = isset($kit['third_kit']) ? $kit['third_kit'] : null;
                if ($team1_shirt === 'home' && $team1_short === 'home' && $team1_socks === 'home') {
                    $team1_kit_image = wp_get_attachment_image($team1_home_kit['ID'], 'sportspress-fit-icon');
                } elseif ($team1_shirt === 'away' && $team1_short === 'away' && $team1_socks === 'away') {
                    $team1_kit_image = wp_get_attachment_image($team1_away_kit['ID'], 'sportspress-fit-icon');
                } elseif ($team1_shirt === 'third' && $team1_short === 'third' && $team1_socks === 'third') {
                    $team1_kit_image = wp_get_attachment_image($team1_third_kit['ID'], 'sportspress-fit-icon');
                } else {
                    $team1_shirt_image = wp_get_attachment_image($kit[$team1_shirt . '_kit']['ID'], 'sportspress-fit-icon');
                    $team1_short_image = wp_get_attachment_image($kit[$team1_short . '_kit']['ID'], 'sportspress-fit-icon');
                    $team1_socks_image = wp_get_attachment_image($kit[$team1_socks . '_kit']['ID'], 'sportspress-fit-icon');
    
                    $team1_kit_image = '<div class="kit-piece shirt">' . $team1_shirt_image . '</div>';
                    $team1_kit_image .= '<div class="kit-piece short">' . $team1_short_image . '</div>';
                    $team1_kit_image .= '<div class="kit-piece socks">' . $team1_socks_image . '</div>';
                }
                break;
            }
        }
    }

    $team2 = $team_ids[1];
    $team2_kit = get_post_meta($team2, 'home_kit', true);
    $team2_kit_image = wp_get_attachment_image($team2_kit, 'sportspress-fit-icon');
    $team2_edit_kit = get_post_meta($event_id, 'home_team_setup_edit_team_kit', true);
    if ($team2_edit_kit) {
        $team2_kit_season = get_post_meta($event_id, 'home_team_setup_season', true);
        $team2_shirt = get_post_meta($event_id, 'home_team_setup_shirt', true);
        $team2_short = get_post_meta($event_id, 'home_team_setup_short', true);
        $team2_socks = get_post_meta($event_id, 'home_team_setup_socks', true);
        $kit_history = get_field('kit_history', $team2);
        foreach ($kit_history as $kit) {
            $season = $kit['season'];
            if ($team2_kit_season == $season) {
                $team2_home_kit = $kit['home_kit'];
                $team2_away_kit = $kit['away_kit'];
                $team2_third_kit = isset($kit['third_kit']) ? $kit['third_kit'] : null;
                if ($team2_shirt === 'home' && $team2_short === 'home' && $team2_socks === 'home') {
                    $team2_kit_image = wp_get_attachment_image($team2_home_kit['ID'], 'sportspress-fit-icon');
                } elseif ($team2_shirt === 'away' && $team2_short === 'away' && $team2_socks === 'away') {
                    $team2_kit_image = wp_get_attachment_image($team2_away_kit['ID'], 'sportspress-fit-icon');
                } elseif ($team2_shirt === 'third' && $team2_short === 'third' && $team2_socks === 'third') {
                    $team2_kit_image = wp_get_attachment_image($team2_third_kit['ID'], 'sportspress-fit-icon');
                } else {
                    $team2_shirt_image = wp_get_attachment_image($kit[$team2_shirt . '_kit']['ID'], 'sportspress-fit-icon');
                    $team2_short_image = wp_get_attachment_image($kit[$team2_short . '_kit']['ID'], 'sportspress-fit-icon');
                    $team2_socks_image = wp_get_attachment_image($kit[$team2_socks . '_kit']['ID'], 'sportspress-fit-icon');
    
                    $team2_kit_image = '<div class="kit-piece shirt">' . $team2_shirt_image . '</div>';
                    $team2_kit_image .= '<div class="kit-piece short">' . $team2_short_image . '</div>';
                    $team2_kit_image .= '<div class="kit-piece socks">' . $team2_socks_image . '</div>';
                }
                break;
            }
        }
    }

    // Get player performance data
    $performance = $event->performance();
    if (empty($performance)) {
        return 'No performance data available';
    }

    // Initialize goalscorers array
    $goalscorers = array();

    // Loop through performance data and store relevant data
    foreach ($performance as $team_id => $players) {
        foreach ($players as $player_id => $data) {
            if (!empty($data['goals'])) {
                $goals = $data['goals'];

                // Extract goal times and store them individually
                if (preg_match_all('/(\d+)\'/', $goals, $matches)) {
                    foreach ($matches[1] as $goal_time) {
                        $goalscorers[] = array(
                            'player_id' => $player_id,
                            'team_id' => $team_id,
                            'time' => intval($goal_time),
                        );
                    }
                }
            }
        }
    }

    // Sort goalscorers by time
    usort($goalscorers, function($a, $b) {
        return $a['time'] - $b['time'];
    });

    $event_specs = get_post_meta($event_id, 'sp_specs', true);
    $attendance = '';
    if (isset($event_specs['attendance']) && !empty($event_specs['attendance'])) {
        $attendance = '<div class="sp-match-attendance"><b>Attendance:</b> ' . $event_specs['attendance'] . '</div>';
    }

    $event_officials = get_post_meta($event_id, 'sp_officials', true);
    $officials = '';
    if (!empty($event_officials)) {
        // Process referees (ID 12)
        if (isset($event_officials[12])) {
            $referees = $event_officials[12];
            foreach ($referees as $referee_id) {
                $referee_name = get_the_title($referee_id);
                $officials .= '<div class="sp-match-referee"><b>Referee:</b> ' . esc_html($referee_name) . '</div>';
            }
        }

        // Process assistants (ID 13)
        if (isset($event_officials[13])) {
            $assistants = $event_officials[13];
            $assistant_names = array();
            foreach ($assistants as $assistant_id) {
                $assistant_names[] = get_the_title($assistant_id);
            }
            if (!empty($assistant_names)) {
                $officials .= '<div class="sp-match-assistants"><b>Assistants:</b> ' . esc_html(implode(', ', $assistant_names)) . '</div>';
            }
        }
    }

    $pk_shots = '';
    $penalty_shootout = get_post_meta( $event_id, 'penalty_shootout', true );
    if ($penalty_shootout) {
        $home_team_shootout = get_field('home_team_shootout');
        $home_team_shoots = $home_team_shootout['shoots'];
        $home_team_started = get_field('home_team_shootout_started_the_shootout');
        $away_team_shootout = get_field('away_team_shootout');
        $away_team_shoots = $away_team_shootout['shoots'];
        $away_team_started = get_field('away_team_shootout_started_the_shootout');

        $pk_shots = '<div class="sp-match-pk-shooters">';
        $pk_shots .= '<div class="sp-match-pk-team sp-match-pk-home">';
        $home_counter = 0;
        foreach ($home_team_shoots as $shots) {
            $player = $shots['player'];
            $goal = $shots['scored'];
            $pk_shots .= '<div class="sp-match-pk-shot">';
            $pk_shots .= '<div class="sp-match-pk-shooter">';
            if ($home_team_started && $home_counter === 0) {
                $pk_shots .= '<svg aria-hidden="true" class="e-font-icon-svg e-fas-caret-right" viewBox="0 0 192 512" xmlns="http://www.w3.org/2000/svg"><path d="M0 384.662V127.338c0-17.818 21.543-26.741 34.142-14.142l128.662 128.662c7.81 7.81 7.81 20.474 0 28.284L34.142 398.804C21.543 411.404 0 402.48 0 384.662z"></path></svg> ';
            }
            $pk_shots .= $player;
            $pk_shots .= '</div>';
            $pk_shots .= '<div class="sp-match-pk-scored">';
            if ($goal) {
                $pk_shots .= '<i class="sp-icon-marker" style="color:green"></i>';
            } else {
                $pk_shots .= '<i class="sp-icon-no" style="color:red"></i>';
            }
            $pk_shots .= '</div>';
            $pk_shots .= '</div>';
            $home_counter++;
        }
        $pk_shots .= '</div>';
        $pk_shots .= '<div class="sp-match-pk-team sp-match-pk-away">';

        $away_counter = 0;
        foreach ($away_team_shoots as $shots) {
            $player = $shots['player'];
            $goal = $shots['scored'];
            $pk_shots .= '<div class="sp-match-pk-shot">';
            $pk_shots .= '<div class="sp-match-pk-scored">';
            if ($goal) {
                $pk_shots .= '<i class="sp-icon-marker" style="color:green"></i>';
            } else {
                $pk_shots .= '<i class="sp-icon-no" style="color:red"></i>';
            }
            $pk_shots .= '</div>';
            $pk_shots .= '<div class="sp-match-pk-shooter">';
            $pk_shots .= $player;
            if ($away_team_started && $away_counter === 0) {
                $pk_shots .= ' <svg aria-hidden="true" class="e-font-icon-svg e-fas-caret-left" viewBox="0 0 192 512" xmlns="http://www.w3.org/2000/svg"><path d="M192 127.338v257.324c0 17.818-21.543 26.741-34.142 14.142L29.196 270.142c-7.81-7.81-7.81-20.474 0-28.284l128.662-128.662c12.599-12.6 34.142-3.676 34.142 14.142z"></path></svg>';
            }
            $pk_shots .= '</div>';
            $pk_shots .= '</div>';
            $away_counter++;
        }
        $pk_shots .= '</div>';
        $pk_shots .= '</div>';
    }

    // Prepare output
    $output = '<div class="sp-match-scorers-kits">';
    $output .= '<div class="sp-match-kit sp-match-home-kit">';
    $output .= $team1_kit_image;
    $output .= '</div>';
    $output .= '<div class="sp-match-scorers">';
    foreach ($goalscorers as $goal) {
        $player_ln = get_post_meta($goal['player_id'], 'short_last_name', true);
        $player_name = $player_ln;
        $player_fn = get_post_meta($goal['player_id'], 'short_first_name', true);
        if (isset($player_fn) && !empty($player_fn)) {
            $player_name = $player_fn . ' ' . $player_ln;
        }
        $goal_time = $goal['time'];
        $output .= '<div class="sp-match-scorer">';
        $output .= '<div class="goal-player home-player">';
        if ($goal['team_id'] == $team1) {
            $output .= esc_html($player_name);
        }
        $output .= '</div>';
        
        $output .= '<div class="goal-time">' . esc_html($goal_time) . '\' </div>';
        
        $output .= '<div class="goal-player away-player">';
        if ($goal['team_id'] == $team2) {
            $output .= esc_html($player_name);
        }
        $output .= '</div>';
        $output .= '</div>';
    }
    $output .= $pk_shots;
    $output .= '<div class="sp-match-aditional">';
    $output .= $officials;
    $output .= $attendance;
    $output .= '</div>';
    $output .= '</div>';
    $output .= '<div class="sp-match-kit sp-match-away-kit">';
    $output .= $team2_kit_image;
    $output .= '</div>';
    $output .= '</div>';


    return $output;
}

// Register the new shortcode
add_shortcode('sp_match_scorers', 'sp_match_scorers_shortcode');



function sp_match_timeline_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts);

    // Get event ID from shortcode attribute or current post
    $event_id = !empty($atts['id']) ? intval($atts['id']) : get_the_ID();
    if (!$event_id) {
        return 'Event ID is required';
    }

    // Get event object
    $event = new SP_Event($event_id);

    // Get the performance data
    $performance = $event->performance();
    if (empty($performance)) {
        return 'No performance data available';
    }

    // Get home and away teams
    $home_team_id = $away_team_id = '';
    $teams = get_post_meta($event_id, 'sp_team', false);
    if (is_array($teams) && count($teams) == 2) {
        $home_team_id = $teams[0];
        $away_team_id = $teams[1];
    } else {
        return 'Home and away teams not properly defined';
    }
    $home_team_abbr = get_post_meta($home_team_id, 'sp_abbreviation', true);
    $away_team_abbr = get_post_meta($away_team_id, 'sp_abbreviation', true);

    // Check if extra time is enabled
    $extra_time_enabled = get_post_meta($event_id, 'extra_time', true);

    // Initialize events arrays for first, second halves, and extra time
    $first_half_events = array();
    $second_half_events = array();
    $extra_time_fh_events = array();
    $extra_time_sh_events = array();

    // Loop through performance data and store relevant data
    foreach ($performance as $team_id => $players) {
        foreach ($players as $player_id => $data) {
            if (is_array($data)) {
                foreach ($data as $event_type => $event_string) {
                    // Skip "goalsreceived" events
                    if ($event_type == 'goalsreceived') {
                        continue;
                    }

                    // Ensure event_string is a string before processing
                    if (is_string($event_string) && !empty($event_string)) {
                        // Extract occurrences and minutes
                        if (preg_match_all('/(\d+)\s\(([^)]+)\)/', $event_string, $matches, PREG_SET_ORDER)) {
                            foreach ($matches as $match) {
                                $minutes_str = $match[2];

                                // Split minutes by comma and process each
                                $minutes = explode(',', $minutes_str);
                                foreach ($minutes as $minute_str) {
                                    // Parse minute and determine base minute
                                    $minute_parts = explode('+', trim($minute_str));
                                    $base_minute = intval($minute_parts[0]);
                                    $extra_time = isset($minute_parts[1]) ? intval($minute_parts[1]) : 0;

                                    // Calculate summed minute
                                    $summed_minute = $base_minute + $extra_time;
                                    $original_string = $base_minute;
                                    if ($extra_time > 0) {
                                        $original_string = $base_minute . '+' . $extra_time;
                                    }

                                    // Determine if the event belongs to the first, second half, or extra time
                                    if ($base_minute <= 45) {
                                        $event_array = &$first_half_events;
                                    } elseif ($base_minute <= 90) {
                                        $event_array = &$second_half_events;
                                    } elseif (($extra_time_enabled) && ($base_minute > 90) && ($base_minute <= 105)) {
                                        $event_array = &$extra_time_fh_events;
                                    } elseif (($extra_time_enabled) && ($base_minute > 105) && ($base_minute <= 120)) {
                                        $event_array = &$extra_time_sh_events;
                                    }

                                    $event_array[] = array(
                                        'minute' => $summed_minute,
                                        'original_string' => $original_string, // Store original string
                                        'type' => $event_type,
                                        'player_id' => $player_id,
                                        'team_id' => $team_id,
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // Sort events by minute for each period
    usort($first_half_events, function ($a, $b) {
        return $a['minute'] - $b['minute'];
    });
    usort($second_half_events, function ($a, $b) {
        return $a['minute'] - $b['minute'];
    });

    if ($extra_time_enabled) {
        usort($extra_time_fh_events, function ($a, $b) {
            return $a['minute'] - $b['minute'];
        });
        usort($extra_time_sh_events, function ($a, $b) {
            return $a['minute'] - $b['minute'];
        });
    }

    // Calculate the length of the first half
    $first_time_length = 45; // Default value
    if (!empty($first_half_events)) {
        $last_event = end($first_half_events);
        if ($last_event['minute'] >= 45) {
            $first_time_length = $last_event['minute'] + 1;
        }
    }

    // Calculate the length of the second half
    $second_time_length = get_post_meta($event_id, 'sp_minutes', true) - 45;
    if ($extra_time_enabled && !empty($second_half_events)) {
        $last_st_event = end($second_half_events);
        if (($last_st_event['minute'] >= 90)) {
            $second_time_length = $last_st_event['minute'] + 1;
        } else {
            $second_time_length = 45;
        }
    } else {
        $second_time_length = 45;
    }

    $total_length = $first_time_length + $second_time_length;

    $total_extra_length = '';
    if ($extra_time_enabled) {
        $first_extra_time_length = 15; // Default value
        if (!empty($extra_time_fh_events)) {
            $last_fe_event = end($extra_time_fh_events);
            if ($last_fe_event['minute'] >= 105) {
                $first_extra_time_length = $last_fe_event['minute'] + 1;
            }
        }

        $second_extra_time_length = get_post_meta($event_id, 'sp_minutes', true) - 105;
        
        $total_extra_length = $first_extra_time_length + $second_extra_time_length;
    }

    // Prepare output
    $output = '<div class="sp-match-timeline">';
    $output .= '<div class="sp-match-hometeam">' . $home_team_abbr . '</div>';
    $output .= '<div class="sp-match-awayteam">' . $away_team_abbr . '</div>';
    $output .= '<div class="sp-match-half sp-match-firsthalf" style="width: calc(' . ($first_time_length / $total_length * 100) . '% - 31px)">';
    foreach ($first_half_events as $event) {
        $output .= render_event($event, $home_team_id, $away_team_id, $first_time_length);
    }
    $output .= '</div>';
    $output .= '<div class="sp-match-halftime">HT</div>';
    $output .= '<div class="sp-match-half sp-match-secondhalf" style="width: calc(' . ($second_time_length / $total_length * 100) . '% - 31px)">';
    foreach ($second_half_events as $event) {
        $output .= render_event($event, $home_team_id, $away_team_id, $second_time_length, 45);
    }
    $output .= '</div>';
    $output .= '</div>';

    // Include extra time timeline if applicable
    if ($extra_time_enabled) {
        $output .= '<div class="sp-match-timeline sp-match-extra-time">';
        $output .= '<div class="sp-match-half sp-match-firstextra" style="width: calc(' . ($first_extra_time_length / $total_extra_length * 100) . '% - 31px)">';
        foreach ($extra_time_fh_events as $event) {
            $output .= render_event($event, $home_team_id, $away_team_id, $first_extra_time_length, 90); // Assuming extra time is 30 minutes
        }
        $output .= '</div>';
        $output .= '<div class="sp-match-halftime">HT</div>';
        $output .= '<div class="sp-match-half sp-match-secondextra" style="width: calc(' . ($second_extra_time_length / $total_extra_length * 100) . '% - 31px)">';
        foreach ($extra_time_sh_events as $event) {
            $output .= render_event($event, $home_team_id, $away_team_id, $second_extra_time_length, 105); // Assuming extra time is 30 minutes
        }
        $output .= '</div>';
        $output .= '</div>';
    }

    // Include necessary CSS
    $output .= '<style>
        .sp-match-timeline {
            position: relative;
            width: 100%;
            height: 65px;
            margin-top: 24px;
            margin-bottom: 24px;
            display: flex;
            z-index: 5;
            justify-content: space-between;
            align-items: center;
        }
        .sp-match-timeline:before {
            content: "";
            position: absolute;
            width: 100%;
            height: 25px;
            top: 50%;
            transform: translateY(-50%);
            background: green;
            border-radius: 12px;
            justify-content: space-between;
            z-index: 5;
        }
        .sp-match-hometeam {
            position: absolute;
            left: 0;
            top: -24px;
            font-family: "Arial Rounded", sans-serif;
        }
        .sp-match-awayteam {
            position: absolute;
            right: 0;
            bottom: -24px;
            font-family: "Arial Rounded", sans-serif;
        }
        .sp-match-half {
            position: relative;
            height: 100%;
            z-index: 6;
        }
        .timeline-event {
            position: absolute;
            top: 0;
            transform: translateX(-50%);
            align-items: center;
            display: flex;
            flex-direction: column;
        }
        .timeline-icon {
            height: 20px;
        }
        .timeline-icon i {
            display: block;
        }
        .timeline-event img {
            width: 24px;
            height: 24px;
            cursor: pointer;
        }
        .event-minute {
            display: block;
            font-size: 12px;
            line-height: 25px;
            color: white;
        }
        .top-icon.bottom-show i,
        .bottom-icon.top-show i,
        .top-icon.bottom-show img,
        .bottom-icon.top-show img {
            display: none;
        }
        .event-info {
            display: none;
            position: absolute;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            border: 1px solid #ccc;
            padding: 5px;
            font-size: 12px;
            z-index: 5;
        }
        .event-minute:hover ~ .event-info,
        .top-icon.top-show:hover ~ .event-info,
        .bottom-icon.bottom-show:hover ~ .event-info {
            display: block;
        }
        .sp-match-halftime {
            width: 50px;
            background: #fff;
            border-radius: 50%;
            border: 1px solid #111;
            color: #111;
            text-align: center;
            line-height: 50px;
            font-size: 24px;
            font-family: "Arial Rounded", sans-serif;
            position: relative;
            z-index: 6;
        }
        .sp-match-extra-time {
            position: relative;
            width: 33%;
            height: 65px;
            margin: 24px auto;
            display: flex;
            z-index: 5;
            justify-content: space-between;
            align-items: center;
        }
        .sp-match-extra-time .sp-match-half {
            height: 100%;
        }
    </style>';

    return $output;
}

function render_event($event, $home_team_id, $away_team_id, $time_length, $offset = 0) {
    $team_id = $event['team_id'];
    $minute = $event['minute'];
    $original_string = $event['original_string'];
    $event_type = $event['type'];
    $event_icon = '';

    switch ($team_id) {
        case $home_team_id:
            $team_position = 'top-show';
            break;
        case $away_team_id:
            $team_position = 'bottom-show';
            break;
        default:
            $team_position = ''; // Default case, no icon found
            break;
    }

    // Invert team position for own goals
    if ($event_type == 'owngoals') {
        $team_position = ($team_position == 'top-show') ? 'bottom-show' : 'top-show';
    }

    // Get the icon or thumbnail for the event type
    switch ($event_type) {
        case 'goals':
            $event_icon = '<i class="sp-icon-soccerball" style="color:#222222 !important"></i>';
            break;
        case 'yellowcards':
            $event_icon = '<i class="sp-icon-card" style="color:#f4d014 !important"></i>';
            break;
        case 'redcards':
            $event_icon = '<i class="sp-icon-card" style="color:#d4000f !important"></i>';
            break;
        case 'owngoals':
            $event_icon = '<i class="sp-icon-soccerball" style="color:#d4000f !important"></i>';
            break;
        default:
            $event_icon = ''; // Default case, no icon found
            break;
    }

    $player_ln = get_post_meta($event['player_id'], 'short_last_name', true);
    $player_name = $player_ln;
    $player_fn = get_post_meta($event['player_id'], 'short_first_name', true);
    if (isset($player_fn) && !empty($player_fn)) {
        $player_name = $player_fn . ' ' . $player_ln;
    }

    $output = '<div class="timeline-event" style="left: ' . (($minute - $offset) / $time_length * 100) . '%;">';
    $output .= '<div class="timeline-icon top-icon ' . $team_position . '">' . $event_icon . '</div>';
    $output .= '<span class="event-minute">' . esc_html($original_string) . '\'</span>';
    $output .= '<div class="timeline-icon bottom-icon ' . $team_position . '">' . $event_icon . '</div>';
    $output .= '<div class="event-info">' . esc_html($player_name) . '</div>';
    $output .= '</div>';

    return $output;
}

// Register the new shortcode
add_shortcode('sp_match_timeline', 'sp_match_timeline_shortcode');
