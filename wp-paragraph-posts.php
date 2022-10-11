<?php
/*---------------------------------------------------------
Plugin Name: WP Paragraph Posts
Author: carlosramosweb
Author URI: https://criacaocriativa.com
Donate link: https://donate.criacaocriativa.com
Description: Esse plugin é uma versão BETA. Gerar os Paragrafos Automaticamente no posts do WordPress.
Text Domain: wp-paragraph-posts
Domain Path: /languages/
Version: 3.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html 
------------------------------------------------------------*/

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}


if ( ! class_exists( 'WP_Paragraph_Posts' ) ) {

	class WP_Paragraph_Posts {

		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'init_functions' ) );
			register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
			//register_deactivation_hook( __FILE__, array( $this, 'deactivate_plugin' ) );
		}
		//=>

		public function init_functions() {
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_links_settings' ) );
			add_action( 'admin_menu', array( $this, 'register_menu_item_admin' ), 10, 2 );
			add_action( 'init', array( $this, 'generate_paragraph_post_link' ) );
			add_action( 'init', array( $this, 'formatting_paragraph_post_link' ) );

			$settings 	= get_option( 'wp_paragraph_posts_settings' );
			$enabled 	= esc_attr( $settings['enabled'] );
			$post_type 	= esc_attr( $settings['post_type'] );

			if ( $enabled == 'yes' ) {
				if ( $post_type == 'post' || $post_type == '' ) {
					add_filter( 'post_row_actions', array( $this, 'paragraph_post_link' ), 10, 2 );
					add_filter( 'bulk_actions-edit-post', array( $this, 'register_generate_paragraph_bulk_actions' ) );
					add_filter( 'handle_bulk_actions-edit-post', array( $this, 'generate_paragraph_bulk_action_handler' ), 10, 3 );
					add_filter( 'handle_bulk_actions-edit-post', array( $this, 'formatting_paragraph_bulk_action_handler' ), 10, 3 );
				} else {
					add_filter( 'page_row_actions', array( $this, 'paragraph_post_link' ), 10, 2 );
					add_filter( 'bulk_actions-edit-page', array( $this, 'register_generate_paragraph_bulk_actions' ) );
					add_filter( 'handle_bulk_actions-edit-page', array( $this, 'generate_paragraph_bulk_action_handler' ), 10, 3 );
					add_filter( 'handle_bulk_actions-edit-page', array( $this, 'formatting_paragraph_bulk_action_handler' ), 10, 3 );
				}				
				add_action( 'admin_notices', array( $this, 'generate_paragraph_bulk_action_admin_notice' ) );
				add_action( 'add_meta_boxes', array( $this, 'paragraph_register_meta_boxes' ) );
			}					
		}
		//=>

		public function paragraph_register_meta_boxes() {
			$settings 	= get_option( 'wp_paragraph_posts_settings' );
			$enabled 	= esc_attr( $settings['enabled'] );
			$post_type 	= esc_attr( $settings['post_type'] );

			if ( $enabled == 'yes' ) { 
			    add_meta_box( 
			    	'meta-box-paragraph', 
			    	'Gerar Paragrafos', 
			    	array( $this,'paragraph_meta_boxe_display_callback' ),
			    	'' . $post_type . '',
			    	'side',
			    	'core'
			    );
			}
		}
		//=>

		public function paragraph_meta_boxe_display_callback( $post ) { 
			$settings 			= get_option( 'wp_paragraph_posts_settings' );
			$formatting_enabled = esc_attr( $settings['formatting_enabled'] );

			$wpnonce 		= esc_attr( wp_create_nonce() );
			$generate_url 	= "post.php?post={$post->ID}&edit=page-edit&action=generate-paragraph&_wpnonce={$wpnonce}";
			$formatting_url = "post.php?post={$post->ID}&edit=page-edit&action=formatting-paragraph&_wpnonce={$wpnonce}";
			$disabled = '';
			if ( empty( $post->post_content ) ) {
				$disabled = 'disabled=""';
			}
			?>
			<div id="generate-paragraph-action" style="margin-bottom: 20px;">
				<p class="howto">Clique para Gerar os Paragrafos Automaticamente.</p>
				<span class="spinner"></span>
				<a href="<?php echo admin_url( $generate_url );?>" id="generate-paragraph" class="button button-primary button-large" <?php echo $disabled; ?>>
					Gerar Paragrafos
				</a>
			</div>
			<?php if ( $formatting_enabled == "yes" ) { ?>
			<hr/>
			<div id="formatting-paragraph-action">
				<p class="howto">Clique para Formatar os Paragrafos Automaticamente.</p>
				<span class="spinner"></span>
				<a href="<?php echo admin_url( $formatting_url );?>" id="formatting-paragraph" class="button button-primary button-large" <?php echo $disabled; ?>>
					Formatar Paragrafos
				</a>
			</div>
			<?php } ?>
			<?php
		}
		//=>

		public function plugin_links_settings( $links ) {
			$action_links = array(
				'settings' => '<a href="' . admin_url( 'admin.php?page=wp-paragraph-posts' ) . '" title="Configuracões" class="edit">Configuracões</a>',
				'donate' 	=> '<a href="' . esc_url( 'https://donate.criacaocriativa.com' ) . '" title="Doar para o autor do plugin" class="error" target="_blank">Doação</a>',
			);
			return array_merge( $action_links, $links );
		}
		//=>

		public function register_menu_item_admin() {
			add_menu_page(
		        'Paragraph Posts',
		        'Paragraph Posts',
		        'manage_options',
		        'wp-paragraph-posts',
		        array( $this, 'page_admin_settings_callback' ),
		        'dashicons-admin-appearance',
		        5
		    );
		}
		//=>

		public function register_generate_paragraph_bulk_actions( $bulk_actions ) {
			$settings 			= get_option( 'wp_paragraph_posts_settings' );
			$formatting_enabled = esc_attr( $settings['formatting_enabled'] );

		  	$bulk_actions['generate_paragraph_bulk'] = "Gerar Paragrafos";
		  	if ( $formatting_enabled == "yes" ) {
		  		$bulk_actions['formatting_paragraph_bulk'] = "Formata Paragrafos";
		  	}
		  	return $bulk_actions;
		}
		//=>
		 
		public function generate_paragraph_bulk_action_admin_notice() {
			if ( ! empty( $_REQUEST['bulk_generate_paragraph'] ) ) {
				$paragraph_count = intval( $_REQUEST['bulk_generate_paragraph'] );
				printf( '<div id="message" class="updated notice notice-success is-dismissible">' .	_n( '<p>Foi gerado %s post.</p>', '<p>Foram gerados %s posts.</p>', $paragraph_count, 'bulk_generate_paragraph' ) . '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dispensar este aviso.</span></button></div>', $paragraph_count );
			}
		}
		//=>

		public function formatting_paragraph_bulk_action_handler( $redirect_to, $doaction, $post_ids ) {
			if ( $doaction !== 'formatting_paragraph_bulk' ) {
				return $redirect_to;
			}
			foreach ( $post_ids as $post_id ) {
				$settings 	= get_option( 'wp_paragraph_posts_settings' );
				$this->formatting_paragraph_post( $post_id );
			}

			$redirect_to = add_query_arg( 'bulk_formatting_paragraph', count( $post_ids ), $redirect_to );
			return $redirect_to;
		}
		//=>

		public function formatting_paragraph_post( $post_id ) {
			if ( $post_id > 0 ) {
				$settings 					= get_option( 'wp_paragraph_posts_settings' );
				$post   					= get_post( $post_id );
				$formatting_characters 		= intval( esc_attr( $settings['formatting_characters'] ) );
				$formatting_character_last 	= esc_attr( $settings['formatting_character_last'] );
				$the_content 				= apply_filters( 'the_content', $post->post_content );

				//$post_content 	= strip_tags( $the_content, "<img><h3><a><b><i><strong>" );
				$post_content 	= strip_tags( $the_content, "" );
				$post_content 	= str_replace( "&nbsp;", " ", $post_content );
				$post_content 	= str_replace( "<br>", "\n", $post_content );
				$post_content 	= str_replace( "<br/>", "\n", $post_content );
				$post_content 	= str_replace( "\n\n\n", "\n\n", $post_content );
				$post_content 	= str_replace( "\n\n", "\n", $post_content );
				$post_content 	= str_replace( "\n", " ", $post_content );
				$post_content 	= str_replace( "?", "?.", $post_content );
				$post_content 	= str_replace( "!", "!.", $post_content );
				$post_content 	= explode( ".", $post_content );

				$new_content 	= "";
				$count      	= 0;
				foreach ( $post_content as $key => $content ) {
					if ( $content != "" ) {
						$content 	 = trim( str_replace( "?.", "?", $content ) );
						$content 	 = trim( str_replace( "!.", "!", $content ) );

						$count 	   += strlen( $content );

						if ( substr( $content, -1 ) != "." && 
							substr( $content, -1 ) != "?" && 
							substr( $content, -1 ) != "!" ) {
							if ( $count >= $formatting_characters ) {
								$count = 0;
								$new_content .= $content . $formatting_character_last . "\n\n";
							} else {
								$new_content .= $content . $formatting_character_last . " ";
							}
						} else {
							$new_content .= $content . " ";
						}
					}
				}

				$update = array(
				  'ID'           => $post_id,
				  'post_content' => $new_content,
				);				 
				wp_update_post( $update );
			}
		}
		//=>
 
		public function generate_paragraph_bulk_action_handler( $redirect_to, $doaction, $post_ids ) {
			if ( $doaction !== 'generate_paragraph_bulk' ) {
				return $redirect_to;
			}
			foreach ( $post_ids as $post_id ) {
				$settings 	= get_option( 'wp_paragraph_posts_settings' );
				$this->generate_paragraph_post( $post_id );
			}

			$redirect_to = add_query_arg( 'bulk_generate_paragraph', count( $post_ids ), $redirect_to );
			return $redirect_to;
		}
		//=>

		public function generate_paragraph_post( $post_id ) {
			if ( $post_id > 0 ) {
				$settings 		= get_option( 'wp_paragraph_posts_settings' );
				$post   		= get_post( $post_id );
				$character 		= esc_attr( $settings['character'] );
				$number 		= esc_attr( $settings['number'] );
				$the_content 	= apply_filters( 'the_content', $post->post_content );

				$class = esc_attr( $settings['class'] );

				$post_content 	= strip_tags( $the_content, "<img><h3><a><b><i><strong>" );
				$post_content 	= str_replace( "<h3 class='" . $class . "'>", "", $post_content );
				$post_content 	= str_replace( "</h3>", "" . $character . "", $post_content );
				$post_content 	= str_replace( "&nbsp;", " ", $post_content );
				$post_content 	= str_replace( "\n\n\n", "\n\n", $post_content );
				$post_content 	= str_replace( "\n\n", "\n", $post_content );
				//$post_content 	= nl2br( $post_content );
				$post_content 	= explode( "\n", $post_content );

				$new_content 	= "";
				foreach ( $post_content as $key => $content ) {
					if ( $key % $number == "0" ) {
						$paragraph 	= trim( strstr( $content, $character, true ) );
						$img 		= trim( strstr( $content, "<img" ) );

						if ( $img != "" ) {
							$img			=  trim( strip_tags( $img, "<img><a>" ) );
							$new_content 	.= $img;
							$content 		= str_replace( $img, "", $content );
						} else if ( $img == "" &&  $paragraph != "" ) {
							$paragraph 		= strip_tags( $paragraph );
							$new_content 	.= "<h3 class='" . $class . "'>" . $paragraph . "</h3>";
						} 

						$content = strstr( $content, $character );
						$content = trim( substr( $content, 1, strlen( $content ) ) );
						$content = trim( str_replace( "<br>", "", $content ) );	

						if ( $content != "" && $content != "." ) {
							$content 		= strip_tags( $content, "<a><b><i><strong>" );
							$new_content 	.= "<p>" . $content . "</p>";
						}
					} else {
						$content = trim( str_replace( "<br>", "", $content ) );
						if ( $content != "" && $content != "." ) {
							$new_content .= "<p>" . $content . "</p>";
						}
					}

				}

				$update = array(
				  'ID'           => $post_id,
				  'post_content' => $new_content,
				);				 
				wp_update_post( $update );
			}
		}
		//=>

		public function formatting_paragraph_post_link() {
			if ( is_admin() && isset( $_GET['post'] ) && isset( $_GET['action'] ) ) {
				if ( $_GET['post'] > 0 && $_GET['action']  == "formatting-paragraph" ) {

					$settings 			= get_option( 'wp_paragraph_posts_settings' );
					$enabled 			= esc_attr( $settings['enabled'] );
					$formatting_enabled = esc_attr( $settings['formatting_enabled'] );
					$post_type 			= esc_attr( $settings['post_type'] );
					$wpnonce 			= esc_attr( wp_create_nonce() );
					$post_id 			= esc_attr( $_GET['post'] );

				    if ( $enabled == 'yes' && $formatting_enabled == 'yes' && $wpnonce == $_GET['_wpnonce'] ) { 
				    	$this->formatting_paragraph_post( $post_id );
				    	if ( isset( $_GET['edit'] ) && isset( $_GET['edit'] ) == "page-edit" ) {
							$edit_url 	= "post.php?post={$post_id}&action=edit&_wpnonce={$wpnonce}";
				    		wp_redirect( admin_url( $edit_url ) );
							exit();
				    	} else {
							$edit_url 	= "edit.php?post_type={$post_type}";
				    		wp_redirect( admin_url( $edit_url ) );
							exit();
				    	}
				    }
				}
			}
		}
		//=>



		public function generate_paragraph_post_link() {
			if ( is_admin() && isset( $_GET['post'] ) && isset( $_GET['action'] ) ) {
				if ( $_GET['post'] > 0 && $_GET['action']  == "generate-paragraph" ) {

					$settings 	= get_option( 'wp_paragraph_posts_settings' );
					$enabled 	= esc_attr( $settings['enabled'] );
					$post_type 	= esc_attr( $settings['post_type'] );
					$wpnonce 	= esc_attr( wp_create_nonce() );
					$post_id 	= esc_attr( $_GET['post'] );

				    if ( $enabled == 'yes' && $wpnonce == $_GET['_wpnonce'] ) { 
				    	$this->generate_paragraph_post( $post_id );
				    	if ( isset( $_GET['edit'] ) && isset( $_GET['edit'] ) == "page-edit" ) {
							$edit_url 	= "post.php?post={$post_id}&action=edit&_wpnonce={$wpnonce}";
				    		wp_redirect( admin_url( $edit_url ) );
							exit();
				    	} else {
							$edit_url 	= "edit.php?post_type={$post_type}";
				    		wp_redirect( admin_url( $edit_url ) );
							exit();
				    	}
				    }
				}
			}
		}
		//=>

		public function paragraph_post_link( $actions, $post ) {	
			$settings 			= get_option( 'wp_paragraph_posts_settings' );
			$formatting_enabled = esc_attr( $settings['formatting_enabled'] );
			$enabled 			= esc_attr( $settings['enabled'] );
			$post_type 			= esc_attr( $settings['post_type'] );

		    if ( $enabled == 'yes' && $post->post_type == $post_type ) { 
		    	$wpnonce 		= esc_attr( wp_create_nonce() );
		    	$generate_url 	= "post.php?post={$post->ID}&action=generate-paragraph&_wpnonce={$wpnonce}";
		        $actions['paragraph'] = '<a href="' . admin_url( $generate_url ) . '" title="Gerar">Gerar Paragrafos</a>';
		    }
		    if ( $formatting_enabled == 'yes' && $post->post_type == $post_type ) { 
		    	$wpnonce 		= esc_attr( wp_create_nonce() );
		    	$formatting_url 	= "post.php?post={$post->ID}&action=formatting-paragraph&_wpnonce={$wpnonce}";
		        $actions['formatting'] = '<a href="' . admin_url( $formatting_url ) . '" title="Gerar">Formata Paragrafos</a>';
		    }
		    return $actions;
		}
		//=>

		public static function activate_plugin() {			 
			if ( is_admin() ) {				
				$settings = array(
					'enabled'	=> 'yes',
					'post_type'	=> 'post',
					'number'	=> '2',
					'character'	=> '.',
					'class'		=> '',
					'formatting_enabled'		=> 'yes',
					'formatting_characters'		=> '300',
					'formatting_character_last' => '.'
				);				
				update_option( 'wp_paragraph_posts_settings', $settings, 'yes' );
			}
		}
		//=>

		public function page_admin_settings_callback() { 
		    
			$message 	= "";
			if( isset( $_REQUEST['_wpnonce'] ) && isset( $_REQUEST['_update'] ) ) {
				$nonce 		= sanitize_text_field( $_REQUEST['_wpnonce'] );
				$update 	= sanitize_text_field( $_REQUEST['_update'] );
				if ( wp_verify_nonce( $nonce, "wp-paragraph-posts-update" ) ) {

					$post_settings = array();
					$post_settings = (array)$_POST['settings'];

					if( isset( $post_settings['enabled'] ) && $post_settings['enabled'] != ""  ) {
						$new_settings['enabled'] = sanitize_text_field( $post_settings['enabled'] );
					} else {
						$new_settings['enabled'] = "no";
					}
					if ( isset( $post_settings['post_type'] ) ) {
						$new_settings['post_type'] = sanitize_text_field( $post_settings['post_type'] );
					}
					if ( isset( $post_settings['number'] ) && $post_settings['number'] != "" ) {
						$new_settings['number'] = sanitize_text_field( $post_settings['number'] );
					} else {
						$new_settings['number'] = "2";
					}
					if ( isset( $post_settings['character'] ) && $post_settings['character'] != "" ) {
						$new_settings['character'] = sanitize_text_field( $post_settings['character'] );
					} else {
						$new_settings['character'] = ".";
					}
					if ( isset( $post_settings['class'] ) ) {
						$new_settings['class'] = sanitize_text_field( $post_settings['class'] );
					}

					$settings = array();
					$settings = get_option( 'wp_paragraph_posts_settings' );

					update_option( "wp_paragraph_posts_settings", array_merge( $settings, $new_settings ) );
					
					$message = "updated";
				} else {
		            $message = "error";
				}
			}

			if( isset( $_REQUEST['_wpnonce'] ) && isset( $_REQUEST['_update_extras'] ) ) {
				$nonce 		= sanitize_text_field( $_REQUEST['_wpnonce'] );
				$update 	= sanitize_text_field( $_REQUEST['_update_extras'] );
				if ( wp_verify_nonce( $nonce, "wp-paragraph-posts-update" ) ) {

					$post_settings = array();
					$post_settings = (array)$_POST['settings'];

					if( isset( $post_settings['formatting_enabled'] ) && $post_settings['formatting_enabled'] != ""  ) {
						$new_settings['formatting_enabled'] = sanitize_text_field( $post_settings['formatting_enabled'] );
					} else {
						$new_settings['formatting_enabled'] = "no";
					}
					if ( isset( $post_settings['formatting_characters'] ) ) {
						$new_settings['formatting_characters'] = sanitize_text_field( $post_settings['formatting_characters'] );
					} else {
						$new_settings['formatting_characters'] = "300";
					}
					if ( isset( $post_settings['formatting_character_last'] ) && $post_settings['formatting_character_last'] != "" ) {
						$new_settings['formatting_character_last'] = sanitize_text_field( $post_settings['formatting_character_last'] );
					} else {
						$new_settings['formatting_character_last'] = ".";
					}

					$settings = array();
					$settings = get_option( 'wp_paragraph_posts_settings' );

					update_option( "wp_paragraph_posts_settings", array_merge( $settings, $new_settings ) );
					
					$message = "updated";
				} else {
		            $message = "error";
				}
			}

			$settings 	= get_option( 'wp_paragraph_posts_settings' );
			$enabled 	= esc_attr( $settings['enabled'] );
			$post_type 	= esc_attr( $settings['post_type'] );
			$number 	= esc_attr( $settings['number'] );
			$character 	= esc_attr( $settings['character'] );
			$class 		= esc_attr( $settings['class'] );

			/* extras */
			$formatting_enabled 		= esc_attr( $settings['formatting_enabled'] );
			$formatting_characters 		= esc_attr( $settings['formatting_characters'] );
			$formatting_character_last 	= esc_attr( $settings['formatting_character_last'] );

		?>
		<!----->
		<div id="wpwrap">
		<!--start-->
		    <h1>Paragraph Posts</h1>
		    
		    <?php if( isset( $message ) ) { ?>
		        <div class="wrap">
		    	<?php if( $message == "updated" ) { ?>
		            <div id="message" class="updated notice is-dismissible" style="margin-left: 0px;">
		                <p>Atualizações feita com sucesso!</p>
		                <button type="button" class="notice-dismiss">
		                    <span class="screen-reader-text">
		                        Dispensar este aviso.
		                    </span>
		                </button>
		            </div>
		            <?php } ?>
		            <?php if( $message == "error" ) { ?>
		            <div id="message" class="updated error is-dismissible" style="margin-left: 0px;">
		                <p>Erro! Não conseguimos fazer as atualizações!</p>
		                <button type="button" class="notice-dismiss">
		                    <span class="screen-reader-text">
		                        Dispensar este aviso.
		                    </span>
		                </button>
		            </div>
		        <?php } ?>
		    	</div>
		    <?php } ?>
		    <!----->

		    <div class="wrap woocommerce">

				<nav class="nav-tab-wrapper wc-nav-tab-wrapper">
	            <?php if( isset( $_GET['tab'] ) ) { $tab = sanitize_text_field( $_GET['tab'] ); } ?>
	           		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paragraph-posts' ) ); ?>" class="nav-tab <?php if( $tab == "" ) { echo "nav-tab-active"; }; ?>">
	           			Configurações
	           		</a>
	           		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-paragraph-posts&tab=extras' ) ); ?>" class="nav-tab <?php if( $tab == "extras" ) { echo "nav-tab-active"; }; ?>">
	           			Extras
	           		</a>
	            </nav>
	            <!---->

		        <?php if( ! isset( $tab ) ) { ?>
		    	<form method="post" id="mainform" name="mainform" enctype="multipart/form-data">
		            <input type="hidden" name="_update" value="1">
		            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'wp-paragraph-posts-update' ) ); ?>">
		            <!---->
		            <table class="form-table">
		                <tbody>
		                    <!---->
		                    <tr valign="top">
		                        <th scope="row">
		                            <label>
		                                Habilitar:
		                            </label>
		                        </th>
		                        <td>
                                <label>
                                    <input type="checkbox" name="settings[enabled]" value="yes" <?php if( $enabled == "yes" ) { echo 'checked="checked"'; } ?> class="form-control">
                                    Ativar plugin
                            		&nbsp;&nbsp;
                                </label>
		                       </td>
		                    </tr>
		                    <!---->
		                    <tr valign="top">
		                        <th scope="row">
		                            <label>
		                                Tipo de post:
		                            </label>
		                        </th>
		                        <td>
                                <label>
									<select name="settings[post_type]" style="width: 100%; max-width: 170px;" class="form-control">
										<option value="">Selecione</option>
										<option value="post" <?php if( $post_type == "post" ) { echo "selected"; } ?>>
											Postagens
										</option>
										<option value="page" <?php if( $post_type == "page" ) { echo "selected"; } ?>>
											Páginas
										</option>
									</select>
                            		&nbsp;&nbsp;
                                </label>
                                <span>
									<span aria-hidden="true" class="dashicons dashicons-admin-post" style="vertical-align: sub;"></span>
									O tipo de post que será utilizado.
								</span>
		                       </td>
		                    </tr>
		                    <!---->
		                    <tr valign="top">
		                        <th scope="row">
		                            <label>
		                                Nº de Paragrafos:
		                            </label>
		                        </th>
		                        <td>
                                <label>
                                    <input type="number" name="settings[number]" placeholder="Padrão: 2" min="2" class="form-control input-text wc_input_decimal"  value="<?php echo $number; ?>">
                            		&nbsp;&nbsp;
                                </label>
                                <span>
									<span aria-hidden="true" class="dashicons dashicons-editor-paragraph" style="vertical-align: sub;"></span>
									Será gerado o subtítulo a partir desse número.
								</span>
		                       </td>
		                    </tr>
		                    <!---->
		                    <tr valign="top">
		                        <th scope="row">
		                            <label>
		                                Caractere Final:
		                            </label>
		                        </th>
		                        <td>
                                <label>
                                    <input type="text" name="settings[character]" placeholder="Padrão: ponto" class="form-control input-text"  value="<?php echo $character; ?>">
                            		&nbsp;&nbsp;
                                </label>
                                <span>
									<span aria-hidden="true" class="dashicons dashicons-editor-customchar" style="vertical-align: sub;"></span>
									A primeira ocorrencia for encontrada finaliza o subtítulo.
								</span>
		                       </td>
		                    </tr>
		                    <!---->
		                    <tr valign="top">
		                        <th scope="row">
		                            <label>
		                                Classe ( CSS ):
		                            </label>
		                        </th>
		                        <td>
                                <label>
                                    <input type="text" name="settings[class]" placeholder="Ex: entry-title" class="form-control input-text"  value="<?php echo $class; ?>">
                            		&nbsp;&nbsp;
                                </label>
                                <span>
									<span aria-hidden="true" class="dashicons dashicons-art" style="vertical-align: sub;"></span>
									A Classe para ser usada no H3 gerados pelo sistema no subtítulo.
								</span>
		                       </td>
		                    </tr>
		                    <!---->
		                </tbody>
		            </table>
		            
	                <hr/>
	                <div class="submit">
	                    <button class="button-primary" type="submit"><?php echo __( 'Salvar Alterações', 'wp-paragraph-posts' ) ; ?></button>
	                </div>

		        </form>
		        <?php } ?>
				<!---->
				<?php if( isset( $tab ) && $tab == "extras" ) { ?>
		    	<form method="post" id="mainform" name="mainform" enctype="multipart/form-data">
		            <input type="hidden" name="_update_extras" value="1">
		            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'wp-paragraph-posts-update' ) ); ?>">
		            <!---->
		            <table class="form-table">
		                <tbody>
		                    <!---->
		                    <tr valign="top">
		                        <th scope="row">
		                            <label>
		                                Habilitar:
		                            </label>
		                        </th>
		                        <td>
                                <label>
                                    <input type="checkbox" name="settings[formatting_enabled]" value="yes" <?php if( $formatting_enabled == "yes" ) { echo 'checked="checked"'; } ?> class="form-control">
                                    Ativar a função de formatação de texto.
                            		&nbsp;&nbsp;
                                </label>
		                       </td>
		                    </tr>
		                    <!---->
		                    <tr valign="top">
		                        <th scope="row">
		                            <label>
		                                Nº Caracteres:
		                            </label>
		                        </th>
		                        <td>
                                <label>
                                    <input type="number" name="settings[formatting_characters]" placeholder="Mínimo: 300" min="300" class="form-control input-text wc_input_decimal"  value="<?php echo $formatting_characters; ?>">
                            		&nbsp;&nbsp;
                                </label>
                                <span>
									<span aria-hidden="true" class="dashicons dashicons-editor-paragraph" style="vertical-align: sub;"></span>
									Será gerado um paragrafo a cada número caracteres indicado.
								</span>
		                       </td>
		                    </tr>
		                    <!---->
		                    <tr valign="top">
		                        <th scope="row">
		                            <label>
		                                Caractere Final:
		                            </label>
		                        </th>
		                        <td>
                                <label>
                                    <input type="text" name="settings[formatting_character_last]" placeholder="Padrão: ponto" class="form-control input-text"  value="<?php echo $formatting_character_last; ?>">
                            		&nbsp;&nbsp;
                                </label>
                                <span>
									<span aria-hidden="true" class="dashicons dashicons-editor-customchar" style="vertical-align: sub;"></span>
									Adiciona esse caractere no final de cada linha ou paragrafo.
								</span>
		                       </td>
		                    </tr>
		                    <!---->
		                </tbody>
		            </table>
		            
	                <hr/>
	                <div class="submit">
	                    <button class="button-primary" type="submit">
	                    	<?php echo __( 'Salvar Alterações', 'wp-paragraph-posts' ) ; ?>
	                    </button>
	                </div>

		        </form>
				<?php } ?>
				<!---->
		    </div>
		</div>
		<?php
		}


	}

	new WP_Paragraph_Posts();
	//..
}