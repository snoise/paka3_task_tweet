<?php
//#################################################
//#################################################
class Paka3_post_lib{
	//######################
	//コンストラクタ
	//######################
	function __construct(){
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

}//class
