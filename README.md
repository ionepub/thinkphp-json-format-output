# JSON格式化输出类（静态类）

API接口中经常会用到

## 输出数据结构

```
Array
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
```

### #返回成功数据

1、返回成功数据（data为空数组，一般在提交保存操作时返回）：

```
{
    "code":0,
    "message":"SUCCESS",
    "data":[

    ]
}
```

2、返回成功数据（data为对象，一般在获取单个数据时返回）：

```
{
    "code":0,
    "message":"SUCCESS",
    "data":{
            "user_id":"100",
            "user_name":"Hello"
    }
}
```

3、返回成功数据（data为数组，且有pagination分页信息返回，一般在获取列表数据时返回）：

```
{
    "code":0,
    "message":"SUCCESS",
    "data":[                      //数据数组
            {
                    "article_id":"1",
                    "title":"Hello world"
            },
            {
                    "article_id":"2",
                    "title":"article title"
            },
    ],
    "pagination":{                   //分页信息
        "total_record":2,            //总数据记录数
        "page":1,                    //当前页数
        "pagesize":8,                //分页大小
        "count":2,                   //当前页数据总数
        "page_count":1,              //总页数
        "more":0          //是否还有数据，1为还有数据，0表示全部数据已加载完
    }
}
```

### #返回失败信息

```
{
    "code":1001,                         //错误码
    "message":"请填写账号和密码",           //错误信息
    "data":[

    ]
}
```

## 使用方法


### #命名空间

文件默认放置在`Application/Common/Api/`文件夹下，命名空间为`Common\Api`，可自行调整命名空间。

### #预定义错误信息

可在`$errorCodeList`数组中预定义错误码和错误信息

```php
	/**
	 * 错误码列表
	 * 例如：小于0表示逻辑错误（服务端问题）
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
```

### #引用

```php
use Common\Api\ApiHelper;
```

### #参数

```php
/**
 * public static function output 输出json格式数据
 * @access public
 * @param $errorCode int | array 当输入为int类型值时，表示错误码；
 *                               当输入为array类型时，表示为正常输出数据
 *                               $errorCode < 0 表示逻辑错误（服务端问题），$errorCode > 0 表示业务错误（客户端问题，如参数错误）
 * @param $errorMessage string   当errorCode为数值时，errorMessage表示错误描述；
 *                               当errorCode不为数值时，errorMessage表示分页信息
 * @param $format       string   输出格式，默认为'json'，否则直接返回数据数组
 */
```

```php
/**
 * public static function pagination 构造分页信息
 * 方便构造输出数据时的分页信息
 * @param $total_record 总数据记录数
 * @param $page 当前页数
 * @param $pagesize 分页大小
 * @param $count 当前页数据总数
 */
```

### #输出失败信息

```php
#1 错误码 + 错误信息
ApiHelper::output(101, '密码错误');

#2 错误码
// 错误信息将按 $errorCodeList 中定义的信息返回
ApiHelper::output(101);

# 错误信息
// 错误码将返回0
ApiHelper::output('密码错误');
```

### #输出数据

```php
#1 输出数据（空数组）
ApiHelper::output(array());

#2 输出数据
$data = array('username'=>'test', 'email'=>'test@admin.com');
ApiHelper::output($data);

#3 输出数据（有分页信息）
// 构造数据数组
$data = array(
	array('username'=>'test', 'email'=>'test@admin.com'),
	array('username'=>'test2', 'email'=>'test2@admin.com'),
);
// 构造分页信息
$pagination = ApiHelper::pagination($total_record, $page, $pagesize, $count);
ApiHelper::output($data, $pagination);
```

### #构造分页信息

```php
#1 无数据时
ApiHelper::pagination(0);

#2 无数据时
ApiHelper::pagination(0, 0, 0, 0);

#3 无数据时
ApiHelper::pagination(0, 1, 10, 0);

#4 有数据时
// 共10条数据记录，当前为第1页，分页大小为8条数据，当前页共有8条数据
ApiHelper::pagination(10, 1, 8, 8);
```

## 一些使用建议

- 返回成功信息中，部分接口可能返回以上三种数据格式的混合格式，混合格式中每个子数据都是以上三种其中一种
- 除非部分复杂接口，所有带有分页信息的接口，默认只操作一个分页，即只带有一个pagination子数据
- 在约定的接口文档中已规定的数据对应格式不会因为内部数据有无而改变数据格式，如：

```
举例：
请求同一个用户默认地址信息接口，如果用户有设置默认地址，则返回
{
    "code":0,
    "message":"SUCCESS",
    "data":{                       //此处为对象
            "user_id":"100",
            "address_id":"33",
            "province":"广东",
            "city":"深圳"
    }
}
如果用户没有设置默认地址，则返回
{
    "code":0,
    "message":"SUCCESS",
    "data":{                      //即使无数据，此处依然为对象
            
    }
}
```



