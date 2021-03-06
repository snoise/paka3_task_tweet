<?php
//#################################################
//#################################################
class Paka3_task_tweet_view{
	//######################
	//コンストラクタ
	//######################
	function __construct(){}

	//##########################
	//表示用の関数
	//##########################
	function html_view( $tweets, $new_post_date , $t_zone , $flag ) {
		$html = array( );
		$imgArray = array( ); //サムネイル画像に設定する予定の配列
		$count = 0;$t_id_check = ""; 
		foreach ( $tweets as $key => $val ) {
			$str=""; //html
			//1.ユーザ名
			$user_name = $val->user->name;
			//2.ユーザアカウント
			$user_account= '@'.$val->user->screen_name;
			//3.ユーザURL
			$user_url= 'https://twitter.com/'.$val->user->screen_name;
			//4.画像
			$img="";$imgURL="";
			if( $val->entities->media ) {
				foreach( $val->entities->media as $imgObj ) {
					$img .= $imgObj->media_url ? "<img class='img' src=".$imgObj->media_url."/>" : "";
					$imgURL = $imgObj->media_url ;
				}
			}

			//5.日付
			$date = date( 'Y年m月d日 H:i', 
							strtotime( $t_zone.'hour', strtotime( $val->created_at ) ) );

			//6.ツイート
			$tweet = $val->text;

			$tweet = mb_ereg_replace('(https?://[-_.!~*\'()a-zA-Z0-9;/?:@&=+$,%#]+)', '<a href="\1" target="_blank">\1</a>', $tweet);
			//絵文字→*
			//reject overly long 2 byte sequences, as well as characters above U+10000 and replace with ?
			$tweet = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]'.
			 '|[\x00-\x7F][\x80-\xBF]+'.
			 '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*'.
			 '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'.
			 '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
			 '*', $tweet ); 
			//reject overly long 3 byte sequences and UTF-16 surrogates and replace with ?
			$tweet = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]'.
			 '|\xED[\xA0-\xBF][\x80-\xBF]/S','*', $tweet );


			//7.RT
			$rt = $val->retweet_count;
			$t_id = $val->id_str;

			//8.リンク
			$link = $user_url."/statuses/".$t_id;
			
			//9.プロフィール画像
			$p_img = "<img src=".$val->user->profile_image_url."/>";


			//表示
			//RTが真の場合もしくはRTが0の場合表示
			if(  $flag['imgMode'] == 0 ) {
				$str =<<< EOS
				<li>
					<ul class="twt">
						<li><a rel="nofollow" href="{$link}" target="_blank">{$img}</a></li>
						<li class="profile"><a rel="nofollow" href="{$user_url}">{$p_img}{$user_name}<b>{$user_account}</b></a></li>
						<li class="tweet">{$tweet}<a href="{$link}" rel="nofollow" class="date">{$date} 参照元:twitter.com</a></li>
					</ul></li>
EOS;
			}elseif( $flag['imgMode'] == 1 ) {
				$str =<<< EOS
					<li><a rel="nofollow" href="{$link}" target="_blank">{$img}<strong class="cl">参照元：twitter.com</strong></a></li>
EOS;
			}

		//1日の範囲か精査
		$f_date = strtotime('-1day',$new_post_date);
		$l_date = $new_post_date;

			if( isset($str) 
				&& $flag[ 'count' ] > $count
				&& $t_id != $t_id_check
				&& strtotime( -1*$t_zone."hour", $f_date )<= strtotime( $val->created_at ) 
				&& strtotime( -1*$t_zone."hour", $l_date  ) > strtotime( $val->created_at ) ) {
				if( $flag[ 'sort' ] == "asc" ) {
					array_unshift( $html, $str );
					array_unshift( $imgArray, $imgURL );
				}elseif( $flag[ 'sort' ] == "desc" ){
					array_push( $html, $str );
					array_push( $imgArray, $imgURL );
				}
				$t_id_check = $t_id ;
				$count += 1;
			}
		}
		$html = "<ul class='paka3Tweet'>".implode( '', $html )."</ul>";
		return array('html' => $html,
								 'imgUrl' => $imgArray );
	}

	function post_css() {
			echo <<< EOS
				<style  type="text/css">
					
					ul.paka3Tweet,
					ul.twt,
					ul.twt li{
						margin:0 !important;
						padding:0 !important;
					}
					ul.paka3Tweet li{
						list-style-type : none;
						border:1px solid;
						padding:5pt !important;
						border-color:#EEEEEE #DDDDDD #BBBBBB;
						border-radius:5px;
						box-shadow:rgba(0, 0, 0, 0.14902) 0 1px 3px;
					}
						ul.paka3Tweet ul.twt li{
							border:0;box-shadow:none!important;
						}
					ul.paka3Tweet li img.img{
						border-radius:5px;
						margin-bottom:5pt;
					}
					ul.paka3Tweet ul.twt li.profile{
						margin-bottom:15pt !important;
					}
						ul.paka3Tweet ul.twt li.profile img{
							display:block;
							border-radius:5px;
							margin-right:10pt;
							float:left;
						}
					ul.paka3Tweet ul.twt li.profile b,
					ul.paka3Tweet a.date{
						color:#999;display:block;
						font-size:10pt;
					}
						ul.paka3Tweet a.date{
							margin:10pt 0;
						}
					ul.paka3Tweet ul.twt li.tweet{
						clear:left;
						line-height:180%;
					}

					 ul.paka3Tweet strong.cl{
					 	font-size:6pt;color:#999;
					 	font-weight:200;display:block;
					 }
					 ul.paka3Tweet a.imglink{
					 		display:block;
					 }
				</style>
EOS;
		}

}
