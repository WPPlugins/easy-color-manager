<?php
/*
Plugin Name: Easy Color Manager
Plugin URI: http://iplatform.jp
Description: To Change Your Site Color and Images Easily.
Author: iplatform corporation
Version: 1.1.1
*/

/** -------------------------------------------------
 *  CSSファイルの組み込み
 * -------------------------------------------------- */
function custom_color_manager() {
	wp_enqueue_style('site-color', WP_PLUGIN_URL .'/easy-color-manager/sitecolor.php');
}
function admin_color_manager() {
	wp_enqueue_style( 'admin-site-color', WP_PLUGIN_URL .'/easy-color-manager/admin-sitecolor.php');
}
add_action( 'wp_print_styles', 'custom_color_manager');
add_action( 'admin_print_styles-appearance_page_color-manager', 'admin_color_manager');

/** -------------------------------------------------
 *  カラー設定項目の定義
 * -------------------------------------------------- */
function get_color_option_array(){
	return array( 'color', 'panel', 'border', 'char1', 'char2', 'char3' );
}
function get_size_option_array(){
	return array( 'height1', 'width1' );
}
function get_setting_option_array(){
	return array( 'radius', 'shadow', 'noise' );
}
function get_image_option_array(){
	return array( 'image1' );
}

/** -------------------------------------------------
 *  カラー設定対象の定義
 * -------------------------------------------------- */
function get_background_part_array(){

	$theme = get_current_theme(); //var_dump( $theme );
	$template = get_template(); //var_dump( $template );
	$twentyten = array( 
			'body'		=> array( 'name' => 'body（背景）', 'type' => 'content', ),
			'header'	=> array( 'name' => 'header （ヘッダー）', 'type' => 'content', ),
			'site-title'	=> array( 'name' => 'site-title（サイトタイトル）', 'type' => 'content', ),
			'site-description'	=> array( 'name' => 'site-description（キャッチコピー）', 'type' => 'content', ),
			'wrapper'	=> array( 'name' => 'wrapper （全体）', 'type' => 'content', ),
			'container'	=> array( 'name' => 'container（主要部分）', 'type' => 'content', ),
			'content'	=> array( 'name' => 'content （記事部分）', 'type' => 'content', ),
			'entry-title'   => array( 'name' => 'entry-title（記事タイトル）', 'type' => 'content', ),
			'sidebar-left'	=> array( 'name' => 'sidebar-left （左サイドバー）', 'type' => 'content', ),
			'sidebar-right'	=> array( 'name' => 'sidebar-right （右サイドバー）', 'type' => 'content', ),
			'footer'	=> array( 'name' => 'footer （フッター）', 'type' => 'content', ),
		);
	$twentyeleven = array( 
			'body'		=> array( 'name' => 'body （背景）', 'type' => 'content', ),
			'site-title'	=> array( 'name' => 'site-title（サイトタイトル）', 'type' => 'content', ),
			'site-description'	=> array( 'name' => 'site-description（キャッチコピー）', 'type' => 'content', ),
			'page'		=> array( 'name' => 'page （全体）', 'type' => 'content', ),
			'branding'	=> array( 'name' => 'branding （ヘッダー）', 'type' => 'content', ),
			'primary'	=> array( 'name' => 'primary （主要部分）', 'type' => 'content', ),
			'content'	=> array( 'name' => 'content （記事部分）', 'type' => 'content', ),
			'entry-title'   => array( 'name' => 'entry-title（記事タイトル）', 'type' => 'content', ),
			'secondary'	=> array( 'name' => 'secondary （サイドバー）', 'type' => 'content', ),
			'colophon'	=> array( 'name' => 'colophon（フッター）', 'type' => 'content', ),
			'site-generator'=> array( 'name' => 'site-generator（フッター）', 'type' => 'content', ),
		);

	$background_part_array = get_theme_mod('background_part');

	if( is_array( $background_part_array ) && !empty( $background_part_array )){

		return $background_part_array;

	} elseif( $theme === 'Twenty Eleven' || $template === 'twentyeleven' ) {
		
		return $twentyeleven;
		
	} elseif( $theme === 'twentyten' || $template === 'twentyten' ){

		return $twentyten;

	} else {

		return array( 'body' => array( 'name' => 'body （全体）', 'type' => 'content', ), );

	}
}
/** -------------------------------------------------
 *  Easy_Color_Manager クラスの生成
 * -------------------------------------------------- */
if ( isset( $GLOBALS['easy_color_manager'] ) ) return;
if ( ! is_admin() ) return;

$GLOBALS['easy_color_manager'] =& new Easy_Color_Manager();
add_action( 'admin_menu', array( &$GLOBALS['easy_color_manager'], 'init' ), 20 );

/** -------------------------------------------------
 *  Easy_Color_Manager クラスの定義
 * -------------------------------------------------- */
class Easy_Color_Manager {

	/** -----------------------------------------
	 *  カラー設定を保持する変数
	 * ------------------------------------------ */
	var $color_manager_array = array();

	/** -----------------------------------------
	 *  カラー設定対象を保持する変数
	 * ------------------------------------------ */
	var $background_part_array = array();

	/** -----------------------------------------
	 *  背景画像オプションを保持する変数
	 * ------------------------------------------ */
	var $custom_option_array = array();


	/** -----------------------------------------
	 *  カラー設定対象のタイプを保持する変数
	 * ------------------------------------------ */
	var $navigation_array = array();
	var $navigation_01_array = array();
	var $navigation_02_array = array();
	var $content_array = array();
	var $all_array = array();

	/**
	 * PHP4 Constructor - Register administration header callback.
	 *
	 * @since 3.0.0
	 * @param callback $admin_header_callback
	 * @param callback $admin_image_div_callback Optional custom image div output callback.
	 * @return Custom_Background
	 */
	function Easy_Color_Manager() {}

	/**
	 * Set up the hooks for the Custom Background admin page.
	 *
	 * @since 3.0.0
	 */
	function init() {

		if ( ! current_user_can('edit_theme_options') )
			return;

		$this->page = $page = add_submenu_page( 'themes.php', 'Color Manager', 'Color Manager', 'edit_theme_options', 'color-manager', array(&$this, 'admin_page'), 30 );
		$this->background_part_array = get_background_part_array();
		$this->custom_option_array = array(
			'background-position-x' => array(
				'left' => '左',
				'center' => '中央',
				'right' => '右'
			),
			'background-repeat' => array(
				'no-repeat' => '繰り返しなし',
				'repeat-x' => '水平に繰り返し',
				'repeat-y' => '垂直に繰り返し',
				'repeat'   => '両方に繰り返し',
			),
			'background-attachment' => array(
				'scroll' => 'スクロール',
				'fixed' => '固定',
			)
		);

		foreach( array_keys( $this->background_part_array ) as $key ){

			if( $this->background_part_array[$key]['type'] === 'navigation-01' ){
				$this->navigation_array[] = $key;
				$this->navigation_01_array[] = $key;
				$this->all_array[] = $key;
			} elseif( $this->background_part_array[$key]['type'] === 'navigation-02' ){
				$this->navigation_array[] = $key;
				$this->navigation_02_array[] = $key;
				$this->all_array[] = $key;
			} else {
				$this->content_array[] = $key;
				$this->all_array[] = $key;
			}
		}

		add_action("load-$page", array(&$this, 'admin_load'));
		add_action("load-$page", array(&$this, 'take_action'), 49);
		add_action("load-$page", array(&$this, 'handle_upload'), 49);

		if ( $this->admin_header_callback )
			add_action("admin_head-$page", $this->admin_header_callback, 51);

	}

