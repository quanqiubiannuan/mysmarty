<?php

namespace library\mysmarty;

use Exception;
use PDO;
use PDOStatement;
use RuntimeException;

class Model
{

    // 数据库主机ip
    private string $host = '';

    // 数据库登录用户名
    private string $user = '';

    // 数据库登录密码
    private string $password = '';

    // 数据库端口
    private int $port = 3306;

    // 默认数据库连接库名
    protected string $database = '';

    // 默认连接数据库编码
    protected string $charset = '';

    // 表名
    protected string $table = '';

    // 默认连接配置
    protected string $config = '';

    // 验证规则
    protected array $rule = [];

    // 类型转换
    protected array $type = [];

    // 连接对象
    private ?PDO $dbh = null;

    // 当前类对象数组
    private static array $obj = [];

    // 查询字段
    private string $mField = '*';

    private array $mWhere = [];

    private array $mWhereArgs = [];

    private string $mSql = '';

    private string $mOrder = '';

    private string $mLimit = '';

    private string $mGroup = '';

    private string $mHaving = '';

    private array $mJoin = [];

    private array $mAllowField = [];

    private array $mData = [];

    private array $mSetAttr = [];

    private array $mGetAttr = [];

    private array $mAddAttr = [];

    private bool $mValidate = false;

    private array $mHidden = [];

    private array $mPdoAttribute = [];

    private string $mErrorCode = '';

    private array $mErrorInfo = [];

    /**
     * 初始化查询变量
     */
    private function initializeVariable()
    {
        $this->mField = '*';
        $this->mWhere = [];
        $this->mWhereArgs = [];
        $this->mOrder = '';
        $this->mLimit = '';
        $this->mGroup = '';
        $this->mHaving = '';
        $this->mJoin = [];
        $this->mAllowField = [];
        $this->mData = [];
        $this->mGetAttr = [];
        $this->mSetAttr = [];
        $this->mAddAttr = [];
        $this->mValidate = false;
        $this->mHidden = [];
        $this->mPdoAttribute = [];
    }

    /**
     * 保存where条件数据
     * @return array
     */
    public function saveWhereData(): array
    {
        return [
            'mField' => $this->mField,
            'mWhere' => $this->mWhere,
            'mWhereArgs' => $this->mWhereArgs,
            'mOrder' => $this->mOrder,
            'mLimit' => $this->mLimit,
            'mGroup' => $this->mGroup,
            'mHaving' => $this->mHaving,
            'mJoin' => $this->mJoin,
            'mAllowField' => $this->mAllowField,
            'mData' => $this->mData,
            'mGetAttr' => $this->mGetAttr,
            'mSetAttr' => $this->mSetAttr,
            'mAddAttr' => $this->mAddAttr,
            'mValidate' => $this->mValidate,
            'mHidden' => $this->mHidden,
            'mPdoAttribute' => $this->mPdoAttribute
        ];
    }

