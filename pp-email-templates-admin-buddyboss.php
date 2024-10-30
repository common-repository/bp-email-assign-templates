<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

//  the List Table class
pp_etemplates_admin_list();




function buddyboss_pp_add_admin_email_tab( $tabs ) {

	$tabs['56'] = array(
		'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'email-templates' ), 'admin.php' ) ),
		'name'  => 'Email Templates',
		'class' => 'philopress',
	);
	//echo '<pre>'; var_dump( $tabs ); echo '</pre>';
	return $tabs;
}
add_filter( 'bp_core_get_admin_tabs', 'buddyboss_pp_add_admin_email_tab' );


function buddyboss_pp_admin_email_menus() {
	add_submenu_page(
		'buddyboss-platform',
		'Email Templates',
		'Email Templates',
		'manage_options',
		'email-templates',
		'pp_etemplates_admin_screen'
	);
}

add_action( 'bp_init', function() {
	add_action( bp_core_admin_hook(), 'buddyboss_pp_admin_email_menus' );
} );


function pp_etemplates_edit_form( $id ) {
	global $wpdb;

	if ( ! isset( $_POST['eto-id'] ) ) {

		pp_etemplates_scripts();

		echo '<div class="wrap"><h3>Edit Template Option</h3>';

			$query = " SELECT * FROM {$wpdb->prefix}options WHERE option_id = $id ";
			$eto = $wpdb->get_row($query);

			if ( $eto != NULL ) {

				$eto_value = maybe_unserialize( $eto->option_value );

				echo '<form action="' . site_url() . '/wp-admin/admin.php?page=email-templates&action=edit-eto" name="eto-form" id="eto-form" method="post" class="standard-form">';

				wp_nonce_field('eto-edit-action', 'eto-edit-field');

			?>

				<table border="0" cellspacing="10">

					<tr>
						<td>Option Name: <div id='eto-name-error' class='error_div'></div></td>
						<td><input type="text" name="eto-name" id="eto-name" maxlength="50" size="50" value="<?php echo stripslashes( $eto_value['oname'] ); ?>" /></td>
					</tr>

					<tr>
						<td>Option File: <div id='eto-file-name-error' class='error_div'></div></td>
						<td><input type="text" name="eto-file-name" id="eto-file-name" maxlength="50" size="50" value="<?php echo stripslashes( $eto_value['fname'] ); ?>" /></td>
					</tr>

				</table>

				<input type="hidden" id="eto-editor" name="eto-editor" value="1" />
				<input type="hidden" name="eto-option-name" id="eto-option-name" value="<?php echo $eto->option_name; ?>"/>
				<input id="eto-submit" name="eto-submit" type="button" class="button button-primary" onclick="validateEtoForm()" value="<?php _e('Update Template Option', 'bp-email-templates'); ?>"  />
				</form>
			<?php
			}
			else
				echo '<br/><div class="error_div"><strong>' . __('The Template Option was not found.', 'bp-email-templates' ) . '</strong></div>';

		echo '</div>';

	}
}

function pp_etemplates_update_option() {

	if ( isset( $_POST['eto-option-name'] ) ) {

		if ( !wp_verify_nonce($_POST['eto-edit-field'],'eto-edit-action') )
			die('Security Check - Failed');

		if ( ! current_user_can('manage_options') )
			return false;

		if ( ! empty( $_POST['eto-name'] ) && ! empty( $_POST['eto-file-name'] ) ) {

			$validate_filename = pp_etemplates_validate_filename( $_POST['eto-file-name'] );

			if ( $validate_filename ) {

				$option_name = $_POST['eto-option-name'];

				$option_value = array(
						'oname' => $_POST['eto-name'],
						'fname' => $_POST['eto-file-name'],
					);

				$update_option = update_option( $option_name, $option_value, false );

				if ( $update_option )
					echo '<br/><div class="entry-content"><strong>' . __('Template Option was Updated', 'bp-email-templates' ) . '</strong></div><br/>';
				else
					echo '<br/><div class="error_div"><strong>' . __('There was a problem Updating that Template Option.', 'bp-email-templates' ) . '</strong></div><br/>';

			}
			else
				echo $validate_filename;
		}
		else
			echo '<br/><div class="error_div"><strong>' . __('Please fill out both fields.', 'bp-email-templates' ) . '</strong></div><br/>';

	}
}


