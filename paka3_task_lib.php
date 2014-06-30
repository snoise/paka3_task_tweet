<?php
//#################################################
//#################################################
class Paka3_task_lib{
	//######################
	//コンストラクタ
	//######################
	function __construct(){
	}
	//######################
	//タスクをスタート
	//######################
	function task_start($hook,$t,$schedule="daily"){
		//(今日から)毎日タスクを実行する時間を設定する
		//管理＞設定されたタイムゾーンでの時間を設定する(13:00)
		$my_time = date( 'Y-m-d '.$t, current_time( 'timestamp' ) );
		//時差
		$t_zone = floor(( current_time( 'timestamp' ) - time( ) ) / 3600);
		//時差を引いて、UNIX時間(UTC:秒)に合わせる
		$task_time = strtotime( -1 * $t_zone." hour", strtotime( $my_time ) );
		wp_schedule_event( $task_time, $schedule, $hook );
	}

	//######################
	//タスクをストップ
	//######################
	function task_stop($hook){
		wp_clear_scheduled_hook( $hook );
	}

	//######################
	//タスクの有無
	//######################
	function task_check( $hook ){
		$check = false;
		if(wp_get_schedule( $hook )){
			$check = true;
		}
		return $check;
	}

}