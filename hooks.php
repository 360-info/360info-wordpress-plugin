<?php

/**
  * Plugin Name:       360infoWordpressPlugin
  * Plugin URI:        https://github.com/360-info/360info-wordpress-plugin
  * Description:       Additional tools to drive content publishing between
  *                    Superdesk and the 360info Wordpress site
  * Version:           0.0.1
  * Requires at least: 5.8.2
  * Requires PHP:      7.2
  * Author:            James Goldie
  * Author URI:        https://jamesgoldie.dev
  * Text Domain:       360info-wordpress-plugin
*/

/* tasks:

- convert posts with Category:Special Report to the Feature post type
  - and remove the category afterward to keep things tidy
  - alternatively, the ninjs does have $obj['profile'] == "SRarticle" for framing
    articles (based on the template in superdesk, maybe?). if we're modifying
    the sd wp plugin (see below), we could map this to sth useful and use that to
    catch these. but maybe it's much of a muchness.
    - plugin already supports converting profiles to a post FORMAT, but Features are
      implemented as a post TYPE. i don't think post formats beyond the ones included
      are a possibility. maybe we just modify the existing plugin code here...

- (done) convert sluglines to a taxonomy
  - superdesk plugin does (if enabled in admin - it isn't currently) map sluglines to wp tags.
    i recommend a structured slugline (eg. "sr:Final Frontiers")
    so we can disambiguate them from other tags, add to the taxonomy, remove the tag

- (done) also convert sluglines to wp highlight labels (purely aesthetic)

- correctly map structured author/edito info in superdesk to person/institution in wp
  - confirmed that this info is transmitted in ninjs, so sd wp plugin could be modified to map it:
    - $obj['authors'] is an array of {code, name, role, biography} objects
  - in wordpress, there are free text author/editor boxes that we're using as fallback (byline maps
    to authors), but the structured author/edit stuff is under Credits, which is a Repeater component
    using the Advanced Custom Fields Plugin (https://www.advancedcustomfields.com/resources/repeater).
    the repeater stores in array of editors and an array of editors. each is a link to a Person post type
    and an Institution post type. the latter defaults to the Person's institution.
  - the question is probably, do we modify the sd wp plugin to:
    a. Map $obj['authors'] to something neutral and accessible in wp in a plugin free way (so that others can benefit),
       and then have a supplementary plugin or theme fn to map *that* to our ACFPro fields, or
    b. Just have our sd wp plugin fork do the whole thing.
*/

$sr_taxonomy_key = 'specialreport';
$sr_taxonomy_singular = 'Special Report';
$sr_taxonomy_plural = 'Special Reports';

/* register_sr_taxonomy:
    - creates a new taxonomy with the above specified key and labels
*/
function register_sr_taxonomy() {
  error_log('DUMMY 360INFO: registering :' . $sr_taxonomy_key . '" taxonomy');
  // register_taxonomy($sr_taxonomy_key,
  //     array('post', 'feature'),
  //     array(
  //       'public' => true,
  //       'hierarchical' => false,
  //       'labels' => array(
  //         'name' => $sr_taxonomy_plural,
  //         'singular_name' => $sr_taxonomy_singular,
  //         'menu_name' => $sr_taxonomy_singular,
  //         'all_items' => 'All ' . $sr_taxonomy_plural,
  //         'view_item' => 'View ' . $sr_taxonomy_singular,
  //         'update_item' => 'Update ' . $sr_taxonomy_singular,
  //         'add_new_item' => 'Add New ' . $sr_taxonomy_singular,
  //         'new_item_name' => 'New ' . $sr_taxonomy . ' Name',
  //         'search_items' => 'Search ' . $sr_taxonomy_plural,
  //         'popular_items' => 'Popular ' . $sr_taxonomy_plural,
  //         'separate_items_with_commas' =>
  //           'Separate ' . $sr_taxonomy_plural . ' with comms',
  //         'add_or_remove_items' => 'Add or remove ' . $sr_taxonomy_plural,
  //         'choose_from_most_used_items' =>
  //           'Choose from most used ' . $sr_taxonomy_plural,
  //         'not_found' => 'No ' . $sr_taxonomy_plural . ' found.',
  //         'back_to_items' => '← Back to' . $sr_taxonomy_plural,
  //       ))
  //   );
    error_log('DUMMY 360INFO: registered :' . $sr_taxonomy_key . '" taxonomy');
}

