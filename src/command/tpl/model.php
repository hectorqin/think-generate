namespace app\<?=$mModule?>\model<?=$mLayer?>;

use <?=$mBase?>;

<?=$modelDoc?>

class <?=$modelName?> extends <?=$mBaseName?>

{
    /**
     * 数据表名称
     * @var string
     */
    const TABLE = '<?=$dbNamePrefix?><?=$tableName?>';

    /**
     * 数据表主键 复合主键使用数组定义
     * @var string|array
     */
    const PRIMARY_KEY = '<?=$pk?>';

    <?php if ($createTime){ ?>
    /**
     * 数据表插入时间字段
     * @var string
     */
    const CREATED_AT = '<?=$createTime?>';
    <?php }?>

    <?php if ($updateTime){ ?>
    /**
     * 数据表更新时间字段
     * @var string
     */
    const UPDATED_AT = '<?=$updateTime?>';
    <?php }?>

    <?php if ($deleteTime){ ?>
    /**
     * 数据表删除时间字段
     * @var string
     */
    const DELETED_AT = '<?=$deleteTime?>';
    <?php }?>

    /**
     * 数据表名称
     * @var string
     */
    protected $table = '<?=$dbNamePrefix?><?=$tableName?>';

    /**
     * 数据表主键 复合主键使用数组定义
     * @var string|array
     */
    protected $pk = '<?=$pk?>';

    <?php if ($autoTime){ ?>

    /**
     * 是否需要自动写入时间戳 如果设置为字符串 则表示时间字段的类型
     * @var bool|string
     */
    protected $autoWriteTimestamp = true;

    /**
     * 创建时间字段 false表示关闭
     * @var false|string
     */
    protected $createTime = '<?=$createTime?>';

    /**
     * 更新时间字段 false表示关闭
     * @var false|string
     */
    protected $updateTime = '<?=$updateTime?>';
    <?php }?>

    <?php if ($deleteTime){ ?>

    /**
     * 软删除字段 false表示关闭
     * @var false|string
     */
    protected $deleteTime = '<?=$deleteTime?>';
    <?php }?>

    /**
     * 搜索字段
     * @var array
     */
    public static $searchFields = [<?=$searchFieldStr?>];

    /**
     * 全局查询范围
     * @var array
     */
    protected $globalScope = [];

    <?php foreach ($varcharField as $field) { ?>

    /**
     * <?=$field['COLUMN_COMMENT']?> 查询器
     *
     * @param \think\db\Query $query 查询对象
     * @param mixed $value 对应字段值
     * @param mixed $data 数据
     * @return void
     */
    public function search<?=$field['COLUMN_NAME_UPPER']?>Attr($query, $value, $data)
    {
        if(is_null($value)){
            return;
        }
        $this->searchVarCharAttr($query, '<?=$field['COLUMN_NAME']?>', $value);
    }

    <?php }?>

    <?php foreach ($enumField as $field) { ?>

    /**
     * <?=$field['COLUMN_COMMENT']?> 查询器
     *
     * @param \think\db\Query $query 查询对象
     * @param mixed $value 对应字段值
     * @param mixed $data 数据
     * @return void
     */
    public function search<?=$field['COLUMN_NAME_UPPER']?>Attr($query, $value, $data)
    {
        if(is_null($value)){
            return;
        }
        $this->searchEnumAttr($query, '<?=$field['COLUMN_NAME']?>', $value);
    }

    <?php }?>

    <?php foreach ($numberField as $field) { ?>

    /**
     * <?=$field['COLUMN_COMMENT']?> 查询器
     *
     * @param \think\db\Query $query 查询对象
     * @param mixed $value 对应字段值
     * @param mixed $data 数据
     * @return void
     */
    public function search<?=$field['COLUMN_NAME_UPPER']?>Attr($query, $value, $data)
    {
        if(is_null($value)){
            return;
        }
        $this->searchNumberZoneAttr($query, '<?=$field['COLUMN_NAME']?>', $value);
    }

    <?php }?>