function pp_etemplates_create_form() {

	pp_etemplates_scripts();

	echo pp_etemplates_help();

	echo '<div class="wrap"><h3>Create a Template Option</h3>';

	echo '<form action="' . site_url() . '/wp-admin/admin.php?page=email-templates&action=create-eto" name="eto-form" id="eto-form"  method="post" class="standard-form">';

	wp_nonce_field('eto-create-action', 'eto-create-field');
?>

		<table border="0" cellspacing="10">

			<tr>
				<td>Option Name: <div id='eto-name-error' class='error_div'></div></td>
				<td><input type="text" name="eto-name" id="eto-name" maxlength="50" size="50" value="" /></td>
			</tr>

			<tr>
				<td>Option File: <div id='eto-file-name-error' class='error_div'></div></td>
				<td><input type="text" name="eto-file-name" id="eto-file-name" maxlength="50" size="50" value="" /><br/><em><?php _e('The file must already exist in the root of your active theme directory.', 'bp-email-templates'); ?></em></td>
			</tr>

		</table>
		<input type="hidden" id="eto-creator" name="eto-creator" value="1" />
		<input id="eto-submit" name="eto-submit" type="button" class="button button-primary" onclick="validateEtoForm()" value="<?php _e('Create Template Option', 'bp-email-templates'); ?>"  />

	</form>
	</div>
<?php
}


function pp_etemplates_help () {

	 $content =
		__( 'On this screen, you can create, edit or delete an email template option.', 'bp-email-templates' ) .
		'<p>' . __( 'The Option Name can be anything but should be unique.', 'bp-email-templates' ) . '</p>';


    $content .=
		'<p>' . __( 'The file name needs to end with ".php" and must be a file that exists in your active theme.', 'bp-email-templates' ) . '</p>' .
		'<p>' . __( 'If you want email templates to live in a separate directory in your theme, then include the path as part of the file name. For example "/bp-email-templates/bp-email-for-friends.php"', 'bp-email-templates' ) . '</p>';


	$content .=
		'<p>' . __( 'BuddyBoss includes a <em>single</em> default email template.', 'bp-email-templates' ) .
		'<br>You will find it here: &nbsp;<em>...plugins/buddyboss-platform/bp-templates/bp-nouveau/buddypress/assets/emails/single-bp-email.php</em>
		'  . '</p>' .
		'<p>' . __( 'But what if you want multiple and assignable templates? Or have a template that does not use the customizer settings?', 'bp-email-templates' ) . '</p>' .
		'<p>' . __( 'If so, create a copy of the default email template, rename it and adjust as necessary. You can change the layout, include images, remove the customizer setting calls, etc.', 'bp-email-templates' ) . '</p>' .
		'<p>' . __( '[ The customizer settings <em>will apply to all templates</em> that call the customizer settings. ]', 'bp-email-templates' ) . '</p>' .
		'<p>' . __( 'Then upload the new template to your active theme directory and create an option here.', 'bp-email-templates' ) . '</p>' .
		'<p>' . __( 'Your new option will be available for selection on the Email create and edit screens in the "Email Template" area.', 'bp-email-templates' ) . '</p>';

		return $content;

}



function pp_etemplates_create_option() {

	if ( isset( $_POST['eto-creator'] ) && $_POST['eto-creator'] == '1' ) {

		if ( ! empty( $_POST['eto-name'] ) && ! empty( $_POST['eto-file-name'] ) ) {

			if ( !wp_verify_nonce($_POST['eto-create-field'],'eto-create-action') )
				die('Security Check - Failed');

			if ( ! current_user_can('manage_options') )
				return false;


			$validate_filename = pp_etemplates_validate_filename( $_POST['eto-file-name'] );

			if ( $validate_filename ) {

				$count = intval( get_option( 'bp_email_templates_count' ) ) + 1;
				$option_name = 'bp-email-template-' . $count;

				$option_value = array(
						'oname' => $_POST['eto-name'],
						'fname' => $_POST['eto-file-name'],
					);

				$new_option = add_option( $option_name, $option_value, '', false );

				//write_log( $new_option );

				if ( $new_option ) {
					update_option( 'bp_email_templates_count', $count, false );
					echo '<br/><div class="entry-content"><strong>' . __('Template Option was created.', 'bp-email-templates' ) . '</strong></div><br/>';
				}
				else
					echo '<br/><div class="error_div"><strong>' . __('There was a problem creating that Template Option.', 'bp-email-templates' ) . '</strong></div><br/>';

			}
			else
				echo $validate_filename;

		}
		else
			echo '<br/><div class="error_div"><strong>' . __('Please fill out both fields.', 'bp-email-templates' ) . '</strong></div>';
	}
}