	/**
	 * Set up the enqueue for the CSS & JavaScript files.
	 *
	 * @since 3.0.0
	 */
	function admin_load() {
		wp_enqueue_script('color-manager.script', WP_PLUGIN_URL .'/easy-color-manager/color-manager.script.js', array('jquery'), '1.0.0');
		wp_enqueue_style('color-manager.style', WP_PLUGIN_URL .'/easy-color-manager/color-manager.style.css');
	}

	/**
	 * Execute color manager modification.
	 *
	 * @since 3.0.0
	 */
	function take_action() {


		if ( empty($_POST) )
			return;

		if ( isset($_POST['reset-background']) ) {
			check_admin_referer('color-manager-reset', '_wpnonce-color-manager-reset');
			remove_theme_mod('background_image');
			remove_theme_mod('background_image_thumb');
			$this->updated = true;
			return;
		}

		if ( isset($_POST['remove-background']) ) {
			// @TODO: Uploaded files are not removed here.
			check_admin_referer('color-manager-remove', '_wpnonce-color-manager-remove');
			set_theme_mod('background_image', '');
			set_theme_mod('background_image_thumb', '');

			$color_manager_array = get_theme_mod( 'color_manager' );
			unset( $color_manager_array['body'] );
			unset( $color_manager_array[$key]['background_repeat'] );
			unset( $color_manager_array[$key]['background_position_x'] );
			unset( $color_manager_array[$key]['background-attachment'] );
			if( empty( $color_manager_array[$key] ))
				unset( $color_manager_array[$key] ); //var_dump( $color_manager_array );
			set_theme_mod( 'color_manager', $color_manager_array );

			$this->updated = true;
			return;
		}

		if ( isset($_POST['remove-custom-image']) ) {
			// @TODO: Uploaded files are not removed here.
			check_admin_referer('remove-custom-image', '_wpnonce-remove-custom-image');

			// var_dump( $_POST['remove-custom-image'] );
			foreach( array_keys( $_POST['remove-custom-image'] ) as $key ){
				$color_manager_array = get_theme_mod( 'color_manager' );
				unset( $color_manager_array[$key]['url'] );
				unset( $color_manager_array[$key]['background_repeat'] );
				unset( $color_manager_array[$key]['background_position_x'] );
				unset( $color_manager_array[$key]['background-attachment'] );
				if( empty( $color_manager_array[$key] ))
					unset( $color_manager_array[$key] ); //var_dump( $color_manager_array );
				set_theme_mod('color_manager', $color_manager_array );
			}
			$this->updated = true;
			return;
		}

		if ( isset($_POST['image-part-option']) ){

			if ( isset($_POST['background-repeat']) ) {
				check_admin_referer('color-manager');
				if ( in_array($_POST['background-repeat'], array('repeat', 'no-repeat', 'repeat-x', 'repeat-y')) )
					$repeat = $_POST['background-repeat'];
				else
					$repeat = 'repeat';

				if ( $_POST['image-part-option'] === 'body' )
					set_theme_mod('background_repeat', $repeat);
			}

			if ( isset($_POST['background-position-x']) ) {
				check_admin_referer('color-manager');
				if ( in_array($_POST['background-position-x'], array('center', 'right', 'left')) )
					$position = $_POST['background-position-x'];
				else
					$position = 'left';

				if ( $_POST['image-part-option'] === 'body' )
					set_theme_mod('background_position_x', $position);
			}

			if ( isset($_POST['background-attachment']) ) {
				check_admin_referer('color-manager');
				if ( in_array($_POST['background-attachment'], array('fixed', 'scroll')) )
					$attachment = $_POST['background-attachment'];
				else
					$attachment = 'fixed';

				if ( $_POST['image-part-option'] === 'body' )
					set_theme_mod('background_attachment', $attachment);
			}

			$image_part_option = $_POST['image-part-option'];
			$color_manager_array = get_theme_mod('color_manager' );
			if( !empty( $color_manager_array[$image_part_option]['url'] )){

				$color_manager_array[$image_part_option]['background_repeat'] = $repeat;
				$color_manager_array[$image_part_option]['background_position_x'] = $position;
				$color_manager_array[$image_part_option]['background-attachment'] = $attachment;
				set_theme_mod('color_manager', $color_manager_array );

			}

		}

		if ( isset($_POST['background-color']) ) {
			check_admin_referer('color-manager');
			$color = preg_replace('/[^0-9a-fA-F]/', '', $_POST['background-color']);
			if ( strlen($color) == 6 || strlen($color) == 3 )
				set_theme_mod('background_color', $color);
			else
				set_theme_mod('background_color', '');
		}

		if ( isset($_POST['background_part']) ) {

			$background_part_array = array();
			foreach( array_keys( $_POST['background_part'] ) as $key ){

				if( !empty( $_POST['background_part'][$key]['key'] )){
					$background_part_array[ esc_attr($key) ] = array(
						'name'     => esc_attr( $_POST['background_part'][$key]['name'] ),
						'selector' => esc_attr( $_POST['background_part'][$key]['selector'] ),
						'type'     => esc_attr( 'content' ),
					);
				}
			}
			set_theme_mod('background_part', $background_part_array );

		}
		if ( isset($_POST['background_part_new']) ) {

			if( !empty( $_POST['background_part_new']['key'] )){

				$background_part_array = get_theme_mod('background_part'); 
				$background_part_array[ esc_attr( $_POST['background_part_new']['key'] ) ] = array(
					'name'     => esc_attr( $_POST['background_part_new']['name'] ),
					'selector' => esc_attr( $_POST['background_part_new']['selector'] ),
					'type'     => esc_attr( 'content' ),
				);
				set_theme_mod('background_part', $background_part_array );
			}
		}

		$color_manager_array = array();
		$color_manager_post = $_POST['color-manager'];
		
		if ( !empty($color_manager_post) ) {
			foreach ( array_keys( $color_manager_post ) as $key1 ){
				foreach ( array_keys( $color_manager_post[$key1] ) as $key2 ){

					
					if( in_array( (string)$key2, get_color_option_array())){

						if ( isset($color_manager_post[$key1][$key2]) ) {
							check_admin_referer('color-manager');
							$color = preg_replace('/[^0-9a-fA-F]/', '', $color_manager_post[$key1][$key2] );
							if ( strlen($color) == 6 || strlen($color) == 3 )
								$color_manager_array[$key1][$key2] = $color;
							else
								$color_manager_array[$key1][$key2] = '';
						}

					} elseif( in_array( (string)$key2, get_size_option_array())){

						if ( isset($color_manager_post[$key1][$key2]) ) {
							check_admin_referer('color-manager');
							$color_manager_array[$key1][$key2] = preg_replace('/[^0-9]/', '', $color_manager_post[$key1][$key2] );
						}

					} elseif( in_array( (string)$key2, get_setting_option_array())){

						if ( isset($color_manager_post[$key1][$key2]) ) {
							check_admin_referer('color-manager');
							$color_manager_array[$key1][$key2] = esc_attr( $color_manager_post[$key1][$key2] );
						}

					} else {

						apply_filters( 'set_color_manager_array', $color_manager_array, $key1, $key2, $color_manager_post );
						// Nothing Update
					}
				}
			}
			set_theme_mod('color_manager', $color_manager_array ); // var_dump( $color_manager_array );
			$this->updated = true;
		}

		$set_color_manager_type = $_POST['set-custom-backgrond-type'];
		$color_manager_type     = $_POST['custom-backgrond-type'];
		if ( !empty( $set_color_manager_type ) && !empty( $color_manager_type ) ){

			if( empty( $color_manager_array ))
				$color_manager_array = get_theme_mod('color_manager', array());

			$color_manager_type_array = get_theme_mod( 'color_manager_type', array());
			$color_manager_type_array[$color_manager_type]['color_manager'] = $color_manager_array;
			set_theme_mod( 'color_manager_type' , $color_manager_type_array );

			$this->updated = true;
		}

		$set_color_manager_type_description = $_POST['set-custom-backgrond-type-description'];
		$color_manager_type_description = $_POST['custom-backgrond-type-description'];
		if ( !empty( $set_color_manager_type_description ) && !empty( $color_manager_type ) ){

			$color_manager_type_array   = get_theme_mod( 'color_manager_type' , array()); // var_dump( $color_manager_type_array ); 
			$color_manager_type_array[$color_manager_type]['description'] = $color_manager_type_description;
			set_theme_mod( 'color_manager_type' , $color_manager_type_array );
		}
	}

