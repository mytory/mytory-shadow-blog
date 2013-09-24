<?
/*
Plugin Name: mytory shadow blog(tistory)
Description: 티스토리에 30분에 한 번씩, 블로그 글을 복사한다. 다음 검색이 독립형 블로그를 차별해서 만든 플러그인.
Author: Ahn, Hyoung-woo
Version: 1.0
*/

// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// date_default_timezone_set('Asia/Seoul');

include "./blogger.php";
// include "./IXR_Date.class.php"; // 워드프레스에 포함돼 있다.

function msb_publish(){

	$args = array(
		'posts_per_page' => '2',
		'order' => 'ASC',
	);
	$posts = get_posts($args);

	$blogid		= "656278";				// Blog API ID
	$username	= "mytory@gmail.com";	// tistory id
	$password	= "qjkckbme";			// tistory password
	$accept_comments	= "0";	//댓글허용 =1
	$accept_trackback	= "0";	//트랙백허용 =1
	$publish	= true;			// 공개여부 : true - 공개, false - 비공개
	foreach ($posts as $key => $post) {
		$cate		= "웹 서버";				// 카테고리
		$tags		= "php,phpunit";			// tag
		$content	= "tistory(티스토리) blog api test<br><b>블로그 api 테스트입니다.<b>";	// 포스트 내용
		$subject	= "tistory blog api test";	// 포스트 제목
		// 시간은 GMT로 만들어야 한다.
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