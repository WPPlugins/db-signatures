<?php
/**
 * Plugin Name: DB Signatures
 * Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
 * Description: Add some HTML content to the bottom of every posts, pages and custom post types.
 * Version: 1.0
 * Author: David Beja
 * Author URI: http://dbeja.com
 * License: GPL2
 */
 
 /*  Copyright 2013  David Beja  (email : david.beja@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( !class_exists('DB_Signatures') ) {
	
	class DB_Signatures {
	
		private $db_signatures_post_type;
		private $plugin_path = '';
		
		/*
		* Constructor
		*/
		public function __construct() {
		
			// Set plugin path
			$this->plugin_path = dirname(__FILE__) . '/';
			
			// Register post type
			require_once( $this->plugin_path . 'db-signatures-post-type.php' );
			$this->db_signatures_post_type = new DB_Signatures_Post_type();
			
			// Register actions
			add_action( 'add_meta_boxes' , array( &$this, 'add_meta_boxes' ) );
			add_action( 'save_post', array( &$this, 'action_save_post' ) );
			
			// Register filters
			add_filter( 'the_content', array( &$this, 'show_signature' ), 10 );
			
		}
		
		/*
		* Activate the plugin
		*/
		public static function activate() {}
		
		/*
		* Deactive the plugin
		*/
		public static function deactivate() {}
		
		/*
		* Action Save Post - on post save
		*/
		public function action_save_post( $post_id ) {
			
			$this->save_meta_box_choose_signature( $post_id );
			
		}
		
		/*
		* Add Meta Boxes to Posts
		*/
		public function add_meta_boxes() {
		
			// Get all post types
			$post_types = get_post_types( array(
					'public' => true,
					'_builtin' => false
			), 'names');
			
			// Add post and page post types
			array_push( $post_types, "post", "page" );
			
			// Foreach post type add meta box
			foreach( $post_types as $post_type ) {
				add_meta_box( 
					'db_signatures_mb_signature',
					__( 'Choose Signature', 'db-signatures' ),
					array( &$this, 'metabox_choose_signature' ),
					$post_type,
					'side' );
			}

		}
		
		/*
		* Metabox Choose Signature - select signature for current post: none; fixed; random
		*/
		public function metabox_choose_signature( $post ) {
		
			// get signature selected
			$signature_selected = get_post_meta( $post->ID, '_db_signatures_signature', true );
			
			// get all signatures
			$signatures = new WP_Query( array(
				'post_status' 	=> 'publish',
				'post_type' 	=> $this->db_signatures_post_type->get_post_type(),
				'nopaging' 		=> true,
				'orderby' 		=> 'title',
				'order' 		=> 'ASC'
			));
						
			// set nonce
			wp_nonce_field( 'db_signatures_signature', 'db_signatures_signature_nonce' );
			
			// render metabox show on
			include( $this->plugin_path . 'templates/meta_box_choose_signature.php' );
			
		}
		
		/*
		* Save Metabox Choose Signature
		*/
		public function save_meta_box_choose_signature( $post_id ) {
		
			// Check if nonce is set and is valid
			if ( !isset( $_POST['db_signatures_signature_nonce'] ) ) return $post_id;
			$nonce = $_POST[ 'db_signatures_signature_nonce' ];
			if ( !wp_verify_nonce( $nonce, 'db_signatures_signature' ) ) return $post_id;

			// Check if autosave
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;

			// Save Metabox Choose Signature
			$signature = $_POST[ 'db_signatures_signature' ];
			delete_post_meta( $post_id, '_db_signatures_signature' );
			if( !empty( $signature ) ) {
				update_post_meta($post_id, '_db_signatures_signature', $signature);
			}

		}
		
		/*
		* Show Signature
		*/
		public function show_signature( $content ) {
		
			$signature = '';
			$signature_contents = '';
			
			// only for singular post items
			if ( is_singular() && !is_front_page() ) {

				// get signature selection
				$signature_selected = get_post_meta( get_the_ID(), '_db_signatures_signature', true );
				switch( $signature_selected ) {

					case 'disabled': return $content;  // signatures disabled

					case 'random': case '':  // random signature, default selection

						$current_post_type = get_post_type();
						$categories = wp_get_object_terms( get_the_ID(), 'category', array( 'fields' => 'ids' ) );
						$tags = wp_get_object_terms( get_the_ID(), 'post_tag', array( 'fields' => 'ids' ) );
						
						$query = array(
							'post_status' 		=> 'publish',
							'post_type' 		=> $this->db_signatures_post_type->get_post_type(),
							'posts_per_page' 	=> '1',
							'orderby' 			=> 'rand',
							'meta_key'	 		=> '_db_signatures_show_on',
							'meta_value' 		=> $current_post_type
						);
						if( count( $categories ) > 0 ) $query['category__not_in'] = $categories;
						if( count( $tags ) > 0 ) $query['tag__not_in'] = $tags;
						
						$signatures = new WP_Query( $query );
						
						while( $signatures->have_posts() ) {
							$signatures->next_post();
							$signature_contents = $signatures->post->post_content;
						}
						wp_reset_postdata();
						
						break;
						
					default:  // a certain signature
						$signature_post = get_post( $signature_selected );
						$signature_contents = $signature_post->post_content;
						break;
				}
				
				// output buffering of show signature template
				ob_start();
				include( $this->plugin_path . 'templates/show_signature.php' );
				$signature = ob_get_clean();
			
			}

			return $content . $signature;
				
		}

		
	}
	
}

if ( class_exists('DB_Signatures') ) {
	
	// Activation and Deactivation hooks
	register_activation_hook( __FILE__, array( 'DB_Signatures', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'DB_Signatures', 'deactivate' ) );
	
	// Instantiate class
	$db_signatures = new DB_Signatures();

}