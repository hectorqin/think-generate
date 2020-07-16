# thinkphp-generator

命令行自动生成数据表模型、校验器、控制器等

## 框架要求

ThinkPHP5.1+ 及 ThinkPHP6

## 安装

~~~ bash
composer require hectorqin/think-generate
~~~

## 配置

修改项目根目录下config/generator.php中对应的参数

### 自动生成控制器需要的类

#### BusinessException 业务异常类

~~~php
<?php

namespace app\exception;

use Throwable;
use app\constants\ErrorCode;

class BusinessException extends \RuntimeException
{
    /**
     * 额外信息
     *
     * @var array|object
     */
    protected $data;

    public function __construct(int $code = 0, string $message = null, Throwable $previous = null)
    {
        if (!is_null($previous) && $previous instanceof static ) {
            $code    = $previous->getCode();
            $message = $previous->getMessage() ?: null;
        }

        if (is_null($message)) {
            $message = ErrorCode::$allCode[$code] ?? '';
        }

        parent::__construct($message, $code, $previous);
    }
}
~~~

#### errorCode 错误常量类

~~~php
<?php
namespace app\constants;

use ReflectionMethod;
use think\facade\Log;
use app\exception\BusinessException;

class ErrorCode
{
    /**
     * 系统错误
     */
    const SYSTEM_ERROR = 1000;

    /**
     * 参数错误、参数未通过校验
     */
    const ARGS_WRONG = 1001;

    /**
     * 数据库操作出错，新增、编辑、删除失败
     */
    const DB_WRONG = 1002;

    /**
     * 未查询到相关数据
     */
    const DATA_NOT_FOUND = 1003;

    /**
     * 操作不允许
     */
    const OPERATING_NOT_PERMIT = 1004;

    /**
     * 操作失败
     */
    const OPERATION_FAILED = 1005;

    /**
     * 未授权
     */
    const OPERATING_UNAUTHORIZED = 1007;

    /**
     * 所有的code
     *
     * @var array
     */
    public static $allCode = [
        self::SYSTEM_ERROR           => '系统错误',
        self::ARGS_WRONG             => '参数错误',
        self::DB_WRONG               => '数据库操作出错',
        self::DATA_NOT_FOUND         => '未查询到相关数据',
        self::OPERATING_NOT_PERMIT   => '非法操作',
        self::OPERATION_FAILED       => '操作失败',
        self::OPERATING_UNAUTHORIZED => '未授权',
    ];

    /**
     * 获取返回的error message
     *
     * @return mixed
     */
    public static function getErrorMessage($exception = null, $default = [])
    {
        $default = is_array($default) ? $default : ($default ? [$default] : []);
        if (!is_null($exception) && method_exists($exception, 'getTraceAsString')) {
            if (!($exception instanceof BusinessException)) {
                Log::emergency($exception->getTraceAsString());
            }
        }
        if ($exception instanceof \TypeError) {
            $errorMsg = $exception->getMessage();
            // Argument 1 passed to app\index\controller\smpss\SmpssSales::tenancyBack() must be of the type string, array given in /Users/hector/htdocs/ws/bkadmin-api/application/index/controller/smpss/Smpsssales.php:182
            $matches = [];
            if (preg_match('/Argument (\d+) passed to ([^\s]+)\(\)/', $errorMsg, $matches)) {
                // $trace = $exception->getTrace();
                // $lastFunc = $trace[0];
                // $reflect    = new ReflectionMethod($lastFunc['class'], $lastFunc['function']);
                $reflect    = new ReflectionMethod(...explode('::', $matches[2]));
                $parameters = $reflect->getParameters();
                if (isset($parameters[$matches[1] - 1])) {
                    $arg   = $parameters[$matches[1] - 1];
                    $error = [
                        'errorCode' => 'ARGS_WRONG',
                        'errorArgs' => $arg->name,
                        'errorMsg'  => "参数 " . $arg->name . " 必须为 " . $arg->getType() . " 类型",
                    ];
                    return array_merge($default, $error);
                }
            }
        }
        if ($exception instanceof \InvalidArgumentException) {
            // method param miss:backNumMap
            $errorMsg = $exception->getMessage();
            $matches  = [];
            if (preg_match('/method param miss:(\w+)/', $errorMsg, $matches)) {
                $error = [
                    'errorCode' => 'ARGS_WRONG',
                    'errorArgs' => $matches[1],
                    'errorMsg'  => "参数 " . $matches[1] . " 为必填项",
                ];
                return array_merge($default, $error);
            }
        }
        if (!is_null($exception) && method_exists($exception, 'getMessage')) {
            $error = [
                'errorMsg' => $exception->getMessage(),
            ];
            return array_merge($default, $error);
        }
        return $default;
    }
}
~~~

## 使用

~~~ bash
# 帮助
$ php think generate --help
Usage:
  generate [options]

Options:
  -c, --config[=CONFIG]              配置名称，默认为 generator [default: "generator"]
  -t, --table[=TABLE]                要生成的table，多个用,隔开, 默认为所有table
  -p, --tablePrefix[=TABLEPREFIX]    table前缀，多个用,隔开
  -i, --ignoreFields[=IGNOREFIELDS]  忽略的字段，不生成搜索器
  -e, --except[=EXCEPT]              要排除的table，多个用,隔开
      --type[=TYPE]                  要生成的类型，多个用,隔开,如 m,v,c,p,s,d
                                            m -- model, v -- validate, c -- controller, p -- postmanJson, s -- searchAttr, d -- model doc
      --templateDir[=TEMPLATEDIR]    自定义模板文件夹路径，必须有 model.tpl,
                                     controller.tpl, validate.tpl等文件，使用tp模板语法
      --mModule[=MMODULE]            模型模块名
      --vModule[=VMODULE]            校验器模块名
      --cModule[=CMODULE]            控制器模块名
      --mLayer[=MLAYER]              模型分层
      --vLayer[=VLAYER]              校验器分层
      --cLayer[=CLAYER]              控制器分层
      --mBase[=MBASE]                模型继承类，如 app\common\model\EventModel
      --vBase[=VBASE]                校验器继承
      --cBase[=CBASE]                控制器继承类
      --db[=DB]                      数据库配置文件名
      --dryRun[=DRYRUN]              只执行，不保存 [default: false]
  -f, --force[=FORCE]                覆盖已存在文件 [default: false]
      --pName[=PNAME]                PostMan 项目名称，默认使用 数据库名
      --pHost[=PHOST]                PostMan API请求前缀，默认使用 api_prefix 环境变量
  -h, --help                         Display this help message
  -V, --version                      Display this console version
  -q, --quiet                        Do not output any message
      --ansi                         Force ANSI output
      --no-ansi                      Disable ANSI output
  -n, --no-interaction               Do not ask any interactive question
  -v|vv|vvv, --verbose               Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

# 生成 user 表模型、校验器、控制器
php think generate -t user --type=m,v,c

# 生成数据库全部数据表的模型、校验器、控制器
php think generate --type=m,v,c

# 不生成，预览操作
php think generate --type=m,v,c -d

~~~

## License

Apache-2.0
