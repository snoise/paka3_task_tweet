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
	private $t = "00:35:00" ;
	private $catID = "" ;

	//カスタムタクソノミーのとき(ないときはコメントアウトしてよい)
	private $post_type = "lives"; //デフォルト：post/page/カスタムタクソノミー
	private $tax_cat = "lives-cat";
	private $tax_ids = array(29); //日付の基準は[0]
	//ここまで
	
	//ツイートの検索設定
	private $word = '臼杵 OR 臼杵市 OR うすき OR 臼高  filter:images -RT' ; //(RTをのぞく場合 -RT)
//最後に書くよ
//filter:images　画像ツイートを抽出、
//filter:news　ニュース引用ツイートを抽出、
//filter:replies　返信ツイートを抽出、
//filter:videos　動画ツイートを抽出。

	private $lang = "ja" ;
	private $imgMode = 1 ; //:0普通/1画像一覧表示
	private $sort    = "asc" ;

	//やっぱり最大値はいるよツイート
	private $tweet_count     = 100; //ツイートの最大表示数(あくまでも表示)
	private $tweet_api_limit = 1; //titter apiのアクセスリミット：１００ツイート(RTも含む)/1回、通常180回/15分
	private $tweet_day_limit = 3; //一度に追加できる記事数（現在の日時よりさかのぼる日数）
	//Twitter API
	/*
	private $apiKey = 'zSmBXlxEoaTrwOX23Hdtbrt0W' ;
	private $apiSecret = 'TPORUQAxuTsicmje6mESheJZ3DkJ1ZoLpz7LsjANKHyyfSMYdN' ;
	private $accessToken = '1098563582-aQ7ngZjfCw0eHkph3cdItRmpyEcOHdSEnmDTkZT' ;
	private $accessTokenSecret = 'KUReVeDvn8CyWzDbkiCAi7xiw6dHAI0BCJH4Tihu0tXHN' ;
*/
	private $apiKey = '9qNoNnHgRqfQ3FU2M0kDRj7vL' ;
	private $apiSecret = 'wi6jCMOHYhpHFgooW9PCI9YwL7hQurocZuw6GCVLedgMfXqQj2' ;
	private $accessToken = '1098563582-eLb4iZ0lpDgz7fxeoLmlaFivKwLrv4eAREydsuo' ;
	private $accessTokenSecret = 'ksHNdcJnKM5qbiwM9pbfLbnYWd2mChn9156Q5ERWBX5oq' ;

	//時差
	private $t_zone ;

	//######################
	//コンストラクタ
	//######################
	function __construct(){
		require_once( "twitteroauth/twitteroauth.php" );
		require_once( "paka3_post_lib.php" );
		require_once( "paka3_task_tweet_view.php" );

		//時差を求める
		$this->t_zone = floor(( current_time( 'timestamp' ) - time( ) ) / 3600);
		//プラグインを有効化したとき
		if(function_exists('register_activation_hook')) {
			register_activation_hook (__FILE__ , array( $this , 'paka3_plugin_start' ) ) ;
		}
		//プラグインをストップしたとき
		if(function_exists('register_deactivation_hook')) {
			register_deactivation_hook (__FILE__ , array( $this , 'paka3_plugin_stop' ) ) ;
		}
		add_action( 'paka3_task_tweet_hook', array( $this , 'paka3_task_post_function' ) ) ;
		add_action( 'wp_enqueue_scripts' , array( 'Paka3_task_tweet_view' , 'post_css' ) ) ;
	}

	//######################
	//プラグインを有効化したときに呼ばれる関数
	//######################
	function paka3_plugin_start(){
		//(今日から)毎日タスクを実行する時間を設定する
		//管理＞設定されたタイムゾーンでの時間を設定する(13:00)
		$my_time = date( 'Y-m-d '.$this->t, current_time( 'timestamp' ) );
		//時差を引いて、UNIX時間(UTC:秒)に合わせる
		$task_time = strtotime( -1 * $this->t_zone." hour", strtotime( $my_time ) );
		wp_schedule_event( $task_time, 'daily', paka3_task_tweet_hook );
	}

	//######################
	//プラグインをストップしたときに呼ばれる関数
	//######################
	function paka3_plugin_stop(){
		wp_clear_scheduled_hook( 'paka3_task_tweet_hook' );
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

				$title = date( 'Y月m日d日のつぶやき',  $new_post_date  );
				$html = $this->myTweet( $new_post_date  );
			  
			  $newPost = array ('title'    => $title,
			  									'post_date' => $new_post_datetime,
												  'content' => $html,
												  'post_type' => $this->post_type,
												  'tax_cat'   => $this->tax_cat,
												  'tax_ids'   => $this->tax_ids);
				// 投稿オブジェクトの作成
				sleep( 5 );
				/*****/
				

				$res = $paka3_post_lib->new_my_post( $newPost, $this->catID );
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
											strtotime( -1*$this->t_zone."hour - 2day", $new_post_date  ) );//15:00
		//現在ある記事の次日（今から投稿する日付）
		$untilDate = date( "Y-m-d", 
											strtotime( -1*$this->t_zone."hour", $new_post_date ) );


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
		$last = $this->searchTweetFunc( $obj, 
														array_merge( $arrayData, array( 
																		'count' => 1, 
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
				$html = count($tweets).$paka3_task_tweet_view->html_view( 
						$tweets, 
						$new_post_date ,
						$this->t_zone, 
						$flag );
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