	/**
	 * Display the color manager page.
	 *
	 * @since 3.0.0
	 */
	function admin_page() {

		$color_manager_array = get_theme_mod('color_manager'); //var_dump( $color_manager_array );
		$this->color_manager_array = $color_manager_array; // var_dump( $this->color_manager_array );
		$this->background_part_array = get_background_part_array(); 

?>
<div class="wrap" id="color-manager">
<?php screen_icon(); ?>
<h2><?php _e('Custom Background'); ?></h2>
<?php if ( !empty($this->updated) ) { ?>
<div id="message" class="updated">
<p><?php printf( __( 'Background updated. <a href="%s">Visit your site</a> to see how it looks.' ), home_url( '/' ) ); ?></p>
</div>
<?php } ?>
<br />
<div class="notice">
id&nbsp;で指定された要素の背景色や背景画像を自由に変更することができるプラグインです。&nbsp;（&lt;div id="○○"&gt;&nbsp;←コレ&lt;/div&gt;&nbsp;&nbsp;）&nbsp;<br />
<hr />
<strong style="display:inline-block; width:180px"><span>(1) 背景画像を設定する</strong>：&nbsp;<a href="#display-image"><strong>背景画像</strong></a>&nbsp;で画像をアップロードし、&nbsp;<a href="#display-option"><strong>オプション</strong></a>&nbsp;で設定を変更します。設定確認と削除は&nbsp;<a href="#display-setting"><strong>背景設定</strong></a>&nbsp;にておこないます<br /></span>
<strong style="display:inline-block; width:180px"><span>(2) 背景色を設定する</strong>：&nbsp;<a href="#display-setting"><strong>背景設定</strong></a>&nbsp;の背景色などを選択して、右側のパレットで好きな色を選択して「保存」を押します。<br /></span>
<strong style="display:inline-block; width:180px"><span>(3) 対象要素を設定する</strong>：&nbsp;<a href="#background-setting"><strong>対象設定</strong></a>&nbsp;で id を入力して保存して下さい。表示名とセレクタは任意です&nbsp;※セレクタは id だけでは優先順位が低い場合にお使い下さい<br /></span>
<strong style="display:inline-block; width:180px"><span>(4) 配色結果を確認する</strong>：&nbsp;反映されていない場合は<a href="#background-setting"><strong>適用されるＣＳＳ</strong></a>&nbsp;でＣＳＳ確認ください。また他に優先順位の高い設定がないか FireBug などで確認ください。<br /></span>
</div>
<h3 id="display-image"><?php _e('Background Image'); ?></h3>
<table class="form-table">
<tbody>

<?php if ( defined( 'BACKGROUND_IMAGE' ) ) : // Show only if a default background image exists ?>
<tr valign="top">
<th scope="row"><?php _e('Restore Original Image'); ?></th>
<td>
<form method="post" action="">
<?php wp_nonce_field('color-manager-reset', '_wpnonce-color-manager-reset'); ?>
<input type="submit" class="button" name="reset-background" value="<?php esc_attr_e('Restore Original Image'); ?>" /><br/>
<?php _e('This will restore the original background image. You will not be able to restore any customizations.') ?>
</form>
</td>
</tr>

<?php endif; ?>
<tr valign="top">
<th scope="row"><?php _e('Upload Image'); ?></th>
<td><form enctype="multipart/form-data" id="upload-form" method="post" action="">
<label for="upload"><?php _e('Choose an image from your computer:'); ?></label><br /><input type="file" id="upload" name="body" /><br />
<input type="hidden" name="action" value="save" />
<select name="image-part" id="image-part">
<?php	foreach( array_keys( $this->background_part_array ) as $key ){
		if( $this->background_part_array[$key]['type'] === 'navigation-02' ){
			echo '<option value="'. $key .'-background">'. $this->background_part_array[$key]['name'] .' 背景';
			echo '<option value="'. $key .'-panel">'. $this->background_part_array[$key]['name'] .' パネル' ;
		} else {
			echo '<option value="'. $key .'">'. $this->background_part_array[$key]['name']  ; 
		}
	}
?>
</select>
<?php wp_nonce_field('color-manager-upload', '_wpnonce-color-manager-upload') ?>
<input type="submit" class="button" value="<?php esc_attr_e('Upload'); ?>" /><br />
表示方法の設定は「背景画像オプション」、削除は「サイトの詳細設定」でおこなってください。
</p>
</form>
</td>
</tr>
</tbody>
</table>

<h3 id="display-option">背景画像オプション</h3>
<form method="post" action="">
<table class="form-table display-option">
<tbody>
<tr valign="top">
<th scope="row"><?php echo '表示位置'; // _e( 'Position' ); ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e( 'Background Position' ); ?></span></legend>
<label>
<input name="background-position-x" type="radio" value="left"<?php checked('left', get_theme_mod('background_position_x', 'left')); ?> />
<?php _e('Left') ?>
</label>
<label>
<input name="background-position-x" type="radio" value="center"<?php checked('center', get_theme_mod('background_position_x', 'left')); ?> />
<?php _e('Center') ?>
</label>
<label>
<input name="background-position-x" type="radio" value="right"<?php checked('right', get_theme_mod('background_position_x', 'left')); ?> />
<?php _e('Right') ?>
</label>
</fieldset></td>
</tr>

<tr valign="top">
<th scope="row"><?php _e( 'Repeat' ); ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e( 'Background Repeat' ); ?></span></legend>
<label><input type="radio" name="background-repeat" value="no-repeat"<?php checked('no-repeat', get_theme_mod('background_repeat', 'repeat')); ?>> <?php _e('No Repeat'); ?></option></label>
	<label><input type="radio" name="background-repeat" value="repeat"<?php checked('repeat', get_theme_mod('background_repeat', 'repeat')); ?>> <?php _e('Tile'); ?></option></label>
	<label><input type="radio" name="background-repeat" value="repeat-x"<?php checked('repeat-x', get_theme_mod('background_repeat', 'repeat')); ?>> <?php _e('Tile Horizontally'); ?></option></label>
	<label><input type="radio" name="background-repeat" value="repeat-y"<?php checked('repeat-y', get_theme_mod('background_repeat', 'repeat')); ?>> <?php _e('Tile Vertically'); ?></option></label>
</fieldset></td>
</tr>

<tr valign="top">
<th scope="row"><?php _e( 'Attachment' ); ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e( 'Background Attachment' ); ?></span></legend>
<label>
<input name="background-attachment" type="radio" value="scroll" <?php checked('scroll', get_theme_mod('background_attachment', 'scroll')); ?> />
<?php _e('Scroll') ?>
</label>
<label>
<input name="background-attachment" type="radio" value="fixed" <?php checked('fixed', get_theme_mod('background_attachment', 'scroll')); ?> />
<?php _e('Fixed') ?>
</label>
</fieldset></td>
</tr>
<tr><th></th><td>
<select name="image-part-option" id="image-part-option">
<?php	foreach( array_keys( $this->background_part_array ) as $key ){
		if( !empty( $color_manager_array[$key]['url'] )){
			if( $this->background_part_array[$key]['type'] === 'navigation-02' ){
				echo '<option value="'. $key .'-background">'. $this->background_part_array[$key]['name'] .' 背景';
				echo '<option value="'. $key .'-panel">'. $this->background_part_array[$key]['name'] .' パネル' ;
			} else {
				echo '<option value="'. $key .'">'. $this->background_part_array[$key]['name'] ;
			}
		}
	}
?>
</select>
<?php wp_nonce_field('color-manager'); ?>
<input type="submit" class="button-primary" name="save-background-options" value="<?php esc_attr_e('Save Changes'); ?>" />
</td></tr>
</tbody>
</table>
</form>
<br id="display-setting" /><hr />
<h3>背景設定</h3>
<form method="post" action="">
<p class="submit"><input type="submit" class="button-primary" name="save-background-options" value="背景設定を保存" /></p>
<table class="form-table display-setting">
<tbody>

<tr valign="top"><?php $this->input_column( 'body', 'body （全体）<br /><div class="body" style="height:40px; width:120px; line-height:40px; text-align:center; text-shadow:none;">body</div>', '背景色',false , false, false, false, false, false, false, false, false, false, true, true  ); ?>
<td rowspan="11" class="palette-wrapper"><?php
	$this->custom_palette('black');
	$this->custom_palette('blue');
	$this->custom_palette('red');
	$this->custom_palette('green');
?></td></tr>
<?php	foreach( array_keys( $this->background_part_array ) as $key ){ ?>

		<tr valign="top">
<?php
		if( $key === 'body' ){
			// already shown
		} elseif( $this->background_part_array[$key]['type'] === 'navigation-01' ){
			$this->input_column( $key, (empty( $this->background_part_array[$key]['name'] ) ? $key : $this->background_part_array[$key]['name'] ) .'<br /><div class="'. $key .'" class="large-navigation" style="width:120px; height:32px; margin:5px auto auto 0;"><ul class="menu"><li style="width:30px; height:32px;"></li><li style="width:30px; height:32px;"></li></ul><div>', '背景色', true, true, true, true, false, false, true, true, true, true, false, true );
		} elseif( $this->background_part_array[$key]['type'] === 'navigation-02' ){
			$this->input_column( $key, (empty( $this->background_part_array[$key]['name'] ) ? $key : $this->background_part_array[$key]['name'] ) .'<br /><div class="'. $key .'" class="large-navigation" style="width:120px; height:32px; margin:5px auto auto 0;"><ul class="menu"><li style="width:30px; height:32px;"></li><li style="width:30px; height:32px;"></li></ul><div>', '背景色', true, true, true, true, 'ナビの高さ', 'パネルの幅', true, true, true, true, false, true );
		} else {
			$this->input_column( $key, (empty( $this->background_part_array[$key]['name'] ) ? $key : $this->background_part_array[$key]['name'] ) .'<br /><div class="'. $key .'" style="height:40px; width:120px; line-height:40px; text-align:center; text-shadow:none;">'. $key .'</div>', '背景色',false , '線の色', true, true, false, false, false, true, true, true, true, true  );
		}
?>
		</tr>
<?php	} ?>
</tbody>
</table>
<?php wp_nonce_field('color-manager'); ?>
<p class="submit"><input type="submit" class="button-primary" name="save-background-options" value="背景設定を保存" /></p>
</form>
</div>
<?php
	$theme = get_current_theme(); //var_dump( $theme );
	$template = get_template(); //var_dump( $template );
	get_background_part_array
?>
<hr id="background-setting" />
<h3>対象設定</h3>
<form method="post" action="">
<table class="form-table background-setting">
<tbody><th>
テーマ：<?php echo $theme; ?><br />
親テーマ：<?php echo $template; ?>
<input type="hidden" name="theme" value="<?php echo $theme; ?>">
<input type="hidden" name="template" value="<?php echo $template; ?>">
</th>
<td>
	<table class="form-table wp-list-table widefat fixed">
	<thead><tr><th>id（必須）</th><th>表示名</th><th>セレクタ</th></tr></thead>
	<tfoot><tr><th>id（必須）</th><th>表示名</th><th>セレクタ</th></tr></tfoot>
	<tbody>
	<?php foreach( array_keys( $this->background_part_array ) as $key ){ ?>

		<tr>
		<td><input type="text" name="background_part[<?php echo $key; ?>][key]" id="background_part[$key][key]" value="<?php echo esc_attr( $key ) ?>" /><br /></td>
		<td><input type="text" name="background_part[<?php echo $key; ?>][name]" id="background_part[$key][name]" value="<?php echo esc_attr( $this->background_part_array[$key]['name'] ) ?>" /><br /></td>
		<td><input type="text" name="background_part[<?php echo $key; ?>][selector]" id="background_part[$key][selector]" value="<?php echo esc_attr( $this->background_part_array[$key]['selector'] ) ?>" /><br /></td>
		</tr>

	<?php } ?>

		<tr>
		<td><input type="text" name="background_part_new[key]" id="background_part[$key][key]" value="" /><br /></td>
		<td><input type="text" name="background_part_new[name]" id="background_part[$key][name]" value="" /><br /></td>
		<td><input type="text" name="background_part_new[selector]" id="background_part[$key][selector]" value="" /><br /></td>
		</tr>
	</tbody>
	</table>
</td>
</tbody>
</table>
<?php wp_nonce_field('color-manager-id'); ?>
<p class="submit"><input type="submit" class="button-primary" name="save-background-options" value="対象設定を保存" /></p>
</form>
<hr id="apply-css" />
<strong style="margin-left:2%;">適用されるＣＳＳ</strong>
<div class="notice">
<?php color_manager_css(); ?>
</div>
<?php
	}

