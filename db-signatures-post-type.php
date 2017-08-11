<?php

if ( !class_exists( 'DB_Signatures_Post_Type' ) ) {
	
	/*
	* DB Signatures Post Type Class to manage the signatures
	*/
	class DB_Signatures_Post_Type {
		
		const POST_TYPE = 'db-signatures';
		
		private $plugin_path = '';
		
		/*
		* Constructor
		*/
		public function __construct() {
		
			// Set plugin path
			$this->plugin_path = dirname(__FILE__) . '/';
		
			// register actions
			add_action( 'init', array( &$this, 'action_init' ));
			add_action( 'add_meta_boxes' , array( &$this, 'add_meta_boxes' ) );
			add_action( 'save_post', array( &$this, 'action_save_post' ) );
			add_action( 'admin_menu', array( &$this, 'rename_core_meta_boxes') );
			add_action( 'admin_menu', array( &$this, 'add_help_page') );
			
			// register filters
			add_filter( 'manage_edit-db-signatures_columns', array( &$this, 'set_columns' ) );
			
		}
		
		/*
		* Get Post Type
		*/
		public function get_post_type() {
		
			return self::POST_TYPE;
			
		}
		
		/*
		* Action Init - init hook
		*/
		public function action_init() {
			
			$this->create_post_type();
			
		}
				
		/*
		* Action Save Post - on post save
		*/
		public function action_save_post( $post_id ) {
			
			$this->save_meta_box_show_on( $post_id );
			
		}
		
		/*
		* Create Post Type
		*/
		public function create_post_type() {
			
			register_post_type( self::POST_TYPE,
				array(
					'labels' => array(
					    'name'               => __( 'DB Signatures', 'db-signatures' ),
					    'singular_name'      => __( 'DB Signature', 'db-signatures' ),
					    'add_new'            => __( 'Add New', 'db-signatures' ),
					    'add_new_item'       => __( 'Add New Signature', 'db-signatures' ),
					    'edit_item'          => __( 'Edit Signature', 'db-signatures' ),
					    'new_item'           => __( 'New Signature', 'db-signatures' ),
					    'all_items'          => __( 'All Signatures', 'db-signatures' ),
					    'view_item'          => __( 'View Signature', 'db-signatures' ),
					    'search_items'       => __( 'Search Signatures', 'db-signatures' ),
					    'not_found'          => __( 'No signatures found', 'db-signatures' ),
					    'not_found_in_trash' => __( 'No signatures found in Trash', 'db-signatures' ),
					    'parent_item_colon'  => '',
					    'menu_name'          => 'DB Signatures'
					),
					'public' => false,
					'show_ui' => true,
					'has_archive' => false,
					'supports' => array( 'title', 'editor' ),
					'taxonomies' => array( 'category', 'post_tag' ),
					'menu_icon' => plugins_url( 'images/icon_post_type.png' , __FILE__ )
				)
			);
			
		}
		
		/*
		* Add Meta Boxes to Post Type
		*/
		public function add_meta_boxes() {
		
			// Add metabox Show On
			add_meta_box( 'db_signatures_mb_show_on', 
					__( 'Show on:', 'db-signatures' ),
					array( &$this, 'meta_box_show_on' ),
					self::POST_TYPE, 
					'side' );
					
		}
		
		/*
		* Metabox Show On - select inside each signature in which post type it will appear
		*/
		public function meta_box_show_on( $post ) {
		
			// get public post types
			$post_types = get_post_types( array( 'public' => true ), 'objects' );
			
			// get post types selected
			$post_types_selected = get_post_meta( $post->ID, '_db_signatures_show_on' );
			
			// set nonce
			wp_nonce_field( 'db_signatures_show_on', 'db_signatures_show_on_nonce' );
			
			// render metabox show on
			include( $this->plugin_path . 'templates/meta_box_show_on.php' );
			
		}
		
		/*
		* Save Metabox Show On
		*/
		public function save_meta_box_show_on( $post_id ) {
		
			// Check if nonce is set and is valid
			if ( !isset( $_POST['db_signatures_show_on_nonce'] ) ) return $post_id;
			$nonce = $_POST[ 'db_signatures_show_on_nonce' ];
			if ( !wp_verify_nonce( $nonce, 'db_signatures_show_on' ) ) return $post_id;

			// Check if autosave
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;

			// Save Metabox Show On
			$show_on = isset( $_POST[ 'db_signatures_post_type' ] ) ? $_POST[ 'db_signatures_post_type' ] : '';
			delete_post_meta( $post_id, '_db_signatures_show_on' );
			if( !empty( $show_on ) ) {
				foreach( $show_on as $post_type ) {
					add_post_meta( $post_id, '_db_signatures_show_on', $post_type );
				}
			}

		}
		
		/*
		* Save Custom Taxonomies
		*/
		public function save_custom_taxonomies( $post_id ) {
		
			// Check post type
		    if (  self::POST_TYPE != $_POST['post_type'] ) {
		        return;
		    }

			// Check if autosave
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;

			// Save Custom Taxonomy: Category
			$cats = wp_get_post_categories( $post_id, array( 'fields' => 'slugs' ) );
			wp_set_object_terms( $post_id, $cats, 'db_signatures_cat' );

		}

		
		/*
		* Rename Core Meta Boxes
		*/
		public function rename_core_meta_boxes() {
			global $submenu;
			
			// first remove core metabox
			remove_meta_box( 'categorydiv', 'db-signatures', 'side' );
			remove_meta_box( 'tagsdiv-post_tag', 'db-signatures', 'side' );

			// add it again with the new name
			add_meta_box( 'categorydiv', 
					__('Hide on Categories:', 'db-signatures'),
					'post_categories_meta_box', 
					'db-signatures', 
					'side', 
					'low');
			add_meta_box( 'tagsdiv-post_tag', 
					__('Hide on Tags:', 'db-signatures'), 
					'post_tags_meta_box', 
					'db-signatures', 
					'side', 
					'low');

			// hide it from submenu
			remove_submenu_page( 'edit.php?post_type=db-signatures', 'edit-tags.php?taxonomy=category&amp;post_type=db-signatures' );
			remove_submenu_page( 'edit.php?post_type=db-signatures', 'edit-tags.php?taxonomy=post_tag&amp;post_type=db-signatures' );
			
			
		}
		
		/*
		* Add Help Page
		*/
		public function add_help_page() {
			add_submenu_page( 
				'edit.php?post_type=db-signatures', 
				__( 'Help', 'db-signatures' ),
				__( 'Help', 'db-signatures' ),
				'edit_posts',
				'db-signatures-help',
				array( &$this, 'show_help' )
			);
		}
		
		/*
		* Show Help Page
		*/
		public function show_help() {
			
			// render help page
			include( $this->plugin_path . 'templates/help_page.php' );
			
		}
		
		public function set_columns( $columns ) {
			$columns['categories'] = __( 'Hide on Cats', 'db-signatures' );
			$columns['tags'] = __( 'Hide on Tags', 'db-signatures' );
			
			return $columns;
		}
		
	}
	
	
}