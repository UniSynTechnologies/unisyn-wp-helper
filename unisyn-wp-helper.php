<?php
/**
  Plugin Name: UniSyn WP Helper
  Plugin URI:
  Description: A collection of helpers and customizations universal to UniSyn hosted sites
  Author: UniSyn Technologies
  Version: 1.0
  Author URI: https://github.com/UniSynTechnologies
 **/

if (!class_exists('UniSynWPHelper')) {
  class UniSynWPHelper {
    protected static $_instance = null;

    public static function instance() {
      if ( is_null( self::$_instance ) ) {
        self::$_instance = new self();
      }

      return self::$_instance;
    }

    /* Auto Load Hooks */
    public function __construct() {
      add_action( 'init', array($this, ', PHP_INT_MAX') );

      add_filter('upload_mimes', array($this, 'mime_types'), 1, 1);

      add_filter( 'editable_roles', array(&$this, 'editable_roles'));
      add_filter( 'map_meta_cap', array(&$this, 'map_meta_cap'),10,4);
    }

    /*
    * Prevent video uploads to media library
    * Not trying to be a video hosting service. Use Youtube or something
    */
    public function mime_types($mime_types){
      unset($mime_types['mp4|m4v']);
      unset($mime_types['mov|qt']);
      unset($mime_types['mpeg|mpg|mpe']);
      unset($mime_types['ogv']);
      unset($mime_types['webm']);
      unset($mime_types['avi']);
      return $mime_types;
    }

    /*
    * Let Editors manage users
    */
    public function editor_manage_users() {
      if ( get_option( 'unisyn_add_cap_editor_once' ) != 'done' ) {
        $edit_editor = get_role('editor'); // Get the user role
        $edit_editor->add_cap('edit_users');
        $edit_editor->add_cap('list_users');
        $edit_editor->add_cap('promote_users');
        $edit_editor->add_cap('create_users');
        $edit_editor->add_cap('add_users');
        $edit_editor->add_cap('delete_users');

        update_option( 'unisyn_add_cap_editor_once', 'done' );
      }
    }

    /*
    * Prevent editor from deleting, editing, or creating an administrator
    * only needed if the editor was given right to edit users
    */
    // Remove 'Administrator' from the list of roles if the current user is not an admin
    public function editable_roles( $roles ){
      if( isset( $roles['administrator'] ) && !current_user_can('administrator') ){
        unset( $roles['administrator']);
      }
      return $roles;
    }
    // If someone is trying to edit or delete an
    // admin and that user isn't an admin, don't allow it
    public function map_meta_cap( $caps, $cap, $user_id, $args ){
      switch( $cap ){
        case 'edit_user':
        case 'remove_user':
        case 'promote_user':
          if( isset($args[0]) && $args[0] == $user_id )
            break;
          elseif( !isset($args[0]) )
            $caps[] = 'do_not_allow';
            $other = new WP_User( absint($args[0]) );
            if( $other->has_cap( 'administrator' ) ) {
              if(!current_user_can('administrator')) {
                  $caps[] = 'do_not_allow';
              }
            }
          break;
        case 'delete_user':
        case 'delete_users':
          if( !isset($args[0]) )
            break;
          $other = new WP_User( absint($args[0]) );
          if( $other->has_cap( 'administrator' ) ) {
            if(!current_user_can('administrator')) {
              $caps[] = 'do_not_allow';
            }
          }
          break;
        default:
          break;
      }
      return $caps;
    }

  }

  function unisyn_wp_helper() {
    return UniSynWPHelper::instance();
  }

  add_action( 'plugins_loaded', 'unisyn_wp_helper', 99 );
}
