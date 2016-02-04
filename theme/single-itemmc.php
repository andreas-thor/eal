<?php
/**
 * The template for displaying all single posts and attachments
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */

// include ('../includes/class.EAL_ItemMC.php');

get_header(); 
?>

<div id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		
<?php
		the_post();
		global $post;
		$item = new EAL_ItemMC();
		$item->load();
?>	

		<h1><?= $post->post_title ?></h1>
		<div><?= $item->description ?></div>
			
		<div style='background-color:F2F6FF; margin-top:2em; padding:1em;'>
			<?= $item->question ?> 
			
			<ul style='list-style: none;margin-top:1em;'>
			<?php 
				foreach ($item->answers as $a) {
					echo ("<li><input type='checkbox'>{$a['answer']}</input></li>");
				}
			?>
			</ul></div>

	</main><!-- .site-main -->
</div><!-- .content-area -->

<div class="sidebar">
	<ul>

	<li>FW: <?= EAL_Item::$levels[$item->level_FW-1] ?></li>
	<li>KW: <?= EAL_Item::$levels[$item->level_KW-1] ?></li>
	<li>PW: <?= EAL_Item::$levels[$item->level_PW-1] ?></li>

	
	<form action="wp-admin/post-new.php?post_type=review" method="post" autocomplete="off"> 
		<input type="hidden" name="item_id" value="<?= $item->id ?>">
		<input type="hidden" name="item_type" value="itemmc">
		<button type="submit" name="action" value="0">Review hinzuf&uuml;gen</button> 
	</form>

</ul>
</div>
	

<?php get_footer(); ?>
