<?php
//#################################################
//#################################################
class Paka3_post_lib{
	//######################
	//コンストラクタ
	//######################
	function __construct(){
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
	}

	//##########################
	//既存のカテゴリの一番あたらしい日付
	//##########################
	public function new_post_date( $catID, $post_type = "post" , $tax_cat , $tax_ids ) {
		$args = array( 
							'posts_per_page' => 1, 
							'orderby' => 'post_date', //投稿日
							'order' => 'DESC', 
							'post_type' => $post_type,
							'category' => $catID, 
							'post_status' => array( 'publish', 'pending', 'private', 'draft', 'future' )
					 );
 		if($tax_cat){
			$args['tax_query'] = array(
					'relation' => 'OR',
					'taxonomy' => $tax_cat,
					'field' => 'term_id',
					'terms' => $tax_ids[0]
				);
		}
		$posts =get_posts( $args );
		return $posts[0]->post_date;
	}

	//######################
	//新規投稿の関数
	//######################
	public function new_my_post($post, $catID = array()  ) {
		$my_post = array( );
		$my_post[ 'post_title' ] = $post['title'];
		$my_post[ 'post_content' ] =  str_replace(array("\r\n","\r","\n"), '', $post['content']);
		$my_post[ 'post_status' ] = $post[ 'draft' ]; //下書き
		$my_post[ 'post_author' ] = 1;
		$my_post[ 'post_name' ] = date( 'YmdHi_tweet' ,strtotime($post['post_date'] ) ) ;//slug
		$my_post[ 'post_date' ] = $post['post_date'];
		$my_post[ 'post_category' ] =  $catID ;
		//カスタムタクソノミー設定//デフォルトpost
		$my_post[ 'post_type' ] = $post[ 'post_type' ] ? $post[ 'post_type' ] : "post" ;

		// データベースに投稿を追加
		$post_id = wp_insert_post( $my_post );
		//サムネイルを設定した場合
		if($post['thumbnail_id']) {
			set_post_thumbnail( $post_id, $post['thumbnail_id'] );
		}
		
		$tax_ids = $post[ 'tax_ids' ] ? $post[ 'tax_ids' ] : array() ;
		$tax_cat = $post[ 'tax_cat' ] ? $post[ 'tax_cat' ] : "" ;
		if( $post_id != 0 ) {
				wp_set_post_terms($post_id,$tax_ids,$tax_cat);
			 return $post_id; //post_id
		}else{
			 return false;
		}
	}

	//######################
	//urlからメディア登録する
	//######################
	public function url_to_media($post_id,$url){
		if($url){
			$data = file_get_contents($url);
			$upPath = wp_upload_dir();
			//ファイル名を取得
			$filename = $this->getFileName($url);
			$imgPath = $upPath['path'].'/'.$filename;
			$imgURL = $upPath['url'].'/'.$filename;;
			//ファイルを保存
			file_put_contents($imgPath,$data);
			//メディア登録
			$imgID = $this->mediaCreate( $imgPath , $imgURL , $post_id);
			//サムネイル構成
			$this->paka3_reImg( $imgID , $imgPath );
			
			return $imgID ;
		}
	}
	//######################
	//URLから画像ファイルの「名前」を生成
	//######################
	private function getFileName($url){  
		$path = getImageSize($url);  
		$d=date("U");
		$res = false;
		if(preg_match('/jpeg/',$path['mime'])){
				$res = $d.'.jpg';
		} elseif(preg_match('/png/',$path['mime'])) {
				$res = $d.'.png';
		} elseif(preg_match('/gif/',$path['mime'])){
				$res = $d.'.gif';
		}
		return $res;
	} 

	//######################
	//画像を新規登録
	//######################
	private function mediaCreate($imgPath, $imgURL , $post_id){
		if($imgPath){
			//同一ディレクトリに保存
			$wp_filetype = wp_check_filetype(basename($imgPath), null );

			$attachment = array(
				'guid'  => $imgURL,
				'post_mime_type' => $wp_filetype['type'],
				'post_title' => preg_replace('/\.[^.]+$/', '', basename($imgPath)),
				'post_content' => '',
				'post_status' => 'inherit'
			);
				$imgID = wp_insert_attachment( $attachment,  $imgPath , $post_id );
			}else{
				//$this->str.="作成失敗<br />";
				$imgID = false;
			}
		return $imgID;
  }

	public function paka3_reImg ( $imgID , $imgPath ) {

			$metadata = wp_generate_attachment_metadata( $imgID , $imgPath );
			if (!empty( $metadata ) && ! is_wp_error( $metadata ) ) {
				wp_update_attachment_metadata( $imgID , $metadata );
				update_attached_file( $imgID , $imgPath );
				//$str .= '画像の再構成が実行されました<br />';
				$flag=true;
			}else{
				//$str .= "ID".$imgID."は、再構成されませんでした（エラー）。<br />";
  	    $flag=false;
			}
			return $flag;
  }


}//class