	/**
	 * Handle a Image upload for the background image.
	 *
	 * @since 3.0.0
	 */
	function handle_upload() {

		if ( empty($_FILES) )
			return;

		// var_dump( $_FILES );

		check_admin_referer('color-manager-upload', '_wpnonce-color-manager-upload');
		$overrides = array('test_form' => false);

		$color_manager_array = get_theme_mod('color_manager'); // var_dump($background_image_array);
		foreach( array_keys( $_FILES ) as $key ){

			$file = wp_handle_upload($_FILES[$key], $overrides);

			if ( isset($file['error']) )
				wp_die( $file['error'] );

			$url = $file['url'];
			$type = $file['type'];
			$file = $file['file'];
			$filename = basename($file);

			// Construct the object array
			$object = array(
				'post_title' => $filename,
				'post_content' => $url,
				'post_mime_type' => $type,
				'guid' => $url
			);

			// Save the data
			$id = wp_insert_attachment($object, $file);

			// Add the meta-data
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );

			$color_manager_array[$key]['url'] = esc_url($url);
			set_theme_mod('color_manager', $color_manager_array );

			if( (string)$key === 'body' ){

				set_theme_mod('background_image', esc_url($url));

				$thumbnail = wp_get_attachment_image_src( $id, 'thumbnail' );
				set_theme_mod('background_image_thumb', esc_url( $thumbnail[0] ) );

				do_action('wp_create_file_in_uploads', $file, $id); // For replication

			}
			$this->updated = true;
		}
	}

	function input_column( $id, $thdiv, $color='背景色', $panel=false, $border=false , $char1=false, $char2=false, $height1=false, $width1=false, $gradation=false, $radius=false, $opacity=false, $shadow=false, $noise=false, $image1=false ){

		$color_manager_array = $this->color_manager_array ? $this->color_manager_array : array();
		$custom_option_array = $this->custom_option_array ? $this->custom_option_array : array();

		$color     = $color     === true ? '背景色' : $color;
		$panel     = $panel     === true ? 'パネル色' : $panel;
		$border    = $border    === true ? '線の色' : $border;
		$char1     = $char1     === true ? '文字色(通常)' : $char1;
		$char2     = $char2     === true ? '文字色(リンク)' : $char2;
		$height1   = $height1   === true ? '高さ(px)' : $height1;
		$width1    = $width1    === true ? '幅(px)' : $width1;
		$opacity   = $opacity   === true ? '半透明にする' : $opacity;
		$radius    = $radius    === true ? '角丸にする' : $radius;
		$shadow    = $shadow    === true ? '影をつける' : $shadow;
		$noise     = $noise     === true ? 'ノイズをかける' : $noise;
		$image1    = $image1    === true ? '背景画像' : $image1;
?>
<th scope="row"><?php echo $thdiv; ?></th>
<td class="color-setting"><fieldset class="color-field">

	<?php if( $color ){ ?>
		<label for="color-manager[<?php echo $id; ?>][color]"><?php echo $color; ?></label>
		<input type="text" name="color-manager[<?php echo $id; ?>][color]" id="color-manager[<?php echo $id; ?>][color]" class="select-color" value="#<?php echo esc_attr($color_manager_array[$id]['color']) ?>" /><br />
	<?php }  if( $panel ){ ?>
		<label for="color-manager[<?php echo $id; ?>][panel]"><?php echo $panel; ?></label>
		<input type="text" name="color-manager[<?php echo $id; ?>][panel]" id="color-manager[<?php echo $id; ?>][panel]" class="select-color" value="#<?php echo esc_attr($color_manager_array[$id]['panel']) ?>" /><br />
	<?php }  if( $border ){ ?>
		<label for="color-manager[<?php echo $id; ?>][border]"><?php echo $border; ?></label>
		<input type="text" name="color-manager[<?php echo $id; ?>][border]" id="color-manager[<?php echo $id; ?>][border]" class="select-color" value="#<?php echo esc_attr($color_manager_array[$id]['border']) ?>" /><br />
	<?php }  if( $char1 ){ ?>
		<label for="color-manager[<?php echo $id; ?>][char1]"><?php echo $char1; ?></label>
		<input type="text" name="color-manager[<?php echo $id; ?>][char1]" id="color-manager[<?php echo $id; ?>][char1]" class="select-color" value="#<?php echo esc_attr($color_manager_array[$id]['char1']) ?>" /><br />
	<?php }  if( $char2 ){ ?>
		<label for="color-manager[<?php echo $id; ?>][char2]"><?php echo $char2; ?></label>
		<input type="text" name="color-manager[<?php echo $id; ?>][char2]" id="color-manager[<?php echo $id; ?>][char2]" class="select-color" value="#<?php echo esc_attr($color_manager_array[$id]['char2']) ?>" /><br />
	<?php }  if( $height1 ){ ?>
		<label for="color-manager[<?php echo $id; ?>][height1]"><?php echo $height1; ?></label>
		<input type="text" name="color-manager[<?php echo $id; ?>][height1]" id="color-manager[<?php echo $id; ?>][height1]" class="input-length" value="<?php echo esc_attr($color_manager_array[$id]['height1']) ?>" /><br />
	<?php }  if( $width1 ){ ?>
		<label for="color-manager[<?php echo $id; ?>][width1]"><?php echo $width1; ?></label>
		<input type="text" name="color-manager[<?php echo $id; ?>][width1]" id="color-manager[<?php echo $id; ?>][width1]" class="input-length" value="<?php echo esc_attr($color_manager_array[$id]['width1']) ?>" /><br />
	<?php } if( $radius ){ ?>
		<label for="color-manager[<?php echo $id; ?>][radius]"><?php echo $radius; ?></label>
		<input type="checkbox" name="color-manager[<?php echo $id; ?>][radius]" id="color-manager[<?php echo $id; ?>][radius]" value="checked" <?php echo esc_attr($color_manager_array[$id]['radius']) ?> /><br />
	<?php } if( $shadow ){ ?>
		<label for="color-manager[<?php echo $id; ?>][shadow]"><?php echo $shadow; ?></label>
		<select name="color-manager[<?php echo $id; ?>][shadow]" id="color-manager[<?php echo $id; ?>][shadow]" class="select-shadow">
			<option value="off" <?php echo selected( 'off', $color_manager_array[$id]['shadow']) ?>>なし</option>
			<option value="normal" <?php echo selected( 'normal', $color_manager_array[$id]['shadow']) ?>>普通</option>
			<option value="strong" <?php echo selected( 'strong', $color_manager_array[$id]['shadow']) ?>>強い</option>
			<option value="weak" <?php echo selected( 'weak', $color_manager_array[$id]['shadow']) ?>>弱い</option>
		</select><br />
	<?php } if( $noise ){ ?>
		<label for="color-manager[<?php echo $id; ?>][noise]" class="select"><?php echo $noise; ?></label>
		<select name="color-manager[<?php echo $id; ?>][noise]" id="color-manager[<?php echo $id; ?>][noise]" class="select-noise">
			<option value="off" <?php echo selected( 'off', $color_manager_array[$id]['noise']) ?>>なし</option>
			<option value="noise"   <?php echo selected( 'noise',   $color_manager_array[$id]['noise']) ?>>ノイズ</option>
			<option value="texture" <?php echo selected( 'texture', $color_manager_array[$id]['noise']) ?>>テクスチャ</option>
			<option value="stripe"  <?php echo selected( 'stripe',  $color_manager_array[$id]['noise']) ?>>ストライプ</option>
		</select><br />
	<?php } ?>
	<?php if( $image1 ){ ?>

		<label>背景画像</label>

		<?php if( !empty( $color_manager_array[$id]['url'] )) { ?>

		<img style="max-height:16px; margin: 0 0 -3px 3px;" src="<?php echo $color_manager_array[$id]['url']; ?>" />
		<?php wp_nonce_field('remove-custom-image', '_wpnonce-remove-custom-image'); ?>
		<?php $background_repeat = empty( $color_manager_array[$id]['background_repeat'] ) ? 'no-repeat' : $color_manager_array[$id]['background_repeat']; ?>
		<?php $background_position_x = empty( $color_manager_array[$id]['background_position_x'] ) ? 'left' : $color_manager_array[$id]['background_position_x']; ?>
		<?php $background_attachment = empty( $color_manager_array[$id]['background-attachment'] ) ? 'scroll' : $color_manager_array[$id]['background-attachment']; ?>
		<?php 	echo '（&nbsp;'.$custom_option_array["background-position-x"][$background_position_x].'、' ;
			echo $custom_option_array["background-repeat"][$background_repeat].'、' ;
			echo $custom_option_array["background-attachment"][$background_attachment].'&nbsp;）' ; ?>
		<input type="submit" class="button" name="remove-custom-image[<?php echo $id; ?>]" value="削除" />

		<?php } ?><br />
	<?php } ?>

</fieldset></td>
<?php
}

	/** -------------------------------------------------
	 *  custom_palette
	 * -------------------------------------------------- */
	function custom_palette( $color_name, $total_width=360 ){

		$color_all = array(
			array(' CFF ','','','','',' CCF','','','','',' FCF','','','','',' FCC','','','','',' FFC','','','','',' CFC ','','','',''),
			array(' 9FF ',' 9CF','','','',' 99F','','','',' C9F',' F9F',' F9C','','','',' F99','','','',' FC9',' FF9',' CF9 ','','','',' 9F9','','','',' 9FC '),
			array(' 6FF ',' 6CF',' 69F','','',' 66F','','',' 96F',' C6F',' F6F',' F6C',' F69','','',' F66','','',' F96',' FC6',' FF6',' CF6',' 9F6 ','','',' 6F6','','',' 6F9 ',' 6FC '),
			array(' 3FF ',' 3CF',' 39F',' 36F','',' 33F','',' 63F',' 93F',' C3F',' F3F',' F3C',' F39',' F36','',' F33','',' F63',' F93',' FC3',' FF3',' CF3',' 9F3',' 6F3 ','',' 3F3','',' 3F6 ',' 3F9 ',' 3FC '),
			array(' 0FF ',' 0CF',' 09F',' 06F',' 03F',' 00F',' 30F',' 60F',' 90F',' C0F',' F0F',' F0C',' F09',' F06',' F03',' F00',' F30',' F60',' F90',' FC0',' FF0',' CF0',' 9F0',' 6F0',' 3F0 ',' 0F0',' 0F3 ',' 0F6 ',' 0F9 ',' 0FC '),
			array(' 0CC ',' 09C',' 06C',' 03C','',' 00C','',' 30C',' 60C',' 90C',' C0C',' C09',' C06',' C03','',' C00','',' C30',' C60',' C90',' CC0',' 9C0',' 6C0',' 3C0 ','',' 0C0','',' 0C3 ',' 0C6 ',' 0C9 '),
			array(' 099 ',' 069',' 039','','',' 009','','',' 309',' 609',' 909',' 906',' 903','','',' 900','','',' 930',' 960',' 990',' 690',' 390 ','','',' 090','','',' 093 ',' 096 '),
			array(' 066 ',' 036','','','',' 006','','','',' 306',' 606',' 603','','','',' 600','','','',' 630',' 660',' 360 ','','','',' 060','','','',' 063 '),
			array(' 033 ','','','','',' 003','','','','',' 303','','','','',' 300','','','','',' 330','','','','',' 030 ','','','',''),
			array(' 3CC ',' 39C',' 36C','','',' 33C','','',' 63C',' 93C',' C3C',' C39',' C36','','',' C33','','',' C63',' C93',' CC3',' 9C3',' 6C3 ','','',' 3C3','','',' 3C6 ',' 3C9 '),
			array(' 6CC ',' 69C','','','',' 66C','','','',' 96C',' C6C',' C69','','','',' C66','','','',' C96',' CC6',' 9C6 ','','','',' 6C6','','','',' 6C9 '),
			array(' 399 ',' 369','','','',' 339','','','',' 639',' 939',' 936','','','',' 933','','','',' 963',' 993',' 693 ','','','',' 393','','','',' 396 '),
			array(' 9CC ','','','','',' 99C','','','','',' C9C','','','','',' C99','','','','',' CC9','','','','',' 9C9 ','','','',''),
			array(' 699 ','','','','',' 669','','','','',' 969','','','','',' 966','','','','',' 996','','','','',' 696 ','','','',''),
			array(' 366 ','','','','',' 336','','','','',' 636','','','','',' 633','','','','',' 663','','','','',' 363 ','','','',''),
		);
		$color_black = array(
			array(' 000 ',' 333',' 666',' 999',' CCC',' FFF '),
		);
		echo '<div class="palette palette-'.$color_name.' clear" style="width:'.$total_width.'px;" >';

		if( $color_name === 'black' ) $color_all = $color_black;

		foreach( $color_all as $color_line ){

			if( $color_name === 'red' ){
				for( $j = 1; $j <= 10; $j++ ) {
					$result = array_shift($color_line);
					$result = array_pop($color_line);
				}
			}
			if( $color_name === 'blue' ){
				for( $j = 1; $j <= 20; $j++ ) {
					$result = array_pop($color_line);
				}
			}
			if( $color_name === 'green' ){
				for( $j = 1; $j <= 20; $j++ ) {
					$result = array_shift($color_line);
				}
			}
			/** --------   幅や高さを調整する   -------- */
			$count = 0;
			foreach ( $color_line as $color_count ) if(!empty($color_count)) $count = $count +1;
			$count !== 0 ?	$width  = $total_width/$count : $width = $total_width;
			$count <=  6 ?  $height = 20 : ( $count <=  12 ?  $height= 22 : ( $count <=  18 ?  $height= 26 : $height = 32 ) );

			foreach ( $color_line as $color ){
				if(!empty( $color )){
					echo '<div style="color: '.$strcolor.'; width:'. $width .'px; height:'. $height .'px; line-height:'. $height .'px; background: #'. trim($color) .'">#'. trim($color) .'</div>';
				}
			}echo '<br style="clear:left" />'; 
		} echo '</div><br style="clear:left" />';
	}
}

