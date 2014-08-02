<?php
 /* 
Plugin Name: Paka3_task_tweet
Plugin URI: http://www.paka3.com/wpplugin
Description: 定期的（1日おきに）に、Twitterから取得して、記事を自動で投稿する
Author: Shoji ENDO
Version: 0.1
Author URI:http://www.paka3.com/
 */

$paka3_task_tweet = new Paka3_task_tweet ;

//エラー対策
if(!function_exists('wp_get_current_user')) {
    include(ABSPATH . "wp-includes/pluggable.php"); 
}


class Paka3_task_tweet{


	//インスタンス変数（設定）
	//Twitter API

	private $apiKey = '' ;
	private $apiSecret = '' ;
	private $accessToken = '' ;
	private $accessTokenSecret = '' ;

	//[設定]ツイートの検索設定
	private $word = '#usuki OR 臼杵 filter:images -RT' ; 
	//検索の最後に書くと幸せになれるかも
	//-RT　RTをのぞく場合 
	//filter:images　画像ツイートを抽出、
	//filter:news　ニュース引用ツイートを抽出、
	//filter:replies　返信ツイートを抽出、
	//filter:videos　動画ツイートを抽出。

	//[設定]実行スケジュール
	private $t = "9:20:00" ;

	//[設定]タイトルと状態
	private $post_title = 'うすきツイート：大分県臼杵市'; //タイトル
	private $post_content = 'Twitterから、本日の気になるツイートです。<br /><!--more-->';
	private $post_status = 'draft'; //下書き
	private $post_thumbnail_id = 363;

	//[設定]カテゴリID
	//private $catID = array(28) ;
	//カスタムタクソノミーのとき(ないときはコメントアウトしてよい)
	private $post_type = "lives"; //デフォルト：post/page/カスタムタクソノミー
	private $tax_cat = "lives-cat";
	private $tax_ids = array(183); //日付の基準は[0]
	//ここまで


	//[設定]表示設定
	private $imgMode = 0 ; //:0普通/1画像一覧表示
	private $sort    = "asc" ; //並び順
	private $lang = "ja" ;

	//[設定]表示数
	private $tweet_count     = 100; //ツイートの最大表示数(あくまでも表示)

	//*設定画面なし:チューニングが必要な場合に設定する*/
	//[設定]一度に追加できる記事数（現在の日時よりさかのぼる日数）
	private $tweet_day_limit = 5; //
	//[設定]表示数
	//※すべてが表示されない場合にはこちらを増やすと良い
	private $tweet_api_limit = 4; //titter apiのアクセスリミット：１００ツイート(RTも含む)/1回、通常180回/15分

	//時差
	private $t_zone ;
	private $hook_name = "paka3_task_tweet_hook";

	//######################
	//コンストラクタ
	//######################
	function __construct(){
		if ( !class_exists('TwitterOAuth') ) {
			require_once( "twitteroauth/twitteroauth.php" );
		}
		require_once( "paka3_post_lib.php" );
		require_once( "paka3_task_lib.php" );
		require_once( "paka3_task_tweet_view.php" );
		require_once( "paka3_task_tweet_admin.php" );

		//funcで呼び出しても良いかも
		$this->tweet_task_var( );

		//時差を求める
		$this->t_zone = floor(( current_time( 'timestamp' ) - time( ) ) / 3600);
		//プラグインを有効化したとき
		if ( function_exists( 'register_activation_hook' ) ) {
			register_activation_hook (__FILE__ , array( $this , 'paka3_plugin_start' ) ) ;
		}
		//プラグインをストップしたとき
		if ( function_exists( 'register_deactivation_hook') ) {
			register_deactivation_hook (__FILE__ , array( $this , 'paka3_plugin_stop' ) ) ;
		}
		//プラグインを削除したとき
		if ( function_exists( 'register_uninstall_hook') ) {
			register_uninstall_hook(__FILE__, 'paka3_plugin_uninstall');
		}
		


		add_action( 'paka3_task_tweet_hook', array( $this , 'paka3_task_post_function' ) ) ;
		add_action( 'wp_enqueue_scripts' , array( 'Paka3_task_tweet_view' , 'post_css' ) ) ;

		//管理画面メニューの追加
		$addmin_menu = new paka3_task_tweet_admin;
	}

