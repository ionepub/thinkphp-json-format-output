<?php
/**
* 接口辅助类文件
* @author lan
*/

namespace Common\Api;

/**
* 接口辅助类，包括基本的接口函数和错误
* 类中方法均为静态方法
* @api 调用示例
*
*      use Common\Api\ApiHelper;
*      ApiHelper::output(101, '密码错误');
*/
class ApiHelper
{
	/**
	 * 错误码列表
	 * 小于0表示逻辑错误（服务端问题）
	 * 大于0表示业务错误（如参数错误）
	 * 100x 表示用户相关错误
	 * ...
	 */
	private static $errorCodeList = array(
		-1		=>	'未知错误',
		0		=>	'SUCCESS',
		1000	=>	'尚未登录，请先登录',//需要登录的接口未登录或session失效，这个code应该特殊对待，有可能需要客户端重连或跳转到登录页
		1001	=>	'请填写账号和密码', //登录页未填写账号或密码
		1002	=>	'账号错误',         //账号长度错误或字符错误
		1003	=>	'账号不存在',       //账号不存在
		1004	=>	'密码错误',         //密码长度或字符错误，有非法字符
		1005	=>	'密码错误',         //账号与密码不匹配
		1006	=>	'手机号不存在',	    //手机号登录时检测
		# 其他...
	);

	/**
	 * 默认分页大小
	 */
	public static $pagesize = 8;

/**
 * public static function output 输出json格式数据
 * @access public
 * @param $errorCode int | array 当输入为int类型值时，表示错误码；
 *                               当输入为array类型时，表示为正常输出数据
 *                               $errorCode < 0 表示逻辑错误（服务端问题），$errorCode > 0 表示业务错误（客户端问题，如参数错误）
 * @param $errorMessage string   当errorCode为数值时，errorMessage表示错误描述；
 *                               当errorCode不为数值时，errorMessage表示分页信息
 * @param $format       string   输出格式，默认为json，否则直接返回数据数组
	 * @return 
	 *
	 *      Array
				(
				    [code] => 0						#错误码
				    [message] => SUCCESS			#错误信息
				    [data] => Array 				#接口数据
				        (
				        )
				    [pagination] => Array 			#分页信息
				        (
				            [total_record] => 10	#总数据记录数
				            [page] => 2				#当前页数
				            [pagesize] => 8			#分页大小
				            [count] => 2			#当前页数据总数
				            [page_count] => 1       #总页数
				            [more] => 0				#是否还有数据，1为还有数据，0表示全部数据已加载完
				        )
				)
	 * @api 调用示例
	 *
	 *       ApiHelper::output(101, '密码错误');    //输出错误信息
	 *       ApiHelper::output('密码错误');         //输出错误信息
	 *       ApiHelper::output(101);                //输出错误信息
	 *       ApiHelper::output(array());            //输出数据（空数组）
	 *       ApiHelper::output(array('abc'));       //输出数据
	 *       ApiHelper::output(array('abc'), array('total_record'=>20,...));  //输出数据（有分页信息）
	 */
	public static function output($errorCode = 0, $errorMessage = '', $format='json'){
		$return = array();
		if(is_numeric($errorCode)){
			//错误
			$errorMessage = trim($errorMessage) ? $errorMessage : (isset(self::$errorCodeList[$errorCode]) ? self::$errorCodeList[$errorCode] : $errorCode); //没有传入错误描述时输出错误码
			$errorCode = intval($errorCode);
			$return['code'] = $errorCode;
			if($errorCode === 0){
				$errorCode = -1;
			}
			$return['message'] = $errorMessage;
			$return['data'] = array();
		}elseif(is_array($errorCode)){
			//正确数据
			$return['code'] = 0;
			$return['message'] = "SUCCESS";
			$return['data'] = $errorCode;
			//分页信息
			if(is_array($errorMessage)){
				$return['pagination'] = $errorMessage;
			}
		}elseif(is_string($errorCode)){
			//没有输入错误码，只输入错误信息
			$return['code'] = 0;
			$return['message'] = $errorCode;
			$return['data'] = array();
		}else{
			$errorCode = intval($errorCode);
			return self::output($errorCode, $errorMessage, $format);
		}
		//输出数据
		if($format == 'json'){
			// 以json格式输出
			if(!headers_sent()){
				$userAgent = $_SERVER['HTTP_USER_AGENT'];
				if(strstr($userAgent, 'MSIE 9')){
					// IE下不输出content-type
				}else{
					@header("Content-type:application/json");
				}
			}
			if($return['code'] == 10000){
				@header('HTTP/1.1 401 Unauthorized');
			}
			echo json_encode($return);
			exit;
		}else{
			// 以数组形式返回
			return $return;
		}
		
	}

/**
 * public static function pagination 构造分页信息
 * 方便构造输出数据时的分页信息
 * @param $total_record 总数据记录数
 * @param $page 当前页数
 * @param $pagesize 分页大小
 * @param $count 当前页数据总数
	 * @return Array
		        (
		            [total_record] => 10	#总数据记录数\n
		            [page] => 2				#当前页数
		            [pagesize] => 8			#分页大小
		            [count] => 2			#当前页数据总数
		            [page_count] => 1       #总页数
		            [more] => 0				#是否还有数据，1为还有数据，0表示全部数据已加载完
		        )
	 * @api 调用示例
	 *
	 *       ApiHelper::pagination(0);           //无数据时
	 *       ApiHelper::pagination(0, 0, 0, 0);  //无数据时
	 *       ApiHelper::pagination(0, 1, 10, 0); //无数据时
	 *       ApiHelper::pagination(10, 1, 8, 8); //有数据时
	 */
	public static function pagination($total_record=0, $page=0, $pagesize=0, $count=0){
		$pagination = array();
		$total_record = intval($total_record) > 0 ? intval($total_record) : 0;
		$page = intval($page) > 0 ? intval($page) : 1;
		$pagesize = intval($pagesize) > 0 ? intval($pagesize) : 0;
		$count = intval($count) > 0 ? intval($count) : 0;
		if($total_record == 0){
			$pagination = array(
				'total_record'	=>	0,
				'page'			=>	1,
				'pagesize'		=>	$pagesize > 0 ? $pagesize : self::$pagesize,
				'count'			=>	0,
				'page_count'	=>	0,
				'more'			=>	0,
			);
		}else{
			$pagesize = $pagesize > 0 ? $pagesize : self::$pagesize;
			$more = $total_record - (($page - 1) * $pagesize) > $count ? 1 : 0;
			$pagination = array(
				'total_record'	=>	$total_record,
				'page'			=>	$page,
				'pagesize'		=>	$pagesize,
				'count'			=>	$count,
				'page_count'	=>	ceil($total_record / $pagesize), //向上取整 
				'more'			=>	$more,
			);
		}
		return $pagination;
	}
}


?>