function pp_etemplates_validate_filename( $filename ) {

	// check for file name ending in .php
	$file_name_end = substr( $filename, -4 );

	if ( '.php' != $file_name_end ) {

		echo '<br/><div class="error_div"><strong>' . __('The file name must end with .php', 'bp-email-templates' ) . '</strong></div>';

		return false;
	}

	// check if file exists
	$template_exists = locate_template( $filename, false );

	if ( '' == $template_exists ) {

		echo '<br/><div class="error_div"><strong>' . sprintf( __('The file %s was not found in the root of your active theme.', 'bp-email-templates' ), $filename ) . '</strong></div>';

		return false;
	}

	return true;
}



function pp_etemplates_admin_screen() {
?>
        <h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( 'Email Templates' ); ?></h2>

		<div class="bp-admin-card section-bp_main">

			<h2>Email Templates</h2>

<?php
		pp_etemplates_update_option();

		if ( isset( $_GET['action'] ) ) {

			if ( $_GET['action'] == 'create-eto' )
				pp_etemplates_create_option();

		}


		$pp_etemplates_list_table = new BP_Email_Templates_Table();

		$pp_etemplates_list_table->prepare_items();


		if ( isset( $_GET['action'] ) ) {
			if ( $_GET['action'] == 'edit-single' ) {
				pp_etemplates_edit_form( $_GET['gid'] );
			} else {
				pp_etemplates_create_form();
			}
		}
		else
			pp_etemplates_create_form();

		?>

		<br />

		<form id="eto-filter" method="post">
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			<?php $pp_etemplates_list_table->display();  ?>
		</form>

	</div>
<?php
}


function pp_etemplates_admin_styles() {

	$page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;

	if ( 'bp-emails-templates' != $page )
		return;

	$style_str = '<style type="text/css">';
	$style_str .= '.column-name { width: 30%; }';
	$style_str .= '.column-file-name { width: 60%; }';
	$style_str .= '</style>';
	echo $style_str;
}
add_action( 'admin_head', 'pp_etemplates_admin_styles'  );


function pp_etemplates_admin_list() {

	if (!class_exists('WP_List_Table')){
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	}

	class BP_Email_Templates_Table extends WP_List_Table {

		 function __construct() {
			 parent::__construct( array(
			'singular'=> 'eto',
			'plural' => 'etos',
			'ajax'	=> false
			) );
		 }

		function get_columns() {
			return $columns= array(
				'cb'            => '<input type="checkbox" />',
				'name'	        => __('Name'),
				'file-name'     => __('File'),
			);
		}


		function get_bulk_actions() {
			$actions = array(
				'delete' => 'Delete'
			);
			return $actions;
		}


		function delete_eto( $id ) {
			global $wpdb;

			$wpdb->query( "DELETE FROM $wpdb->options WHERE option_id = $id" );

		}

		function process_bulk_action() {

			if ( 'delete'===$this->current_action() ) {
				foreach($_POST['bid'] as $id) {
					$this->delete_eto( $id );
				}
			}

			if ( 'delete-single'===$this->current_action() ) {
				$nonce = $_REQUEST['_wpnonce'];
				if (! wp_verify_nonce($nonce, 'eto-nonce') ) die('Security check');

				$this->delete_eto( $_GET['gid'] );
			}

		}


		function prepare_items( $search = NULL ) {
			global $wpdb, $_wp_column_headers;

			$screen = get_current_screen();

			$this->process_bulk_action();

			$query = "SELECT * FROM {$wpdb->prefix}options WHERE option_name LIKE 'bp-email-template-%' ORDER BY option_id DESC ";

			$columns = $this->get_columns();
			$hidden = array();
			$sortable = $this->get_sortable_columns();
			$this->_column_headers = array($columns, $hidden, $sortable);

			$this->items = $wpdb->get_results($query);

		}



		function display_rows() {
			global $wpdb;

			$records = $this->items;        //print_r( $records );

			list( $columns, $hidden ) = $this->get_column_info();

			if ( !empty($records) ) {
				foreach( $records as $rec ) {

					$rec_data = maybe_unserialize( $rec->option_value );

					if ( $rec_data['oname'] == 'Default Template' ) {
						echo '<tr id="record_0"><th scope="row" class="check-column"></th><td class="name column-name">Default Template<br><div class="row-actions"><span class="edit"><a href="https://codex.buddypress.org/emails/#customize-email-template" target="_blank">This is the default template. You cannot delete it, but you can overload it. More info...</a></div></td><td class="file-name column-file-name">single-bp-email.php</td>';
					}
					else {
						echo '<tr id="record_'.$rec->option_id.'">';
						foreach ( $columns as $column_name => $column_display_name ) {

							$class = "class='$column_name column-$column_name'";
							$style = "";
							if ( in_array( $column_name, $hidden ) ) $style = ' style="display:none;"';
							$attributes = $class . $style;

							switch ( $column_name ) {

								case "cb":
									echo '<th scope="row" class="check-column">';
									echo '<input type="checkbox" name="bid[]" value="' . $rec->option_id . '"/>';
									echo '</th>';
									break;

								case "name":
										echo '<td '. $attributes . '>' . stripslashes($rec_data['oname']);
										echo "<br /><div class='row-actions'><span class='edit'>";
										$edit_nonce= wp_create_nonce('eto-edit-nonce');
										echo sprintf('<a href="?page=email-templates&action=edit-single&gid=%s&_wpnonce=%s" ">' . __('Edit', 'bp-email-templates') . '</a>',$rec->option_id,$edit_nonce);
										echo "</span> | <span class='trash'>";
										$nonce= wp_create_nonce('eto-nonce');
										echo sprintf('<a href="?page=email-templates&action=%s&gid=%s&_wpnonce=%s" onclick="return confirm(\'Are you sure you want to Delete this Template Option?\');">' . __('Delete', 'bp-email-templates') . '</a>','delete-single',$rec->option_id,$nonce);
										echo "</span></div></td>";
									break;

								case "file-name":
									echo '<td '. $attributes . '>'. $rec_data['fname'] . "</td>";
									break;
							}
						}
						echo'</tr>';
					}
				}
			}
		}
	}
}

