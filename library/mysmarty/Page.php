<?php

namespace library\mysmarty;

/**
 * 分页类
 * @package library\mysmarty
 */
class Page
{
    private static ?self $obj = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * @return static
     */
    public static function getInstance(): static
    {
        if (self::$obj === null) {
            self::$obj = new self();
        }
        return self::$obj;
    }

    /**
     * 获取分页数据
     * @param int $count 数据总数
     * @param int $size 每页显示多少条
     * @param int|bool $limitTotalPage 限制总页，false则不限制
     * @param int|bool $limitPage 分页显示个数，false 不获取
     * @param string $varPage 分页变量。默认为page
     * @return array
     */
    public function paginate(int $count, int $size = 10, int|bool $limitTotalPage = false, int|bool $limitPage = 5, string $varPage = 'page'): array
    {
        $curPage = getInt($varPage);
        if ($curPage < 1) {
            $curPage = 1;
        }
        $totalPage = ceil($count / $size);
        if ($limitTotalPage && $totalPage > $limitTotalPage) {
            $totalPage = $limitTotalPage;
        }
        if ($curPage > $totalPage) {
            $curPage = $totalPage;
        }
        $result = [
            'curPage' => $curPage,
            'count' => $count,
            'totalPage' => $totalPage,
            'size' => $size
        ];
        if ($limitPage) {
            $pageData = [];
            // 获取分页数组
            if ($totalPage <= $limitPage) {
                for ($i = 1; $i <= $totalPage; $i++) {
                    $pageData[] = $i;
                }
            } else {
                $per = floor($limitPage / 2);
                $start = $curPage - $per;
                if ($start < 1) {
                    $start = 1;
                }
                $end = $start + $limitPage - 1;
                if ($end > $totalPage) {
                    $start -= $end - $totalPage;
                    $end = $totalPage;
                }
                for ($i = $start; $i <= $end; $i++) {
                    $pageData[] = $i;
                }
            }
            $result['pageData'] = $pageData;
        }
        return $result;
    }
}