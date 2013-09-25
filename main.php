<?
/*
Plugin Name: Mytory Shadow Blog
Description: 망할 다음
Version: 1.0
Author: Ahn, Hyoung-woo
*/
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// date_default_timezone_set('Asia/Seoul');

include_once ABSPATH . "/wp-includes/class-IXR.php";

class Mytory_Shadow_Blog {

	public function __construct(){
		add_action('msb_request', array(&$this, 'msb_publish'));
		register_activation_hook( __FILE__, array(&$this, 'msb_activate'));
		register_deactivation_hook( __FILE__, array(&$this, 'msb_deactivate'));
	}

	function send_request($requestname, $params, $url){
		$request = new IXR_Request($requestname, $params);
        $xml = $request->getXml();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, 'PHP XMLRPC 1.0');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 1);
		$results = curl_exec($ch);
		curl_close($ch);
		return $results;
	}

	public function msb_create_post_tistory($categories, $tags, $post_content, $post_title, $dateCreated){
		$blogid		= "656278";				// Blog API ID
		$username	= "mytory@gmail.com";	// tistory id
		$password	= "QJKCKBME";			// tistory API password
		$mt_allow_comments	= 0;	//댓글허용 =1
		$mt_allow_pings	= 0;	//트랙백허용 =1
		$publish	= true;			// 공개여부 : true - 공개, false - 비공개
		$url = 'http://mytory.tistory.com/api';

		$params = array(
			$blogid,
			$username,
			$password,
			array(
				'categories' => $categories,
				"mt_keywords"=> $tags,
				"description"=> $post_content,
				"title"=> $post_title,
				"mt_allow_comments"=> $mt_allow_comments,
				"mt_allow_pings"=> $mt_allow_pings,
				"dateCreated"=> $dateCreated,
			),
			$publish,
		);

		return $this->send_request('metaWeblog.newPost',$params, $url);
	}

	// 에러코드 받는 거 작업중.
	public function msb_publish(){
	    global $wp_query, $post;

		$args = array(
			'posts_per_page' => '5',
			'order' => 'ASC',
	        'post_status' => 'publish',
	        'meta_query' => array(
	            array(
	                'key' => '_send_to_tistory',
	                'compare' => 'NOT EXISTS',
	            )
	        )
		);
		$wp_query = new WP_Query($args);

		while(have_posts()){
	        the_post();

	        $tags = wp_specialchars_decode(strip_tags(get_the_tag_list('', ',')));

	        // tistory는 카테고리를 하나밖에 설정 못한다.
	        $cats = wp_get_post_categories(get_the_ID());
	        $categories = array();
	        foreach ($cats as $cat_ID) {
	        	$categories[] = get_cat_name($cat_ID);
	        }

	        $permalink = str_replace('local', 'net', $post->guid);
			$post_content = '<p class="mytory">
	                    <a href="' . $permalink . '" class="mytory__permalink">▶원문: <span class="mytory__title">' . get_the_title() . '</span></a>
	                </p>' .
	                '<link rel="canonical" href="' . $permalink . '">' .
	                get_the_content();
			$post_title = wp_specialchars_decode($post->post_title);

			// 시간은 GMT로 만들어야 한다.
			$ixr = new IXR_Date(strtotime($post->post_date_gmt));
			$dateCreated = $ixr->getIso();

			if($_SERVER['REMOTE_ADDR'] == '127.0.0.1'){
				return false;
			}

			// $categories, $tags, $post_content, $post_title, $dateCreated
			$result = $this->msb_create_post_tistory($categories, $tags, $post_content, $post_title, $dateCreated);

			$response = new IXR_Message($this->extract_xml($result));

	        if (!$response->parse()) {
	            // XML error
	            $this->error = new IXR_Error(-32700, 'parse error. not well formed');
	            return false;
	        }

	        // Is the message a fault?
	        if ($response->messageType == 'fault') {
	            $this->error = new IXR_Error($response->faultCode, $this->message->faultString);
	            return false;
	        }

	        // Message must be OK
	        update_post_meta( get_the_ID(), '_send_to_tistory', $response->params[0]);
	        update_post_meta( get_the_ID(), '_send_to_tistory_date', date('Y-m-d H:i:s'));
		}
	    wp_reset_query();
	}

	public static function msb_activate() {
		wp_schedule_event( time(), 'hourly', 'msb_request');
	    do_action('msb_request');
	}

	public static function msb_deactivate(){
		remove_action( 'msb_request', 'msb_publish' );
		wp_clear_scheduled_hook( 'msb_request' );
	}

	function extract_xml($http_response){
		$temp = explode('<?xml', $http_response);
		return '<?xml' . $temp[1];
	}
}

$mytory_shadow_blog = new Mytory_Shadow_Blog;