// print scripts and styles for create / edit forms
function pp_etemplates_scripts() {
?>
	<style> .error_div { color: red; } </style>

	<script type="text/javascript">

		function validateEtoForm() {
			removeEtoValidationErrors();

			if ( validateEtoRequiredField( document.getElementById('eto-name').value ) == false ) {
				document.getElementById('eto-name-error').innerHTML = "Please add a Name.";
				document.getElementById('eto-name').style.background= "#eee";
				document.getElementById('eto-name').focus();
				return false;
			}

			if ( validateEtoRequiredField( document.getElementById('eto-file-name').value ) == false ) {
				document.getElementById('eto-file-name-error').innerHTML = "Please add a File Name.";
				document.getElementById('eto-file-name').style.background= "#eee";
				document.getElementById('eto-file-name').focus();
				return false;
			}

			document.forms["eto-form"].submit();
			return false;
		}


		function removeEtoValidationErrors() {
			document.getElementById('eto-name-error').innerHTML = "";
			document.getElementById('eto-file-name-error').innerHTML = "";
		}

		function validateEtoRequiredField(value) {
			if ( value == null || value == "" ) {
					return false;
			}
		}

	</script>
<?php
}

// add Template Name column
function pp_etemplates_add_custom_column( $columns ){

	unset($columns['title']);
	unset($columns['date']);

    $columns['title']       = __( 'Title', 'buddypress' );
    $columns['etemplate']   = __( 'Template', 'bp-email-templates' );
    $columns['date']        = __( 'Date', 'bp-email-templates' );

    return $columns;
}
add_filter( 'manage_' . bp_get_email_post_type() . '_posts_columns', 'pp_etemplates_add_custom_column' );

// add data to Template Name column
function pp_etemplates_add_custom_column_data( $column, $post_id ){

    if ( $column == 'etemplate' ) {

        $value = get_post_meta( $post_id, 'bp-etemplate', true );

        if ( ! empty( $value ) ) {

			$option = get_option( $value );

			if ( ! $option ) {

				// option does not exist, so get rid of post_meta and use default template
				delete_post_meta( $post_id, 'bp-etemplate' );

				echo 'Default Template';

			}
			else
				echo $option['oname'];
        }
		else
			echo 'Default Template';
    }
}
add_action( 'manage_' . bp_get_email_post_type() . '_posts_custom_column', 'pp_etemplates_add_custom_column_data', 10, 2 );


function pp_etemplates_custom_column_css() {
  echo '<style> .column-etemplate {width: 20%} </style>';
}
add_action( 'admin_head', 'pp_etemplates_custom_column_css' );