    /**
     * 恢复where条件数据
     * @param array $data
     */
    public function recoverWhereData(array $data)
    {
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $this->$k = $v;
            }
        }
    }

    /**
     * 构造方法
     * @param string $config 配置名
     */
    public function __construct(string $config = 'mysql')
    {
        $config = $this->config ?: $config;
        if (!isset(CONFIG['database'][$config])) {
            throw new RuntimeException('数据库[' . $config . ']配置不存在');
        }
        $this->host = CONFIG['database'][$config]['host'];
        $this->user = CONFIG['database'][$config]['user'];
        $this->password = CONFIG['database'][$config]['password'];
        $this->port = CONFIG['database'][$config]['port'];
        $this->charset = CONFIG['database'][$config]['charset'];
        /**
         * $this->database 指向子类的$database属性
         *  $database
         */
        $this->database = $this->database ?: CONFIG['database'][$config]['database'];
        if (empty($this->table)) {
            $class = substr(static::class, strrpos(static::class, '\\') + 1);
            $this->table = toDivideName($class);
        }
        $this->setError(0);
    }

    private function __clone()
    {
    }

    /**
     * 获取数据库连接dsn
     * @return string
     */
    private function getDsn(): string
    {
        return 'mysql:dbname=' . $this->database . ';host=' . $this->host . ';port=' . $this->port . ';charset=' . $this->charset;
    }

    /**
     * 连接数据库
     */
    private function connect()
    {
        // 通过MySQL ip与端口唯一确定一个数据库连接
        $key = $this->host . $this->port;
        $this->dbh = MyPdo::getDbh($key);
        if ($this->dbh === null) {
            $dsn = $this->getDsn();
            try {
                $this->dbh = new PDO($dsn, $this->user, $this->password, $this->mPdoAttribute);
                MyPdo::setDbh($key, $this->dbh);
                MyPdo::$database = $this->database;
            } catch (Exception $e) {
                throw new RuntimeException('pdo 连接失败');
            }
        }
        if (MyPdo::$database !== $this->database) {
            MyPdo::$database = $this->database;
            $this->dbh->exec('use ' . $this->formatDatabae($this->database) . ';');
        }
    }

    /**
     * 转义数据库名
     * @param string $database
     * @return string
     */
    private function formatDatabae(string $database): string
    {
        $database = trim(str_ireplace('`', '', $database));
        return '`' . $database . '`';
    }

    /**
     * 获取对象实例
     * @param string $config 配置数据库名
     * @return static
     */
    public static function getInstance(string $config = 'mysql'): static
    {
        $obj = self::getCurrentObj($config);
        if ($obj === null) {
            $obj = new self($config);
            self::$obj[$config] = $obj;
        }
        return $obj;
    }

    /**
     * 获取当前mysql对象
     * @param string $config 配置数据库名
     * @return static|null
     */
    private static function getCurrentObj(string $config): static|null
    {
        if (!empty(self::$obj[$config])) {
            return self::$obj[$config];
        }
        return null;
    }

    /**
     * 开启验证
     * @return static
     */
    public function validate(): static
    {
        $this->mValidate = true;
        return $this;
    }

    /**
     * 指定数据库名
     * @param string $database 数据库名
     * @return static
     */
    public function name(string $database): static
    {
        $this->database = $database;
        return $this;
    }

    /**
     * 指定表名
     * @param string $table 表名
     * @return static
     */
    public function table(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * 字段选择
     *
     * @param string $field 要查询的字段，多个逗号分隔
     * @return static
     */
    public function field(string $field): static
    {
        $this->mField = $field;
        return $this;
    }

    /**
     * where and条件
     * @param string|array $field 字段
     * @param mixed $value 值
     * @param string $op 操作符
     * @return static
     */
    public function where(array|string $field, mixed $value = null, string $op = '='): static
    {
        return $this->whereMap($field, $value, $op, true);
    }

    /**
     * where or 条件
     * @param string|array $field 字段
     * @param mixed $value 值
     * @param string $op 操作符
     * @return static
     */
    public function whereOr(string|array $field, mixed $value = null, string $op = '='): static
    {
        return $this->whereMap($field, $value, $op, false);
    }

    /**
     * where条件
     * @param string|array $field
     * @param mixed $value
     * @param string $op
     * @param boolean $and
     * @return static
     */
    public function whereMap(array|string $field, mixed $value = null, string $op = '=', bool $and = true): static
    {
        $andStr = 'and';
        if (!$and) {
            $andStr = 'or';
        }
        if (is_array($field)) {
            foreach ($field as $k => $v) {
                if (is_array($v)) {
                    switch (count($v)) {
                        case 1:
                            $this->mWhere[] = [
                                $k,
                                $v[0],
                                $op,
                                $andStr
                            ];
                            break;
                        case 2:
                            $this->mWhere[] = [
                                $k,
                                $v[0],
                                $v[1],
                                $andStr
                            ];
                            break;
                    }
                } else {
                    $this->mWhere[] = [
                        $k,
                        $v,
                        $op,
                        $andStr
                    ];
                }
            }
        } else {
            $this->mWhere[] = [
                $field,
                $value,
                $op,
                $andStr
            ];
        }
        return $this;
    }

    /**
     * 排序
     * @param string $field 排序字段
     * @param string $order 排序规则，desc 降序，asc 升序
     * @return static
     */
    public function order(string $field, string $order = ''): static
    {
        if (empty($order)) {
            $this->mOrder = 'order by ' . $this->formatOrder($field);
        } else {
            $this->mOrder = 'order by ' . $this->formatOrder($field) . ' ' . $order;
        }
        return $this;
    }

    /**
     * 格式化字段,逗号分隔
     * @param string $field
     * @return string
     */
    private function formatOrder(string $field): string
    {
        $fieldArr = explode(',', $field);
        foreach ($fieldArr as $k => $v) {
            $vArr = explode(' ', $v);
            $vArr[0] = $this->formatField($vArr[0]);
            $fieldArr[$k] = implode(' ', $vArr);
        }
        return implode(',', $fieldArr);
    }

    /**
     * 限制
     * @param int $offset 偏移量，从0开始
     * @param int $size 大小
     * @return static
     */
    public function limit(int $offset, int $size = 0): static
    {
        if ($offset < 0) {
            $offset = 0;
        }
        if (empty($size)) {
            $this->mLimit = 'limit ' . $offset;
        } else {
            $this->mLimit = 'limit ' . $offset . ',' . $size;
        }
        return $this;
    }

    /**
     * 分页查询
     * @param int $page 第几页，从1开始
     * @param int $size 分页大小
     * @return static
     */
    public function page(int $page, int $size = 10): static
    {
        $offset = ($page - 1) * $size;
        return $this->limit($offset, $size);
    }

    /**
     * 分组
     * @param string $field 字段
     * @return static
     */
    public function group(string $field): static
    {
        $this->mGroup = 'group by ' . $this->formatGroup($field);
        return $this;
    }

    /**
     * 格式化字段
     * @param string $field
     * @return string
     */
    private function formatGroup(string $field): string
    {
        $fieldArr = explode(',', $field);
        foreach ($fieldArr as $k => $v) {
            $fieldArr[$k] = $this->formatDatabae($v);
        }
        return implode(',', $fieldArr);
    }

    /**
     * 分组
     * @param string $condition 条件
     * @return static
     */
    public function having(string $condition): static
    {
        $this->mHaving = 'having ' . $condition;
        return $this;
    }

    /**
     * join查询
     * @param string $table 连接的表名
     * @param string $condition 连接条件
     * @param string $type 连接类型（left join，right join,inner join）
     * @return static
     */
    public function join(string $table, string $condition, string $type = 'left join'): static
    {
        $this->mJoin[] = [
            $this->formatTable($table),
            $condition,
            $type
        ];
        return $this;
    }

    /**
     * 左连接
     *
     * @param string $table 连接的表名
     * @param string $condition 连接条件
     * @return static
     */
    public function leftJoin(string $table, string $condition): static
    {
        return $this->join($table, $condition);
    }

    /**
     * 右连接
     * @param string $table 连接的表名
     * @param string $condition 连接条件
     * @return static
     */
    public function rightJoin(string $table, string $condition): static
    {
        return $this->join($table, $condition, 'right join');
    }

    /**
     * 内连接
     * @param string $table 连接的表名
     * @param string $condition 连接条件
     * @return static
     */
    public function innerJoin(string $table, string $condition): static
    {
        return $this->join($table, $condition, 'inner join');
    }

    /**
     * 去重查询
     * @param string $field 字段
     * @return static
     */
    public function distinct(string $field): static
    {
        $this->mField = 'distinct ' . $this->formatOrder($field);
        return $this;
    }

    /**
     * 查询
     * @return array
     */
    public function select(): array
    {
        $data = $this->query($this->dealSelectSql(), $this->mWhereArgs);
        $this->initializeVariable();
        return $data;
    }

    /**
     * 处理sql语句
     */
    private function dealSelectSql(): string
    {
        $where = $this->dealWhere();
        $join = $this->dealJoin();
        if (!preg_match('/^(distinct|count|avg|max|min|sum)/i', $this->mField)) {
            $this->formatFields($this->mField);
        }
        return 'select ' . $this->mField . ' from ' . $this->formatTable($this->table) . ' ' . $join . ' ' . $where . ' ' . $this->mGroup . ' ' . $this->mHaving . ' ' . $this->mOrder . ' ' . $this->mLimit;
    }

    /**
     * 处理join条件
     * @return string
     */
    private function dealJoin(): string
    {
        $join = '';
        if (!empty($this->mJoin)) {
            foreach ($this->mJoin as $v) {
                if (empty($join)) {
                    $join = $v[2] . ' ' . $v[0] . ' on ' . $v[1];
                } else {
                    $join .= ' ' . $v[2] . ' ' . $v[0] . ' on ' . $v[1];
                }
            }
        }
        return $join;
    }

    /**
     * 获取sql语句
     * @return string
     */
    public function getLastSql(): string
    {
        return $this->mSql;
    }

    /**
     * 处理where条件
     */
    private function dealWhere(): string
    {
        $where = '';
        if (!empty($this->mWhere)) {
            foreach ($this->mWhere as $v) {
                if (empty($where)) {
                    if ($v[1] === null) {
                        $where = 'where ' . $v[0];
                    } else {
                        $where = 'where ' . $this->formatField($v[0]) . ' ' . $v[2] . ' ?';
                        $this->mWhereArgs[] = $v[1];
                    }
                } else {
                    if ($v[1] === null) {
                        $where .= ' ' . $v[3] . ' ' . $v[0];
                    } else {
                        $where .= ' ' . $v[3] . ' ' . $this->formatField($v[0]) . ' ' . $v[2] . ' ?';
                        $this->mWhereArgs[] = $v[1];
                    }
                }
            }
        }
        return $where;
    }

    /**
     * 格式化字段
     * @param string $field
     * @return string
     */
    private function formatField(string $field): string
    {
        if ($field === '*') {
            return '*';
        }
        if (str_contains($field, '.')) {
            $fieldArr = explode('.', $field);
            foreach ($fieldArr as $k => $v) {
                if ($v === '*') {
                    continue;
                }
                $fieldArr[$k] = $this->formatDatabae($v);
            }
            return implode('.', $fieldArr);
        }
        return $this->formatDatabae($field);
    }

    /**
     * 统计数据记录总数
     * @param string $field 字段
     * @return int
     */
    public function count(string $field = '*'): int
    {
        $num = 0;
        $this->mOrder = '';
        $this->mLimit = '';
        $this->mField = 'count(' . $this->formatField($field) . ') num_';
        $result = $this->select();
        if (!empty($result)) {
            $num = $result[0]['num_'];
        }
        return (int)$num;
    }

    /**
     * 查找最大值
     * @param string $field 字段
     * @return int|float
     */
    public function max(string $field): int|float
    {
        $max = 0;
        $this->mOrder = '';
        $this->mLimit = '';
        $this->mField = 'max(' . $this->formatField($field) . ') num_';
        $result = $this->select();
        if (!empty($result)) {
            $max = $result[0]['num_'];
        }
        return $max;
    }

    /**
     * 查找最小值
     * @param string $field 字段
     * @return int|float
     */
    public function min(string $field): float|int
    {
        $min = 0;
        $this->mOrder = '';
        $this->mLimit = '';
        $this->mField = 'min(' . $this->formatField($field) . ') num_';
        $result = $this->select();
        if (!empty($result)) {
            $min = $result[0]['num_'];
        }
        return $min;
    }

    /**
     * 查找平均值
     * @param string $field 字段
     * @return int|float
     */
    public function avg(string $field): float|int
    {
        $avg = 0;
        $this->mOrder = '';
        $this->mLimit = '';
        $this->mField = 'avg(' . $this->formatField($field) . ') num_';
        $result = $this->select();
        if (!empty($result)) {
            $avg = $result[0]['num_'];
        }
        return $avg;
    }

    /**
     * 查找总和
     * @param string $field 字段
     * @return int|float
     */
    public function sum(string $field): float|int
    {
        $sum = 0;
        $this->mOrder = '';
        $this->mLimit = '';
        $this->mField = 'sum(' . $this->formatField($field) . ') num_';
        $result = $this->select();
        if (!empty($result)) {
            $sum = $result[0]['num_'];
        }
        return $sum;
    }

    /**
     * 查找一条数据
     * @return array
     */
    public function find(): array
    {
        $this->limit('1');
        $data = [];
        $result = $this->select();
        if ($result) {
            $data = $result[0];
        }
        return $data;
    }

    /**
     * 空查询
     * @param string $field 字段
     * @return static
     */
    public function null(string $field): static
    {
        $this->where($this->formatField($field) . ' is null');
        return $this;
    }

    /**
     * 非空查询
     * @param string $field 字段
     * @return static
     */
    public function notNull(string $field): static
    {
        $this->where($this->formatField($field) . ' is not null');
        return $this;
    }

    /**
     * 原生查询
     * @param string $sql 原生sql语句
     * @param array $mWhereArgs 绑定的参数
     * @return array
     */
    public function query(string $sql, array $mWhereArgs = []): array
    {
        $data = [];
        $result = $this->preExec($sql, $mWhereArgs);
        if ($result) {
            while (true) {
                $row = $result->fetch(PDO::FETCH_ASSOC);
                if (!$row) {
                    break;
                }
                // 添加器
                $this->useAddAttr($row);
                // 获取器
                $this->useGetAttr($row);
                // 隐藏字段
                $this->useHidden($row);
                $this->getType($row);
                $data[] = $row;
            }
        }
        return $data;
    }

    /**
     * 去除隐藏字段
     * @param array $data
     */
    private function useHidden(array &$data): void
    {
        if (!empty($this->mHidden)) {
            foreach ($this->mHidden as $v) {
                if (isset($data[$v])) {
                    unset($data[$v]);
                }
            }
        }
    }

    /**
     * @param string $sql 原生sql语句
     * @param array $mWhereArgs 绑定的参数
     * @return int
     */
    public function execute(string $sql, array $mWhereArgs = []): int
    {
        $isInsert = false;
        if (0 === stripos($sql, 'insert')) {
            $isInsert = true;
        }
        $result = $this->preExec($sql, $mWhereArgs);
        if (empty($result)) {
            return 0;
        }
        if ($isInsert) {
            $num = $this->dbh->lastInsertId();
            if (empty($num) && !empty($result->rowCount())) {
                $num = $result->rowCount();
            }
        } else {
            $num = $result->rowCount();
        }
        return (int)$num;
    }

    /**
     * 统一执行sql语句前的执行
     * @param string $sql
     * @param array $mWhereArgs
     * @return PDOStatement|bool
     */
    private function preExec(string $sql, array $mWhereArgs = []): bool|PDOStatement
    {
        $sql = trim($sql);
        $this->connect();
        $result = $this->dbh->prepare($sql);
        $res = $result->execute($mWhereArgs);
        $this->mSql = $sql . ' 绑定的参数：' . json_encode($mWhereArgs, JSON_UNESCAPED_UNICODE);
        $this->mErrorCode = $result->errorCode();
        $this->mErrorInfo = $result->errorInfo();
        if (!$res) {
            return false;
        }
        return $result;
    }

    /**
     * 相等查询
     * @param string $field 字段
     * @param string $value 值
     * @return static
     */
    public function eq(string $field, string $value): static
    {
        $this->where($field, $value);
        return $this;
    }

    /**
     * 不相等查询
     * @param string $field 字段
     * @param string $value 值
     * @return static
     */
    public function neq(string $field, string $value): static
    {
        $this->where($field, $value, '!=');
        return $this;
    }

    /**
     * 大于查询
     * @param string $field 字段
     * @param string $value 值
     * @return static
     */
    public function gt(string $field, string $value): static
    {
        $this->where($field, $value, '>');
        return $this;
    }

    /**
     * 大于或等于查询
     * @param string $field 字段
     * @param string $value 值
     * @return static
     */
    public function egt(string $field, string $value): static
    {
        $this->where($field, $value, '>=');
        return $this;
    }

    /**
     * 小于查询
     * @param string $field 字段
     * @param string $value 值
     * @return static
     */
    public function lt(string $field, string $value): static
    {
        $this->where($field, $value, '<');
        return $this;
    }

    /**
     * 小于或等于查询
     * @param string $field 字段
     * @param string $value 值
     * @return static
     */
    public function elt(string $field, string $value): static
    {
        $this->where($field, $value, '<=');
        return $this;
    }

    /**
     * 相似查询
     * @param string $field 字段
     * @param string $value 值
     * @return static
     */
    public function like(string $field, string $value): static
    {
        $this->where($field, $value, 'like');
        return $this;
    }

    /**
     * 不相似查询
     * @param string $field 字段
     * @param string $value 值
     * @return static
     */
    public function notLike(string $field, string $value): static
    {
        $this->where($field, $value, 'not like');
        return $this;
    }

    /**
     * 区间查询
     * @param string $field 字段
     * @param string $startValue 开始值
     * @param string $endValue 结束值
     * @return static
     */
    public function between(string $field, string $startValue, string $endValue): static
    {
        $this->where($this->formatField($field) . ' between "' . $startValue . '" and "' . $endValue . '"');
        return $this;
    }

    /**
     * 不在区间查询
     * @param string $field 字段
     * @param string $startValue 开始值
     * @param string $endValue 结束值
     * @return static
     */
    public function notBetween(string $field, string $startValue, string $endValue): static
    {
        $this->where($this->formatField($field) . ' not between "' . $startValue . '" and "' . $endValue . '"');
        return $this;
    }

    /**
     * in查询
     * @param string $field 字段
     * @param string|array $value 值，逗号分割，或数组
     * @return static
     */
    public function in(string $field, array|string $value): static
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        $this->where($this->formatField($field) . ' in (' . $value . ')');
        return $this;
    }

    /**
     * not in查询
     * @param string $field 字段
     * @param string|array $value 值，逗号分割，或数组
     * @return static
     */
    public function notIn(string $field, array|string $value): static
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        $this->where($this->formatField($field) . ' not in (' . $value . ')');
        return $this;
    }

    /**
     * find_in_set查询
     * @param string $field 字段
     * @param string $value 值
     * @return static
     */
    public function findInSet(string $field, string $value): static
    {
        $this->where('find_in_set("' . $value . '",' . $this->formatField($field) . ')');
        return $this;
    }

    /**
     * 过滤字段
     * @param string|array|bool $field 过滤字段
     * @return static
     */
    public function allowField(array|string|bool $field): static
    {
        if (is_array($field)) {
            $this->mAllowField = $field;
        } else if ($field === true) {
            // 仅允许数据库中已有的字段填充（不会去掉主键id）
            $data = $this->query('desc ' . $this->table);
            foreach ($data as $v) {
                $this->mAllowField[] = $v['Field'];
            }
        } else if (is_string($field)) {
            $this->mAllowField = explode(',', $field);
        }
        return $this;
    }

    /**
     * 添加数据
     * @param array $data
     * @param bool $isReplace 是否替换
     * @return int
     */
    public function add(array $data, bool $isReplace = false): int
    {
        // 添加器
        $this->useAddAttr($data);
        // 修改器
        $this->useSetAttr($data);
        if (!empty($this->mAllowField)) {
            foreach ($data as $k => $v) {
                if (!in_array($k, $this->mAllowField, true)) {
                    unset($data[$k]);
                }
            }
        }
        if (!$this->useValidate($data)) {
            return 0;
        }
        // 隐藏字段
        $this->useHidden($data);
        $this->setType($data);
        if (empty($data)) {
            return 0;
        }
        $keyArr = array_keys($data);
        $this->formatFields($keyArr);
        $valueArr = array_values($data);
        $pArr = array_fill(0, count($valueArr), '?');
        $sql = 'insert';
        if ($isReplace) {
            $sql = 'replace';
        }
        $sql .= ' into ' . $this->formatTable($this->table) . ' (' . implode(',', $keyArr) . ') values (' . implode(',', $pArr) . ')';
        $num = $this->execute($sql, $valueArr);
        $this->initializeVariable();
        return $num;
    }

    /**
     * 格式化表名
     * @param string $table
     * @return string
     */
    private function formatTable(string $table): string
    {
        return $this->formatField($table);
    }

    /**
     * 格式化字段数组
     * @param array|string $fields 字段数组
     */
    private function formatFields(array|string &$fields): void
    {
        if (is_array($fields)) {
            foreach ($fields as $k => $v) {
                $v = $this->formatField($v);
                $fields[$k] = $v;
            }
        } else {
            $fields = $this->formatOrder($fields);
        }
    }

    /**
     * 添加数据
     * @param array $data
     * @return int
     */
    public function insert(array $data): int
    {
        return $this->add($data);
    }

    /**
     * 添加或更新数据
     * @param array $data
     * @return int
     */
    public function replace(array $data): int
    {
        return $this->add($data, true);
    }

    /**
     * 使用获取器
     * @param array $row
     */
    private function useGetAttr(array &$row): void
    {
        if (empty($row)) {
            return;
        }
        foreach ($this->mGetAttr as $attr) {
            if (array_key_exists($attr, $row) && method_exists($this, 'get' . formatController($attr) . 'Attr')) {
                $row[$attr] = $this->{'get' . formatController($attr) . 'Attr'}($row[$attr]);
            }
        }
    }

    /**
     * 使用修改器
     * @param array $data
     */
    private function useSetAttr(array &$data): void
    {
        if (!empty($this->mSetAttr) && !empty($data)) {
            foreach ($this->mSetAttr as $attr) {
                if (array_key_exists($attr, $data) && method_exists($this, 'set' . formatController($attr) . 'Attr')) {
                    $data[$attr] = $this->{'set' . formatController($attr) . 'Attr'}($data[$attr]);
                }
            }
        }
    }

    /**
     * 使用添加器
     * @param array $data
     */
    private function useAddAttr(array &$data): void
    {
        if (!empty($this->mAddAttr) && !empty($data)) {
            foreach ($this->mAddAttr as $attr) {
                if (method_exists($this, 'add' . formatController($attr) . 'Attr')) {
                    $data[$attr] = $this->{'add' . formatController($attr) . 'Attr'}($data);
                }
            }
        }
    }

    /**
     * 使用验证规则验证
     * @param array $data
     * @return bool
     */
    private function useValidate(array &$data): bool
    {
        if (!$this->mValidate || empty($this->rule)) {
            return true;
        }
        foreach ($this->rule as $k => $v) {
            if (!isset($data[$k])) {
                return false;
            }
            if (!$this->checkFields($k, $data[$k])) {
                return false;
            }
        }
        return true;
    }

    /**
     * 验证规则
     * @param string $k 字段
     * @param mixed $v 值
     * @return bool
     */
    private function checkFields(string $k, mixed $v): bool
    {
        $rule = $this->rule[$k];
        $ruleArr = explode('|', $rule);
        foreach ($ruleArr as $r) {
            $r = trim($r);
            if (!preg_match('/:/', $r)) {
                // 单验证字符
                switch ($r) {
                    case 'require':
                        if (empty($v)) {
                            return false;
                        }
                        break;
                    case 'number':
                        if (!is_numeric($v)) {
                            return false;
                        }
                        break;
                    case 'email':
                        if (!isEmail($v)) {
                            return false;
                        }
                        break;
                    case 'phone':
                        if (!isPhone($v)) {
                            return false;
                        }
                        break;
                    case 'url':
                        if (!isUrl($v)) {
                            return false;
                        }
                        break;
                    case 'domain':
                        if (!isDomain($v)) {
                            return false;
                        }
                        break;

                    case 'json':
                        if (!is_array(json_decode($v, true))) {
                            return false;
                        }
                        break;
                    case 'date':
                        if (!strtotime($v)) {
                            return false;
                        }
                        break;
                    case 'null':
                        if ($v !== null) {
                            return false;
                        }
                        break;
                    case 'not null':
                        if ($v === null) {
                            return false;
                        }
                        break;
                    case 'int':
                        if (!is_int($v)) {
                            return false;
                        }
                        break;
                    case 'double':
                    case 'float':
                        if (!is_float($v)) {
                            return false;
                        }
                        break;
                    case 'string':
                        if (!is_string($v)) {
                            return false;
                        }
                        break;
                }
            } else {
                $rArr = explode(':', $r);
                switch (trim($rArr[0])) {
                    case 'max':
                        if ($v > $rArr[1]) {
                            return false;
                        }
                        break;

                    case 'min':
                        if ($v < $rArr[1]) {
                            return false;
                        }
                        break;

                    case 'range':
                        $rArr2 = explode(',', $rArr[1]);
                        if ($v < $rArr2[0] || $v > $rArr2[1]) {
                            return false;
                        }
                        break;
                    case 'minlength':
                        if (mb_strlen($v, 'utf-8') < $rArr[1]) {
                            return false;
                        }
                        break;
                    case 'maxlength':
                        if (mb_strlen($v, 'utf-8') > $rArr[1]) {
                            return false;
                        }
                        break;
                    case 'length':
                        if (mb_strlen($v, 'utf-8') < $rArr[1] || mb_strlen($v, 'utf-8') > $rArr[2]) {
                            return false;
                        }
                        break;
                    case 'size':
                        if (mb_strlen($v, 'utf-8') !== $rArr[1]) {
                            return false;
                        }
                        break;
                }
            }
        }

        return true;
    }

    /**
     * 更新语句
     * @param array $data
     * @param bool $isBindArgs 是否使用绑定参数
     * @return int
     */
    public function update(array $data, bool $isBindArgs = true): int
    {
        // 添加器
        $this->useAddAttr($data);
        // 修改器
        $this->useSetAttr($data);
        if (!empty($this->mAllowField)) {
            foreach ($data as $k => $v) {
                if (!in_array($k, $this->mAllowField, true)) {
                    unset($data[$k]);
                }
            }
        }
        if (!$this->useValidate($data)) {
            return 0;
        }
        // 隐藏字段
        $this->useHidden($data);
        $this->setType($data);
        if (empty($data)) {
            return 0;
        }
        $keyArr = array_keys($data);
        $valueArr = $isBindArgs ? array_values($data) : [];
        $keyStr = '';
        foreach ($keyArr as $k => $v) {
            $tmp = '?';
            if (!$isBindArgs) {
                $tmp = $data[$v];
            }
            if (empty($keyStr)) {
                $keyStr = $this->formatField($v) . ' = ' . $tmp;
            } else {
                $keyStr .= ',' . $this->formatField($v) . ' = ' . $tmp;
            }
        }
        $where = $this->dealWhere();
        $sql = 'update ' . $this->formatTable($this->table) . ' set ' . $keyStr . ' ' . $where;
        $num = $this->execute($sql, array_merge($valueArr, $this->mWhereArgs));
        $this->initializeVariable();
        return $num;
    }

    /**
     * 查找表中的主键字段
     * @return string|bool
     */
    private function getPkName(): bool|string
    {
        $data = $this->query('desc ' . $this->table);
        foreach ($data as $v) {
            if ($v['Key'] === 'PRI') {
                return $v['Field'];
            }
        }
        return false;
    }

    /**
     * 删除数据
     * @param int|bool|array $id 主键id
     * @return int
     */
    public function delete(int|bool|array $id = false): int
    {
        if ($id !== FALSE) {
            $pkName = $this->getPkName();
            if (is_array($id) || preg_match('/,/', $id)) {
                $this->in($pkName, $id);
            } else {
                $this->eq($pkName, $id);
            }
        }
        $where = $this->dealWhere();
        if (empty($where)) {
            return 0;
        }
        $sql = 'delete from ' . $this->formatTable($this->table) . ' ' . $where;
        $num = $this->execute($sql, $this->mWhereArgs);
        $this->initializeVariable();
        return $num;
    }

    /**
     * 开启事务
     */
    public function startTrans(): void
    {
        $this->connect();
        if (!$this->dbh->inTransaction()) {
            $this->dbh->beginTransaction();
        }
    }

    /**
     * 提交事务
     */
    public function commit(): void
    {
        $this->connect();
        if ($this->dbh->inTransaction()) {
            $this->dbh->commit();
        }
    }

    /**
     * 回滚事务
     */
    public function rollback(): void
    {
        $this->connect();
        if ($this->dbh->inTransaction()) {
            $this->dbh->rollBack();
        }
    }

    /**
     * 更新字段值
     * @param string $field 字段
     * @param string $value 值
     * @return int
     */
    public function setField(string $field, string $value): int
    {
        return $this->update([
            $field => $value
        ]);
    }

    /**
     * 自增
     * @param string $field 自增字段
     * @param int|float $num 增值
     * @return int
     */
    public function setInc(string $field, int|float $num = 1): int
    {
        return $this->update([
            $field => $this->formatField($field) . '+' . $num
        ], false);
    }

    /**
     * 自减
     * @param string $field 自减字段
     * @param int|float $num 减值
     * @return int
     */
    public function setDec(string $field, int|float $num = 1): int
    {
        return $this->update([
            $field => $this->formatField($field) . '-' . $num
        ], false);
    }

    /**
     * 设置数据
     * @param array $data
     * @return static
     */
    public function data(array $data): static
    {
        $this->mData = $data;
        return $this;
    }

    /**
     * 添加数据
     * @return bool|int
     */
    public function save(): bool|int
    {
        if (empty($this->mData)) {
            return false;
        }
        return $this->add($this->mData);
    }

    /**
     * 添加多条数据
     * @param array $datas 多条数据
     * @return array
     */
    public function saveAll(array $datas): array
    {
        $result = [];
        foreach ($datas as $data) {
            $result[] = $this->add($data);
        }
        return $result;
    }

    /**
     * 获取器设置
     * @param array|string $fields 需要获取的字段数组
     * @return static
     */
    public function getAttr(array|string $fields): static
    {
        if (!is_array($fields)) {
            $fields = explode(',', $fields);
        }
        $this->mGetAttr = $fields;
        return $this;
    }

    /**
     * 修改器设置
     * @param array|string $fields 需要修改的字段数组
     * @return static
     */
    public function setAttr(array|string $fields): static
    {
        if (!is_array($fields)) {
            $fields = explode(',', $fields);
        }
        $this->mSetAttr = $fields;
        return $this;
    }

    /**
     * 添加器设置
     * @param array|string $fields 需要修改的字段数组
     * @return static
     */
    public function addAttr(array|string $fields): static
    {
        if (!is_array($fields)) {
            $fields = explode(',', $fields);
        }
        $this->mAddAttr = $fields;
        return $this;
    }

    /**
     * 关联查找
     * @param string $tableName 关联表名,其它库名，请使用 . 连接
     * @param string $foreignKey 关联表外键
     * @param string $primaryKey 本表主键
     * @param string $field 关联表需要查询的字段
     * @return static
     */
    public function with(string $tableName, string $foreignKey, string $primaryKey, string $field = ''): static
    {
        if (!empty($field)) {
            if ($this->mField === '*') {
                $this->mField = $this->table . '.' . '*';
            }
            $this->mField .= ',' . $field;
        }
        return $this->leftJoin($tableName, $this->table . '.' . $primaryKey . '=' . $tableName . '.' . $foreignKey);
    }

    /**
     * 获取分页数据
     * @param int $size 每页显示多少条
     * @param int|bool $limitTotalPage 限制总页，false则不限制
     * @param int|bool $limitPage 分页显示个数，false 不获取
     * @param string $varPage
     * @return array
     */
    public function paginate(int $size = 10, bool|int $limitTotalPage = false, int|bool $limitPage = 5, string $varPage = 'page'): array
    {
        $whereData = $this->saveWhereData();
        $count = $this->count();
        $this->recoverWhereData($whereData);
        return $this->paginateByCount($count, $size, $limitTotalPage, $limitPage, $varPage);
    }

    /**
     * 根据总数获取分页数据
     * @param int $count 数据总数
     * @param int $size 每页显示多少条
     * @param int|bool $limitTotalPage 限制总页，false则不限制
     * @param int|bool $limitPage 分页显示个数，false 不获取
     * @param string $varPage
     * @return array
     */
    public function paginateByCount(int $count, int $size = 10, int|bool $limitTotalPage = false, int|bool $limitPage = 5, string $varPage = 'page'): array
    {
        $result = Page::getInstance()->paginate($count, $size, $limitTotalPage, $limitPage, $varPage);
        $result['data'] = $this->page($result['curPage'], $result['size'])->select();
        return $result;
    }

    /**
     * 切换数据库
     * @param string $database 数据库名
     * @return static
     */
    public function changeDatabase(string $database): static
    {
        if (MyPdo::$database !== $database) {
            $this->execute('use ' . $this->formatDatabae($database) . ';');
            MyPdo::$database = $database;
            $this->database = $database;
        }
        return $this;
    }

    /**
     * 清空表数据
     * @param string $table
     */
    public function truncate(string $table = ''): void
    {
        $table = $table ?: $this->table;
        if (!empty($table)) {
            $this->execute('TRUNCATE ' . $this->formatTable($table) . ';');
        }
    }

    /**
     * 隐藏字段
     * @param string|array $field
     * @return static
     */
    public function hidden(array|string $field): static
    {
        if (!is_array($field)) {
            $field = explode(',', $field);
        }
        $this->mHidden = array_merge($this->mHidden, $field);
        return $this;
    }

    /**
     * 设置类型
     * @param array $data
     */
    private function setType(array &$data): void
    {
        foreach ($data as $k => $v) {
            $type = $this->type[$k] ?? '';
            switch ($type) {
                case 'integer':
                    $data[$k] = (int)$v;
                    break;
                case 'float':
                    $data[$k] = (float)$v;
                    break;
                case 'boolean':
                    $data[$k] = (bool)$v;
                    break;
                case 'object':
                case 'array':
                    $data[$k] = json_encode($v);
                    break;
                case 'serialize':
                    $data[$k] = serialize($v);
                    break;
                case 'timestamp':
                    $data[$k] = strtotime($v);
                    break;
                case 'datetime':
                    $data[$k] = date('Y-m-d H:i:s', $v);
                    break;
            }
        }
    }

    /**
     * 获取类型
     * @param array $data
     */
    private function getType(array &$data): void
    {
        foreach ($data as $k => $v) {
            $type = $this->type[$k] ?? '';
            switch ($type) {
                case 'integer':
                    $data[$k] = (int)$v;
                    break;
                case 'float':
                    $data[$k] = (float)$v;
                    break;
                case 'boolean':
                    $data[$k] = (bool)$v;
                    break;
                case 'array':
                    $data[$k] = json_decode($v, true);
                    break;
                case 'object':
                    $data[$k] = json_decode($v, false);
                    break;
                case 'serialize':
                    $data[$k] = unserialize($v, ['allowed_classes' => true]);
                    break;
                case 'timestamp':
                    $data[$k] = date('Y-m-d H:i:s', $v);
                    break;
                case 'datetime':
                    $data[$k] = strtotime($v);
                    break;
            }
        }
    }

    /**
     * 设置数据库
     * @param string $database 数据库名
     * @return static
     */
    public function setDatabase(string $database): static
    {
        return $this->name($database);
    }

    /**
     * 设置表
     * @param string $table 表名
     * @return static
     */
    public function setTable(string $table): static
    {
        return $this->table($table);
    }

    /**
     * 设置列名大小写规则
     * @param int $case 0 不做任何修改，1 大写，2 小写
     * @return static
     */
    public function setFieldCase(int $case): static
    {
        $this->mPdoAttribute[PDO::ATTR_CASE] = $case;
        return $this;
    }

    /**
     * 设置错误方式
     * @param int $error 0 仅设置错误代码,1 引发 E_WARNING 错误,2 抛出 exceptions 异常
     * @return static
     */
    public function setError(int $error): static
    {
        $this->mPdoAttribute[PDO::ATTR_ERRMODE] = $error;
        return $this;
    }

    /**
     * 设置空字段处理
     * @param int $emptyNull 0 不转换, 1  将空字符串转换成 NULL, 2 将 NULL 转换成空字符串
     * @return static
     */
    public function setEmptyNull(int $emptyNull): static
    {
        $this->mPdoAttribute[PDO::ATTR_ORACLE_NULLS] = $emptyNull;
        return $this;
    }

    /**
     * 提取的时候将数值转换为字符串
     * @param bool $to true 转换，false 不转换
     * @return static
     */
    public function setIntFieldToStr(bool $to): static
    {
        $this->mPdoAttribute[PDO::ATTR_STRINGIFY_FETCHES] = $to;
        return $this;
    }

    /**
     * 指定超时的秒数。并非所有驱动都支持此选项，这意味着驱动和驱动之间可能会有差异
     * @param int $timeOut 单位：秒
     * @return static
     */
    public function setTimeout(int $timeOut): static
    {
        $this->mPdoAttribute[PDO::ATTR_TIMEOUT] = $timeOut;
        return $this;
    }

    /**
     * 是否自动提交每个单独的语句
     * @param bool $autoCommit true 是，false 否
     * @return static
     */
    public function setAutoCommit(bool $autoCommit): static
    {
        $this->mPdoAttribute[PDO::ATTR_AUTOCOMMIT] = $autoCommit;
        return $this;
    }

    /**
     * 启用或禁用预处理语句的模拟
     * 有些驱动不支持或有限度地支持本地预处理。使用此设置强制PDO总是模拟预处理语句（如果为 TRUE ），或试着使用本地预处理语句（如果为 FALSE）。如果驱动不能成功预处理当前查询，它将总是回到模拟预处理语句上。
     * @param bool $emulate true 模拟，false 使用本地预处理语句
     * @return static
     */
    public function setEmulateSql(bool $emulate): static
    {
        $this->mPdoAttribute[PDO::ATTR_EMULATE_PREPARES] = $emulate;
        return $this;
    }

    /**
     * （在MySQL中可用）： 使用缓冲查询
     * @param bool $bufferQuery true 使用，false 不使用
     * @return static
     */
    public function setBufferQuery(bool $bufferQuery): static
    {
        $this->mPdoAttribute[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = $bufferQuery;
        return $this;
    }

    /**
     * 设置默认的提取模式
     * @param int $mode
     * 2 返回一个索引为结果集列名的数组
     * 4 返回一个索引为结果集列名和以0开始的列号的数组
     * 6 返回 TRUE ，并分配结果集中的列值给 PDOStatement::bindColumn() 方法绑定的 PHP 变量
     * 8 返回一个请求类的新实例，映射结果集中的列名到类中对应的属性名。如果 fetch_style 包含 PDO::FETCH_CLASSTYPE（例如：PDO::FETCH_CLASS | PDO::FETCH_CLASSTYPE），则类名由第一列的值决定
     * 9 更新一个被请求类已存在的实例，映射结果集中的列到类中命名的属性
     * 1 结合使用 PDO::FETCH_BOTH 和 PDO::FETCH_OBJ，创建供用来访问的对象变量名
     * 3 返回一个索引为以0开始的结果集列号的数组
     * 5 返回一个属性名对应结果集列名的匿名对象
     * @return static
     */
    public function setFetchMode(int $mode): static
    {
        $this->mPdoAttribute[PDO::ATTR_DEFAULT_FETCH_MODE] = $mode;
        return $this;
    }

    /**
     * 获取错误码
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->mErrorCode;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getErrorInfo(): string
    {
        $info = $this->mErrorInfo;
        if (empty($info)) {
            return '';
        }
        return '错误码：' . $info[0] . '，驱动错误码：' . $info[1] . '，错误信息：' . $info[2];
    }

    /**
     * 刷新权限
     * @return int
     */
    public function flushPrivileges(): int
    {
        return $this->execute('FLUSH PRIVILEGES');
    }
}