	function tweet_task_var (){
		$tasktweet = get_option('paka3_task_tweet');
		if( isset( $tasktweet ) ){
			$this->apiKey = $tasktweet[ 'apiKey' ] ? $tasktweet[ 'apiKey' ] : $this->apiKey;
			$this->apiSecret = $tasktweet[ 'apiSecret' ] ? $tasktweet[ 'apiSecret' ] : $this->apiSecret;
			$this->accessToken = $tasktweet[ 'accessToken' ] ? $tasktweet[ 'accessToken' ] : $this->accessToken;
			$this->accessTokenSecret = $tasktweet[ 'accessTokenSecret' ] ? $tasktweet[ 'accessTokenSecret' ] : $this->accessTokenSecret;

			//表示設定
			$this->imgMode = $tasktweet[ 'imgMode' ] ? $tasktweet[ 'imgMode' ] : $this->imgMode;
			$this->sort = $tasktweet[ 'sort' ] ? $tasktweet[ 'sort' ] : $this->sort;
			$this->lang = $tasktweet[ 'lang' ] ? $tasktweet[ 'lang' ] : $this->lang;

			//検索ワードと時間
			$this->word = $tasktweet[ 'word' ] ? $tasktweet[ 'word' ] : $this->word;
			$this->t = $tasktweet[ 't' ] ? $tasktweet[ 't' ] : $this->t; 

			//タイトル・本文・状態・サムネイル
			$this->post_title = $tasktweet[ 'post_title' ] ? $tasktweet[ 'post_title' ] : $this->post_title;
			$this->post_content = $tasktweet[ 'post_content' ] ? $tasktweet[ 'post_content' ] : $this->post_content;
			$this->post_status = $tasktweet[ 'post_status' ] ? $tasktweet[ 'post_status' ] : $this->post_status;
			$this->post_thumbnail_id = $tasktweet[ 'post_thumbnail_id' ] ? $tasktweet[ 'post_thumbnail_id' ] : $this->post_thumbnail_id;

			//カテゴリの設定
			$this->catID = $tasktweet[ 'catID' ] ? $tasktweet[ 'catID' ] : $this->catID;
			$this->post_type = $tasktweet[ 'post_type' ] ? $tasktweet[ 'post_type' ] : $this->post_type;
			$this->tax_cat = $tasktweet[ 'tax_cat' ] ? $tasktweet[ 'tax_cat' ] : $this->tax_cat;
			$this->tax_ids = $tasktweet[ 'tax_ids' ] ? $tasktweet[ 'tax_ids' ] : $this->tax_ids;
			$this->tweet_count = $tasktweet[ 'tweet_count' ] ? $tasktweet[ 'tweet_count' ] : $this->tweet_count;
		}

	}


	//######################
	//プラグインを有効化したときに呼ばれる関数
	//######################
	function paka3_plugin_start(){
		update_option('paka3_task_tweet_hook_name', $this->hook_name);
		//初期登録（消しても良いかも）
		$opt = array("t" => $this->t,
						"word" => $this->word,
						"imgMode" => $this->imgMode,
						"sort" => $this->sort,
						"lang" => $this->lang,
						"post_title" => $this->post_title,
						"post_content" =>  $this->post_content,
						"post_status" => $this->post_status,
						"post_thumbnail_id" => $this->post_thumbnail_id,
						"catID" => $this->catID,
						"post_type" => $this->post_type,
						"tax_cat" => $this->tax_cat,
						"tax_ids" => $this->tax_ids,
						"tweet_count" => $this->tweet_count,
						"apiKey" => $this->apiKey,
						"apiSecret" => $this->apiSecret,
						"accessToken" => $this->accessToken,
						"accessTokenSecret" => $this->accessTokenSecret,
						);
		update_option('paka3_task_tweet',$opt);
	}


	//######################
	//プラグインをストップしたときに呼ばれる関数
	//######################
	function paka3_plugin_stop(){
		$hook = get_option('paka3_task_tweet_hook_name'); 
		wp_clear_scheduled_hook( $hook  );
		//delete_option('paka3_task_tweet_hook_name');
		//delete_option('paka3_task_tweet');
	}