    <?php if(!$baseModelCorrect){ ?>

    /**
     * 创建时间查询器
     *
     * @param \think\db\Query $query 查询对象
     * @param mixed $value 对应字段值
     * @param mixed $data 数据
     * @return void
     */
    public function searchCreateTimeAttr($query, $value, $data)
    {
        if (is_null($value)) {
            return;
        }
        if (!defined('static::CREATED_AT')) {
            return;
        }
        $this->searchTimestampAttr($query, static::CREATED_AT, $value);
    }

    /**
     * 更新时间查询器
     *
     * @param \think\db\Query $query 查询对象
     * @param mixed $value 对应字段值
     * @param mixed $data 数据
     * @return void
     */
    public function searchUpdateTimeAttr($query, $value, $data)
    {
        if (is_null($value)) {
            return;
        }
        if (!defined('static::UPDATED_AT')) {
            return;
        }
        $this->searchTimestampAttr($query, static::UPDATED_AT, $value);
    }

    /**
     * 查找字符串类型的字段
     * 字符串  ==> 精确匹配  数组且[0]是like，模糊搜索  否则 用 IN 搜索
     *
     * @param \think\db\Query $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public function searchVarCharAttr($query, $field, $value)
    {
        if (is_string($value)) {
            $query->where(static::TABLE . '.' . $field, '=', $value);
        } else if (is_array($value)) {
            if ($value[0] === 'like' && isset($value[1])) {
                $query->where(static::TABLE . '.' . $field, 'like', $value[1]);
            } else {
                $query->whereIn(static::TABLE . '.' . $field, $value);
            }
        }
    }


    /**
     * 查找时间戳类型的字段
     * 非数组      ===> = 时间戳
     * 1元素数组   ==> >= 时间戳
     * 2元素数组   ==> between 时间戳区间
     *
     * @param \think\db\Query $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public function searchTimestampAttr($query, $field, $value)
    {
        if (!is_array($value)) {
            if ($value === 0 || $value === '0') {
                $query->where(static::TABLE . '.' . $field, '=', 0);
            } else {
                if (is_string($value)) {
                    $value = strtotime($value) ?: $value;
                }
                $query->where(static::TABLE . '.' . $field, '=', $value);
            }
        } else if (count($value) == 1) {
            if (is_string($value[0])) {
                $value[0] = strtotime($value[0]) ?: $value[0];
            }
            $query->where(static::TABLE . '.' . $field, '>=', $value[0]);
        } else if (count($value) > 1) {
            if (is_string($value[0])) {
                $value[0] = strtotime($value[0]) ?: $value[0];
            }
            if (is_string($value[1])) {
                $value[1] = strtotime($value[1]) ?: $value[1];
            }
            $query->whereBetween(static::TABLE . '.' . $field, $value);
        }
    }

    /**
     * 查找数字区间类型的字段
     * 非数组      ===> = 数字
     * 1元素数组   ==> >= 数字
     * 2元素数组   ==> between 数字区间
     *
     * @param \think\db\Query $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public function searchNumberZoneAttr($query, $field, $value)
    {
        if (!is_array($value)) {
            $query->where(static::TABLE . '.' . $field, '=', $value);
        } else if (count($value) == 1) {
            $query->where(static::TABLE . '.' . $field, '>=', $value[0]);
        } else if (count($value) > 1) {
            $query->whereBetween(static::TABLE . '.' . $field, $value);
        }
    }

    /**
     * 查找枚举类型的字段
     * 非数组  ==> = 数字
     * 数组   ==> in 数组
     *
     * @param \think\db\Query $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public function searchEnumAttr($query, $field, $value)
    {
        if (!is_array($value)) {
            $query->where(static::TABLE . '.' . $field, '=', (int) $value);
        } else {
            $query->whereIn(static::TABLE . '.' . $field, $value);
        }
    }

    /**
     * 查找set字段
     * 非数组  ==> FIND_IN_SET(value, field)
     * 数组 第一个元素为逻辑字段 [0] == and  FIND_IN_SET(value[1], field) AND FIND_IN_SET(value[2], field) ..
     *
     * @param \think\db\Query $query
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public function searchFindInSetAttr($query, $field, $value)
    {
        $field = "`" . implode('`.`', explode('.', $field)) . "`";
        if (!is_array($value)) {
            $query->whereRaw("FIND_IN_SET('${value}', ${field})");
        } else {
            $logic = strtolower(array_shift($value)) == 'and' ? 'AND' : 'OR';
            $query->where(function ($query) use ($field, $value, $logic) {
                foreach ($value as $key => $v) {
                    $query->whereRaw("FIND_IN_SET('${v}', ${field})", [], $logic);
                }
            });
        }
    }

    <?php }?>

}
