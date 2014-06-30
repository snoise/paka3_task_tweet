<?php
class Paka3_task_tweet_admin{
	function __construct( ) {
		add_action( 'admin_menu' , array($this , 'adminAddMenu' ) );
		add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
	}
 
	//メニューの「paka3投稿」の設定（今回はサブメニュー）
	function adminAddMenu ( ) {
		add_submenu_page("edit.php", 'タスクTweet設定ページ', 'Tweet設定',  'edit_themes', 'paka3_task_tweet', array('Paka3_task_tweet_admin','paka3_post_page'));
		add_action( 'admin_notices', array( $this , 'start_text' ));
	}
	function paka3_post_page () {
		if(isset($_POST['tasktweet']) && check_admin_referer( get_bloginfo('url').'paka3TaskTweet_new','paka3TastTweet' )){
				//タスクの処理
				//更新処理処理
				$opt = $_POST['tasktweet'];
				$opt['catID'] = explode(",",$opt['catID']);
				$opt['tax_ids'] = explode(",",$opt['tax_ids']);

				update_option('paka3_task_tweet', $opt);
				 //更新メッセージ
				echo '<div class="updated fade"><p><strong>';
					_e('Options saved.');
				echo "</strong></p></div>";
		}

		$tasktweet = get_option('paka3_task_tweet') ; 

		if(isset($tasktweet[catID])) $tasktweet['catID'] = implode(",",$tasktweet['catID']) ;
		if(isset($tasktweet[tax_ids])) $tasktweet['tax_ids'] = implode(",",$tasktweet['tax_ids']) ;
		if(isset($tasktweet[tweet_count])) $sc_selected[$tasktweet[tweet_count]] = 'selected'; 
		if(isset($tasktweet[imgMode])) $im_selected[$tasktweet[imgMode]] = 'selected' ;
		if(isset($tasktweet[sort])) $s_selected[$tasktweet[sort]] = 'selected' ;
		if(isset($tasktweet[post_status])) $ps_selected[$tasktweet[post_status]] = 'selected' ;

		//タスク/
		$paka3_task = new Paka3_task_lib;
		$hook = get_option('paka3_task_tweet_hook_name'); 
				
		if( $tasktweet['now']){
			$paka3_task->task_stop($hook);
			$paka3_task->task_start($hook,$tasktweet[t],"daily");
		}else{
			$paka3_task->task_stop($hook);
		}
		//echo $paka3_task->task_check( $hook );
		if(	$paka3_task->task_check( $hook ) ){
				$task_checked = "checked";
		}else{
				$task_checked = "";
		}
		/***********/
		if(isset($tasktweet[post_thumbnail_id])){
		$thumbnail = wp_get_attachment_image( $tasktweet[post_thumbnail_id] );
		$thumbnailHtml =<<< EOS
				<div id="img_{$tasktweet[post_thumbnail_id]}">
					<a href="#" class="paka3image_remove">削除する</a>
					<br />{$thumbnail}
					<input type='hidden' name='tasktweet[post_thumbnail_id]' value='{$tasktweet[post_thumbnail_id]}' />
				</div>
EOS;
		}

		//ページに表示する内容
		 $wp_n = wp_nonce_field(get_bloginfo('url').'paka3TaskTweet_new','paka3TastTweet');
			echo <<< EOS
			<div class="wrap">
         <h2>タスクTweet設定ページ</h2>
         <form method="post" action="">
     {$wp_n}
     <hr>
      <h3>検索・スケジュール</h3>
				<table class="form-table">
						<tr valign="top">
						<th scope="row"><label for="now">スケジュールを実行</label></th>
						<td><input name="tasktweet[now]" type="checkbox" id="now" 
									value="1" class="regular-checkbox" $task_checked/>
									スケジュールを実行する。
						</td>
						</tr>
						<tr valign="top">
						<th scope="row"><label for="t">実行スケジュール(時間)</label></th>
						<td><input name="tasktweet[t]" type="text" id="t" value="$tasktweet[t]" class="regular-text" />
						</td>
						</tr>
						<tr valign="top">
						<th scope="row"><label for="word">検索ワード</label></th>
						<td><input name="tasktweet[word]" type="text" id="word" value="$tasktweet[word]" class="regular-text" />
						</td>
						</tr>
						<tr valign="top">
						<th scope="row"><label for="tweet_count">最大表示件数</label></th>
						<td>
						<select name="tasktweet[tweet_count]" id="tweet_count">
							<option value="10" $sc_selected[10]>10
							<option value="30" $sc_selected[30]>30
							<option value="50" $sc_selected[50]>50
							<option value="100" $sc_selected[100]>100
							<option value="200" $sc_selected[200]>200
						</select>
						</td>
						</tr>
				 </table>
				 	<hr>
			<h3>表示設定</h3>
			<table class="form-table">
						<tr valign="top">
						<th scope="row"><label for="imgMode">表示形式(0普通/1画像一覧表示)</label></th>
						<td>
						<select name="tasktweet[imgMode]" id="imgMode">
							<option value="0" $im_selected[0]>記事一覧表示
							<option value="1" $im_selected[1]>画像のみ表示
						</select>
						</td>
						</tr>
						<tr valign="top">
						<th scope="row"><label for="sort">並び順（表示順）asc/desc</label></th>
						<td>
						<select name="tasktweet[sort]" id="sort">
							<option value="asc" $s_selected[asc]>古い順(昇順:ASC)
							<option value="desc" $s_selected[desc]>新しい順(降順:DESC)
						</select>
						</td>
						</tr>
						<tr valign="top">
						<th scope="row"><label for="lang">記事の状態(ja/enなど)</label></th>
						<td><input name="tasktweet[lang]" type="text" id="lang" value="$tasktweet[lang]" class="regular-text" />
						</td>
						</tr>
				 </table>

				<hr>
				<h3>作成記事について</h3>
				<table class="form-table">

						<tr valign="top">
						<th scope="row"><label for="post_title">記事タイトル</label></th>
						<td><input name="tasktweet[post_title]" type="text" id="post_title" value="$tasktweet[post_title]" class="regular-text" />
						</td>
						</tr>
						<tr valign="top">
						<th scope="row"><label for="post_content">記事・文頭</label></th>
						<td>
EOS;
						wp_editor( $tasktweet[post_content], 'post_content' ,
						array( 'textarea_name' => 'tasktweet[post_content]' ,
										'drag_drop_upload' => true ,
										'textarea_rows'=>3) ) ;
			echo <<< EOS
						</td>
						</tr>
						<tr valign="top">
						<th scope="row"><label for="post_status">記事の状態</label></th>
						<td>
						<select name="tasktweet[post_status]" id="post_status">
							<option value="draft" $ps_selected[draft]>下書き
							<option value="publish" $ps_selected[publish]>公開
							<option value="private" $ps_selected[private]>非公開
						</select>

						</td>
						</tr>
						<tr valign="top">
						<th scope="row"><label for="post_thumbnail_id">アイキャッチ画像(初期値)</label></th>
						<td>
							<button id="paka3media" type="button" class="button">画像を選択</button>
							<div id="paka3images">$thumbnailHtml</div>
						</td>
						</tr>
				 </table>
				<hr>
				<h3>カテゴリの設定等</h3>
				<table class="form-table">

						<tr valign="top">
						<th scope="row"><label for="catID">カテゴリID(カンマ区切り)</label></th>
						<td><input name="tasktweet[catID]" type="text" id="catID" value="$tasktweet[catID]" class="regular-text" />
						</td>
						</tr>
						<tr valign="top">
						<th scope="row"><label for="post_type">※ページタイプ(post/page/カスタム投稿)</label></th>
						<td><input name="tasktweet[post_type]" type="text" id="post_type" value="$tasktweet[post_type]" class="regular-text" />
						</td>
						</tr>
						<tr valign="top">
						<th scope="row"><label for="tax_cat">※カスタムタクソノミー（カテゴリ）</label></th>
						<td><input name="tasktweet[tax_cat]" type="text" id="tax_cat" value="$tasktweet[tax_cat]" class="regular-text" />
						</td>
						</tr>
						<tr valign="top">
						<th scope="row"><label for="tax_ids">※カスタムタクソノミー：カテゴリID(カンマ区切り)</label></th>
						<td><input name="tasktweet[tax_ids]" type="text" id="tax_ids" value="$tasktweet[tax_ids]" class="regular-text" />
						</td>
						</tr>
				 </table>
				 <hr />



				<h3>Twitter API</h3>
				<table class="form-table">
						<tr valign="top">
						<th scope="row"><label for="apikey">API Key</label></th>
						<td><input name="tasktweet[apiKey]" type="text" id="apikey" value="$tasktweet[apiKey]" class="regular-text" /></td>
						</tr>
						<tr valign="top">
						 <th scope="row"><label for="apisecret">API Secret</label></th>
						<td><input name="tasktweet[apiSecret]" type="text" id="apisecret" value="$tasktweet[apiSecret]" class="regular-text" /></td>
						</tr>
						<tr valign="top">
						 <th scope="row"><label for="accesstoken">Access Token</label></th>
						<td><input name="tasktweet[accessToken]" type="text" id="accesstoken" value="$tasktweet[accessToken]" class="regular-text" /></td>
						</tr>
						<tr valign="top">
						 <th scope="row"><label for="accesstokensecret">Access Token Secret</label></th>
						<td><input name="tasktweet[accessTokenSecret]" type="text" value="$tasktweet[accessTokenSecret]" id="accesstokensecret" class="regular-text" /></td>
						</tr>
           </table>


           
           <p class="submit"><input type="submit" name="Submit" class="button-primary" value="変更を保存" /></p>

         </form>
       </div>
EOS;

	}
	//#################################
	//管理画面スクリプトの読み込み
	//#################################
	function admin_scripts($hook_suffix){

		if($hook_suffix=="posts_page_paka3_task_tweet"){
			wp_enqueue_media(); // メディアアップローダー用のスクリプトをロードする
			// カスタムメディアアップローダー用のJavaScript
			 wp_enqueue_script(
				'my-media-uploader',
				//**javasctiptの指定
				plugins_url("paka3-uploader.js", __FILE__),

				array('jquery'),
				filemtime(dirname(__FILE__).'/paka3-uploader.js'),
				false
			);

			echo <<< EOS
			<style type="text/css">
				#paka3images div{
				float:left;
				margin: 10px;
				height: 120px;
				overflow:hidden;
				}
				#paka3images img 
				{
				max-width: 100px;
				max-height: 100px;
				border: 1px solid #cccccc;
				}
				.paka3ImageEnd{
				clear:left
				}
			</style>
EOS;
     }
   }
 
//#############################
//停止中のコメント
//#############################
	function start_text(){
		$paka3_task = new Paka3_task_lib;
		$hook = get_option('paka3_task_tweet_hook_name');
		$str =<<< EOS
			<div class="error"><p>
			[Paka3_task_tweet]現在スケジュールが停止中です。
			</p></div>
EOS;

		if(($_POST[tasktweet] && !$_POST[tasktweet][now]) || 
				(!isset($_POST[tasktweet]) && !$paka3_task->task_check( $hook ) )
			){
			echo $str;
		}
	}
}