function color_manager_css( $color_manager_array = array(), $admin=false, $type='#' ){

	if( empty( $color_manager_array )){
		$color_manager_array = get_theme_mod('color_manager'); // echo '$color_manager_array='; var_dump( $color_manager_array );
	}
	$background_part_array = get_background_part_array(); // var_dump( $background_part_array );

	// var_dump( $color_manager_array );
	if ( !empty($color_manager_array) ) { //var_dump( $color_manager_array );

		foreach ( array_keys( $color_manager_array ) as $key1 ){

			$selector = ( empty( $background_part_array[ $key1 ]['selector'] ) || $admin ) ? '' : $background_part_array[ $key1 ]['selector'];

			/* ------------------------------------------------------
			 * ready of background image
			 * ------------------------------------------------------ */
			$background_image  = '';
			$background_repeat = '';
			$background_position   = '';
			$background_attachment = '';

			if( !empty( $color_manager_array[$key1]['url'] )){

				$background_image      = $color_manager_array[$key1]['url'] ;
				$background_repeat     = empty( $color_manager_array[$key1]['background_repeat'] ) ? 'no-repeat' : $color_manager_array[$key1]['background_repeat'];
				$background_position   = empty( $color_manager_array[$key1]['background_position_x'] ) ? 'left' : $color_manager_array[$key1]['background_position_x'];
				$background_attachment = empty( $color_manager_array[$key1]['background-attachment'] ) ? 'scroll' : $color_manager_array[$key1]['background-attachment'];

			}

			/* ------------------------------------------------------
			 * create base color css
			 * ------------------------------------------------------ */

			if( !empty( $color_manager_array[$key1] )){

				$shadow          = (string)$color_manager_array[$key1]['shadow'];
				$shadow_css      = (  $shadow === 'normal' ) ? '-webkit-box-shadow : 0 6px 10px rgba(80,80,80,.5), inset 0px 0px 10px 0px #fff; -moz-box-shadow : 0 6px 10px rgba(80,80,80,.5), inset 0px 0px 10px 0px #fff; box-shadow : 0 6px 10px rgba(80,80,80,.5), inset 0px 0px 10px 0px #fff; '
				                 : (( $shadow === 'strong' ) ? '-webkit-box-shadow : 0 6px 30px 2px rgba(0,0,0,1), inset 0px 0px 10px 0px #fff; -moz-box-shadow : 0 6px 30px 2px rgba(0,0,0,1), inset 0px 0px 10px 0px #fff; box-shadow : 0 6px 30px 2px rgba(0,0,0,1), inset 0px 0px 10px 0px #fff; '
			        	         : (( $shadow === 'weak'   ) ? '-webkit-box-shadow : 0 6px 10px rgba(128,128,128,.5), inset 0px 0px 10px 0px #fff; -moz-box-shadow : 0 6px 10px rgba(128,128,128,.5), inset 0px 0px 10px 0px #fff; box-shadow : 0 6px 10px rgba(128,128,128,.5), inset 0px 0px 10px 0px #fff; ' : '' ));
				$noise           = (string)$color_manager_array[$key1]['noise'];
				$noise_css       = (  $noise === 'texture' ) ? 'background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAeCAYAAAA7MK6iAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAABTlJREFUeNq0l1dP61oQhbchpiQU06MNAgnE//8jCIF4QYIHagiEYlooptz1zc1ETk4OR7plS5adXWbPrFlTkmxvb8fv7+9Qq9XC+/t7WFxctO/Hx8fQbrfDw8NDWFpasrXZ2Vl7z8/PN0JpaG9kL2NiYiIMDQ2FNE3z5+fnbHh4OCRJgsyeM4FLy8/r62uU8Gr/PM/Ly0vPmpSKzA3aW34+Pz/tXRRF92wl9I23t7fw9fWVabT71zSPxdnFxUVWqVTs9+TkZBgbG+vueXp6isxjqVsJAre3t/Hq6iqMjo5moJpcX1+jdfj4+AgrKyu5BLb1XeXNIV20IMtS4B8fH2+EAYP9zWYzE7y4oalLv5gHDd/TfzYBOh2wSwTJEIfQGM1lScH8yMhI6/z8PLq/5OtccNW0P4UDrVbL5kFhbm7OoOxYaZzAKNZ4Azvfyc7OTuRgjDHc398bdGzm8jzPjWwubH9/3y5AKAMBwIxAzkAwBPNeWFgwMk5NTRlRxR2bR77gDhUuBUYErq2tBfedLLZ5LuGRoFyuaJchhLmyKuNbLgurq6vmW2CFSI7koJFgST+B3KIyYSCdtM9/ElYeXHx3d5cB9/r6eiGDWsiV1VHzf18MgRRzKcJhHhvQvlqt5lKkJhanKASMsgahxn4GbpiZmTFCSUYEVlCEaKCHfMkxuZwBkeXl5ZDs7e1FhDHw8++Y64PYleUD92ANSjkPlEy6+zBOaynfhFnCZjZpc3Fzc5NChk6IBFlgWovV+NXcwDxaQxAQ4CJIg4V+IeuswRN3HXKdjMBfOTw8NNYp/lIggn2Xl5cGEZs6KBhcCOGNEuxhP4owX6/XLQK4FCUgpmSaAuyXOwop8CzlXhU99YSMIm2aWqxzqBPPXJp7EhEZIgoRCmX4/s34hdX9g0uJZ5QhLoELhLBcShjLsRiX4Rrk4QaslussvjkDsTjDAMmK2BaJQaoKFnEB7+PjY2Mivzc3N6lQTX3PaS4FZsb09HRG7ONb0AJ2LkYRfA/cPCiiS5vaR1asCr2sx2JPmT/F9D8ZVDwUwdcYZdUJiLCYSWBRdjIL0NJDi4MwkXnFbaGDZjVnzs7OzAWgQ5rEco9fFOcM8ll3EloEnJ6eRnyBD8jVHGDRw4qB79AY69EYH/MbAWKrKckaBcPhxkXuayWjhiOqEKujWNja2qJ7+KWYSxGyGQUEa7vrlNFGoxG9uPNAwHLBB6E/NQeJrLTCTZJgAgYDCzBiFcJY8wSBVUC6sbFh0LFXqFmKNbbKGhIKDAZFZJCbyQWOAKFp5KKQ68Ls6OjIJj1xKCF0Y9kLhROOx+qqlCG25XPqt/HECYSsThXrxj9wWxzrwqiw+E+Swk9DShuyKMO7ghV0F8Dg3SPWAR8pEcZ6F9rpHs1SWA3sPMDoaZX8jOXArb4tdBA1JHAj0JuPqU5MsBnhpSrU7RgIEYQAq3cYZdYyz3ks8baHb88FnufNUiktFzYS2EtP9X/ASymkLxtUansylwq+hQkaYo2saCqU6sDoRYJYxz1YTQwT46BBLvCEwVlSMPGOPM54lQMNdaQh2d3dpUnvaeI8tBjeWSIUONmDH7mQNfZxCTLoRthL8pGSPxLWGgHh3tNL0WV46pP2DRICFuN3ZbWi3zWQsb9cCoE4qEcjKVkYcglJwPtivj2W+c/khOFSrMJa5kkYzlDmcI9cZXOd/G6I8ICgt8y8DcWDg4OI77yFAUpyNRYQ7CcnJ3V8iCKQpBOL0f2MPxEkGblkZISON+4wngzI2+uw/635S4ABAHag1emXmLyaAAAAAElFTkSuQmCC);'
				                 : (( $noise === 'stripe' ) ? 'background-image: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAeCAYAAAA7MK6iAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAADlJREFUeNrszTENACAQBMEHB9fiX86LQgIUJDSz9SYzunvVRUnq5TfrU2AwGAwGg8FgMBgMPrcFGACZHAMgShC9/wAAAABJRU5ErkJggg==);' 
				                 : (( $noise === 'noise'   ) ? 'background-image: url(data:image/png;base64,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);' : '' ));

				$css = '';
				$css =	( !empty( $color_manager_array[$key1]['color']  )  ? 'background-color: #'.$color_manager_array[$key1]['color'].'; ' : '').
					( !empty( $background_image )  ? 'background-image: url('.$background_image.'); background-repeat:'.$background_repeat.'; background-position: top '.$background_position.'; background-attachment: '.$background_attachment.'; ' : 
						( !empty( $color_manager_array[$key1]['noise'] )  ? $noise_css : '')).
					( !empty( $color_manager_array[$key1]['height1'] ) ? 'height: '. $color_manager_array[$key1]['height1'] . 'px; ' : '').
					( !empty( $color_manager_array[$key1]['border'] )  ? 'border: 1px solid #'.$color_manager_array[$key1]['border'].'; ' : '').
					( !empty( $color_manager_array[$key1]['radius'] )  ? 'border-radius: 4px; -webkit-border-radius: 4px; -moz-border-radius: 4px; ':'').
					( !empty( $color_manager_array[$key1]['char1'] )   ? 'color: #'.$color_manager_array[$key1]['char1'].'; ' :'').
					( !empty( $color_manager_array[$key1]['shadow'] )  ? $shadow_css :''). //'-webkit-box-shadow : 0 6px 10px rgba(112,112,112,.5), inset 0px 0px 10px 0px #fff; -moz-box-shadow : 0 6px 10px rgba(112,112,112,.5), inset 0px 0px 10px 0px #fff; box-shadow : 0 6px 10px rgba(112,112,112,.5), inset 0px 0px 10px 0px #fff; ' :'').
					( !empty( $color_manager_array[$key1]['opacity'] ) ? 'opacity: 0.8; ' :'');
				echo empty($css) ? '' : 
					((empty( $selector ) || $admin ) ? ((((string)$key1 === 'body' && !$admin ) ? '' : $type ).$key1 ) : $selector ) .' { '.$css.' } ';

				$css = '';
				$css =	( !empty( $color_manager_array[$key1]['char2'] )  ? 'color: #'.$color_manager_array[$key1]['char2'].'; ' :'');
				echo empty($css) ? '' : ((empty( $selector ) || $admin ) ? $type.$key1 : $selector ).' a:link, '.((empty( $selector ) || $admin ) ? $type.$key1 : $selector ).' a:visited  { '.$css.' } ';

				$css = '';
				$css =	( !empty( $color_manager_array[$key1]['char3'] )  ? 'color: #'.$color_manager_array[$key1]['char3'].'; ' :'');
				echo empty($css) ? '' : ((empty( $selector ) || $admin ) ? $type.$key1 : $selector ).' a:hover, '.((empty( $selector ) || $admin ) ? $type.$key1 : $selector ).' a:active  { '.$css.' } ';
			}
		}
	}
}