/* process_sr_tags:
    - converts post tags beginning with "SR:" to terms in a custom taxonomy
      (creating the taxonomy and term as required). strips those tags once it's
      done.
    - also adds (or updates) a highlight label based on those "SR:" tags
*/
function process_sr_tags($post_ID, $post, $update) {
     
  error_log(
    'DUMMY 360INFO: running hook "process_sr_tags" on post ' . $post_ID);

  # first, create the SR taxonomy if it doesn't exist
  # (wp_set_post_terms will do this for us, but manually doing it lets us
  # set the labels up nicely)
  if (!taxonomy_exists($sr_taxonomy_key)) {
    error_log('DUMMY 360INFO: SR taxonomy not detected');
    register_sr_taxonomy();
  } else {
    error_log('DUMMY 360INFO: SR taxonomy detected!');
  }

  # locate any "SR:" tags and use them to add the post
  # to a custom taxonomy for special reports. also make it the
  # highlight label?
  $post_tags = wp_get_post_tags($post_ID);
  error_log(
    'DUMMY 360: tags for post ' . $post_ID . ': ' . implode(', ', $post_tags));
  $sr_tag_prefix = 'SR:';
  function is_sr_tag($tag) {
    return(str_starts_with($tag['name'], $sr_tag_prefix));
  }
  $sr_post_tags = array_filter($post_tags, $is_sr_tag);

  if (empty($sr_post_tags)) {
    error_log('DUMMY 360: no SR tags for post ' . $post_ID . '. Returning!');
    return();
  }

  error_log('DUMMY 360: SR tags found for post ' . $post_ID .
    ': ' . implode(', ', $post_tags));

  # 1. extract the rest of the sr tag names
  # (strip commas too, as they're used by wp_set_post_terms)
  function remove_prefix_and_commas($tag) {
    $sr_name = str_replace($tag['name'], $sr_tag_prefix, '');
    $sr_name = str_replace($sr_name, ',', '');
    return($sr_name);
  }
  $sr_names = array_map($remove_prefix_and_commas, $sr_post_tags);

  error_log('DUMMY 360: adding SR taxonomy term "' .
    implode(', ', $post_tags) . '" for post ' . $post_ID);

  # 2. add the SR names as terms to the post (creating the terms if needed)
  # wp_set_post_terms($post_ID, $sr_names, $sr_taxonomy_key);

  # 3. remove the tags from the post
  # NOTE - not 100% sure about object comparison in php! will this work?
  $other_tags = array_diff($post_tags, $sr_post_tags);

  if (empty($other_tags)) {
    error_log('DUMMY 360: no remaining tags on post ' . $post_ID
  } else {
    error_log('DUMMY 360: setting remaining tags on post ' . $post_ID .
      ': ' . implode(', ', $other_tags));
  }

  # wp_set_post_tags($post_ID, $other_tags);

  # 4. create (or update) the highlight label for the post
  # NOTE - should the highlight label for a framing article simply be
  #   "SPECIAL REPORT"?
  $sr_hl_label = implode(',', $sr_names);   # uppercase? or is that styled?
  $hl_label_key = 'highlight_label';
  if (get_post_meta($post_ID, $hl_label_key)) {
    error_log('DUMMY 360: updating highlight label on post ' . $post_ID .
      ' to ' . $sr_hl_label);
    # update_post_meta($post_ID, $hl_label_key, $sr_hl_label);
  } else {
    error_log('DUMMY 360: adding highlight label on post ' . $post_ID .
      ' to ' . $sr_hl_label);
    # add_post_meta( $post_ID, $hl_label_key, $sr_hl_label);
  }

  error_log(
    'DUMMY 360INFO: exiting hook "process_sr_tags" on post ' . $post_ID);
}

/* framing_to_feature:

  if a post has the category "Special Report":
    - change its post type to SR
    - remove the SR category
    - (?) locate other articles with the same SR term, get all their authors
      and add them all to this feature

  this fn should be set to run AFTER process_sr_tags so that it is in the
  correct taxonomy.
*/
function framing_to_feature($post_ID, $post, $update) {

  error_log(
    'DUMMY 360INFO: running hook "framing_to_feature" on post ' . $post_ID);

  # check it's a framing article
  if(get_post_type($post_ID) == 'post' and
    (!has_category('Special Report', $post_ID))) {
      error_log(
        'DUMMY 360INFO: post ' . $post_ID . ' is not a framing article. ' .
        'Returning');
      return();
  }

  # convert to a feature
  # https://developer.wordpress.org/reference/functions/set_post_type
  error_log(
    'DUMMY 360INFO: setting post ' . $post_ID . ' to "feature" post type');
  # set_post_type($post_id, 'feature')
  
  # update other post metadata
  # update_post_meta($post_id, ...);

  error_log(
    'DUMMY 360INFO: exiting hook "framing_to_feature" on post ' . $post_ID);

}

/* pool_article_authors_to_framing:

  if a post DOES NOT have the SR category:
    - check to see if there is a feature in the same taxonomy
      - if there is, add this article's authors to it
*/
function pool_article_authors_to_framing($post_ID) {

  error_log(
    'DUMMY 360INFO: running hook "pool_article_authors_to_framing" on post ' .
    $post_ID);
    
  # check it's a non-framing article
  if (get_post_type($post_ID) == 'feature' or
    has_category('Special Report', $post_ID)) {
      error_log('DUMMY 360INFO: exiting since post ' . $post_ID .
        ' is a framing article');
    return();
  }

  # check the taxonomy exists
  if (!taxonomy_exists($sr_taxonomy_key)) {
    error_log('DUMMY 360INFO: SR taxonomy not detected. This should happen ' .
      'automatically! Report this bug to the developer. Exiting hook');
    return();
  }

  # check to see if there is a feature in the same taxonomy
  $post_sr = get_the_terms($post_ID, $sr_taxonomy_key);

  if (empty($post_sr)) {
    error_log('DUMMY 360INFO: no SR attached to post ' . $post_ID .
      '. Exiting hook');
    return()
  }

  if (count($post_sr) > 1) {
    error_log('DUMMY 360INFO: multiple SRs attached to post ' . $post_ID .
      ': ', implode($post_sr));
    return();
  }

  # find the framing article(s) for this sr, if it already exists
  error_log('DUMMY 360INFO: querying DB for framing articles in the SR ' .
    $post_sr['slug'] . '(' . $post_sr['name'] . ')');
  $framing_query = new WP_Query(array(
    'post_type' => 'feature',
    'tax_query' => array(
      array(
        'taxonomy' => $sr_taxonomy_key,
        'field' => 'slug',
        'terms' => $post_sr['slug']
      )
    ),
    'fields' => 'ids'
  ));

  # check there are published framing articles to which to pool
  // if (!($framing_query->have_posts())) {
  //   return();
  // }

  /* extract_contributors:

    extract the authors from the current article
    (custom fields using https://www.advancedcustomfields.com/resources)
  */
  function extract_contributors($post_ID) {
    $contributor_groups = array();
    if (have_rows('contributor_groups', $post_ID)) {
      while (have_rows('contributor_groups', $post_ID)) {
        the_row();
        $group_label = get_sub_field('label');
        $contributor_groups[$group_label] = array();
        if (have_rows('contributors', $post_ID)) {
          while (have_rows('contributors', $post_ID)) {
            the_row();
            # add a person and org to the arrays for the group
            $contributor_groups[$group_label][] = array(
              'person' => get_sub_field('person', $post_ID),
              'institution' => get_sub_field('institution', $post_ID)
            );
          }
        }
      }
    }
    return(contributor_groups);
  }

  # $article_contributors = extract_contributors($post_ID);
  
  # then, load them into each framing article
  # $framing_posts = $framing_query->posts;
  // foreach ($framing_posts as $framing_post) {
    
  //   $framing_contributors = extract_contributors($framing_post);

  //   # foreach($contrib_group as )
    
  //   // get_field()
  //   // get_sub_field()
  //   // get_row()
  //   // update_row()
  //   // update_field()
  //   // update_subfield()

  // }
    
}

# register these hooks
error_log('360INFO: registering plugin hooks');
register_activation_hook(__FILE__, 'register_sr_taxonomy');
add_action('save_post', 'process_sr_tags', 20, 3);
# add_action('save_post', 'framing_to_feature', 100, 3);
# add_action('save_post', 'pool_article_authors_to_framing', 100, 3);
