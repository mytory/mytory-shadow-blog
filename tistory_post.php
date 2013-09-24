<?
/*
Plugin Name: mytory shadow blog(tistory)
Description: Ƽ���丮�� 30�п� �� ����, ��α� ���� �����Ѵ�. ���� �˻��� ������ ��α׸� �����ؼ� ���� �÷�����.
Author: Ahn, Hyoung-woo
Version: 1.0
*/

// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// date_default_timezone_set('Asia/Seoul');

include "./blogger.php";
// include "./IXR_Date.class.php"; // ������������ ���Ե� �ִ�.

function msb_publish(){

	$args = array(
		'posts_per_page' => '2',
		'order' => 'ASC',
	);
	$posts = get_posts($args);

	$blogid		= "656278";				// Blog API ID
	$username	= "mytory@gmail.com";	// tistory id
	$password	= "qjkckbme";			// tistory password
	$accept_comments	= "0";	//������ =1
	$accept_trackback	= "0";	//Ʈ������� =1
	$publish	= true;			// �������� : true - ����, false - �����
	foreach ($posts as $key => $post) {
		$cate		= "�� ����";				// ī�װ�
		$tags		= "php,phpunit";			// tag
		$content	= "tistory(Ƽ���丮) blog api test<br><b>��α� api �׽�Ʈ�Դϴ�.<b>";	// ����Ʈ ����
		$subject	= "tistory blog api test";	// ����Ʈ ����
		// �ð��� GMT�� ������ �Ѵ�.
		$ixr = new IXR_Date(strtotime('2011-09-11 12:00:00'));
		$reg_date = $ixr->getIso();

		$data = blogger_newPost($blogid, $username, $password, $cate, $tags, $content, $subject, $accept_comments, $accept_trackback, $publish, $reg_date);

		// Output some detail on what we got!
		echo var_dump($data);
	}
}
add_action('mytory_shadow_blog', 'msb_publish');

function msb_activate() {
	wp_schedule_event( time(), 'hourly', 'mytory_shadow_blog');
}
register_activation_hook( __FILE__, 'msb_activate' );

function msb_deactivate(){
	remove_action( 'mytory_shadow_blog', 'msb_publish' );
	wp_clear_scheduled_hook( 'mytory_shadow_blog' );
}
register_deactivation_hook( __FILE__, 'msb_deactivate' );
?>