/** -------------------------------------------------
 *  16進数の計算 $add / true=和,false=差
 * -------------------------------------------------- */
function color_manager_addition ( $str1, $str2, $add ){

	$str1   = color_manager_3to6( $str1 ); //var_dump( $str1 );
	$str2   = color_manager_3to6( $str2 ); //var_dump( $str2 );
	$str3   = '';
	$str3_array = array(
		'r' => array( substr( $str1,0,2 ), substr( $str2,0,2 ) ),
		'g' => array( substr( $str1,2,2 ), substr( $str2,2,2 ) ),
		'b' => array( substr( $str1,4,2 ), substr( $str2,4,2 ) ),
	); //var_dump( $str3_array );
	foreach( array_keys( $str3_array ) as $color ){

		$str3_array[$color] = $add ? 
		hexdec( $str3_array[$color][0] ) + hexdec( $str3_array[$color][1] ) : 
			hexdec( $str3_array[$color][0] ) - hexdec( $str3_array[$color][1] );

		if( (int)$str3_array[$color] > 255 ) {
			$str3 .= 'ff';
		} elseif( (int)$str3_array[$color] < 0 ) {
			$str3 .= '00';
		} else {
			$str3 .= sprintf("%02s", dechex( $str3_array[$color] ));
		}

	} //var_dump( $str3 );
	return $str3;
}
function color_manager_3to6 ( $color ){

	$str_array = array_values( preg_split( '//', $color, -1, PREG_SPLIT_NO_EMPTY ));
	if ( strlen($color) == 6 ) return $color;
	if ( strlen($color) == 3 ) {
		$color_new = '';
		foreach(  $str_array as $str ) {
			$color_new .= (string)$str . (string)$str;
		}
		return $color_new;
	}
}