	//######################
	//プラグインを削除したときによばれる
	//######################
	function paka3_plugin_uninstall(){
		$hook = get_option('paka3_task_tweet_hook_name'); 
		wp_clear_scheduled_hook( $hook  );
		delete_option('paka3_task_tweet_hook_name');
		delete_option('paka3_task_tweet');
	}
	//######################
	//実行する処理
	//######################
	function paka3_task_post_function() {

			//現在の日付
			$now_date = current_time( 'timestamp' );
			//既存の最新の記事の日付
			$paka3_post_lib = new Paka3_post_lib ;
			$post_date = $paka3_post_lib->new_post_date( $this->catID , 
				$this->post_type ,$this->tax_cat,$this->tax_ids)	; //日本時間だった
			if(isset($post_date)){
					$post_date = strtotime( $post_date );
			}else{
					$post_date = strtotime( '-1day', current_time( 'timestamp' ) );
			}

			//スケジュールの時間調整
			$post_datetime = date( 'Y-m-d '.$this->t, $post_date  );
			$post_date = strtotime( $post_datetime );

			//何日追加する？
			$d = ( $now_date- $post_date )/( 3600*24 );
			//追加が($this->tweet_day_limit)記事以上の場合は
			if($d > $this->tweet_day_limit) {
				$post_date = strtotime( floor($d - $this->tweet_day_limit).'day', $post_date );
				$d = $this->tweet_day_limit;
			}

			
			while( $d >= 1 && $d <= $this->tweet_day_limit) {
				//差分を撮って日付
				$new_post_date = strtotime( '1day', $post_date );
				$new_post_datetime = date( 'Y-m-d H:i:s' , $new_post_date  );

				$title = date( '-Y.m.d-',  $new_post_date  );
				$title = $this->post_title.$title;
				//データ取得
				$tweet = $this->myTweet( $new_post_date  );
			  $html = $tweet[ 'imgUrl' ][2].$tweet[ 'html' ];
				//$post_thumbnail_id = $paka3_post_lib->url_to_media( $tweet[ 'imgUrl' ][0] );
				//if($post_thumbnail_id && ! is_wp_error( $post_thumbnail_id )){
				//	$this->post_thumbnail_id = $post_thumbnail_id;
				//}

			  $newPost = array ('title'       => $title,
			  									'post_date'   => $new_post_datetime,
			  									'post_status' => $this->post_status,
			  									'thumbnail_id' => $this->post_thumbnail_id,
												  'content'     => $this->post_content.$html,
												  'post_type' => $this->post_type,
												  'tax_cat'   => $this->tax_cat,
												  'tax_ids'   => $this->tax_ids);
				// 投稿オブジェクトの作成
				sleep( 5 );
				/*****/
				

				$post_id = $paka3_post_lib->new_my_post( $newPost, $this->catID );
				//サムネイル設定
				if($post_id){
					$img_id = $paka3_post_lib->url_to_media( $post_id , $tweet[ 'imgUrl' ][0] );
					set_post_thumbnail( $post_id, $img_id );
				}
				//
				
				$post_date = $new_post_date;
				$d = $d-1;
			}			
	}



	//######################
	//Tweetの取得関数
	//######################
	function myTweet( $new_post_date ) {
		$obj = new TwitterOAuth( $this->apiKey, $this->apiSecret, $this->accessToken, $this->accessTokenSecret );
		
		//######################
		//画像のみor両方
		$flag = array(  
										'imgMode' => $this->imgMode,
										'sort'    => $this->sort,
										'count'   => $this->tweet_count) ; 
		
		//キーワード
		$arrayData = array( 'q' => sprintf(esc_html("%s"),$this->word), 
												'lang' => $this->lang, 
												'result_type' => 'recent' );


		//現在ある記事の前日
		$sinceDate = date( "Y-m-d", 
											strtotime( -1*$this->t_zone."hour - 1day", $new_post_date  ) );//15:00
		//現在ある記事の次日（今から投稿する日付）
		$untilDate = date( "Y-m-d", 
											strtotime( -1*$this->t_zone."hour + 1day", $new_post_date ) );



		//######################
		//指定日前々日の最後を取得（ただし時差なし）
		$first = $this->searchTweetFunc( $obj, 
																		array_merge( $arrayData, array( 
																				'count' => 1, 
																				'until' => $sinceDate, 
																				 ) )
																		 );
		$first_id = $first[0]->id_str;
		
		//######################
		//指定日の最後を取得（ただし時差なし）
		//countをfirstとlastで変更しているのはuntilのキャッシュ対策2014/8/1以降
		$last = $this->searchTweetFunc( $obj, 
														array_merge( $arrayData, array( 
																		'count' => 2, 
																		'until' => $untilDate, 
																		 ) )
																 );
		$last_id = $last[0]->id_str;
		//######################

		//print_r($first);
		//echo "<br>".$first_id."*".$last_id;

		//データの取得
		$tweets = array( );
		$c=0;

		

		while( floatval($last_id) > floatval($first_id) && $c < $this->tweet_api_limit ) {
						$twt = $this->searchTweetFunc( $obj, 
																		array_merge( $arrayData, array( 
																				'count' => 100, 
																				'since_id' => $first_id, 
																				'max_id' => $last_id 
																				 ) )
																		 );
						$tweets = array_merge( $tweets, $twt );



						$last = array_pop( $twt );
						//print_r($last);
						$last_id = $last->id_str;
						$c=$c + 1;
				}
		
		//表示

		if ( $tweets ) {
				//ビュークラス
				$paka3_task_tweet_view = new Paka3_task_tweet_view ;
				$html = $paka3_task_tweet_view->html_view( 
						$tweets, 
						$new_post_date ,
						$this->t_zone, 
						$flag );
				//$html = array('html'=>"","imgUrl"=>array())
		} else {
				$html = false;
		}
		return $html;
	}


	//##########################
	//search
	//##########################
	function searchTweetFunc( $obj, $array=array( 'count' => 20 ) ) {
			//Tweet取得

			$req = $obj->OAuthRequest( 'https://api.twitter.com/1.1/search/tweets.json', 
																'GET', 
																$array );
			$tweets = json_decode( $req );
			
			if( isset( $tweets ) && empty( $tweets->errors ) ) {
				return $tweets->statuses;
			}else{
				return false;
			}
	}

}//class









