<?php

namespace library\mysmarty;

/**
 * 富文本编辑器类
 * @package library\mysmarty
 */
class Ckeditor
{
    private static ?self $obj = null;
    // 包含有code标签的数据
    private array $codeData = [];
    private string $codeKey = '##code##';
    // 包含有pre标签的数据
    private array $preData = [];
    private string $preKey = '##pre##';
    private string $allowTags = '<p><img><h1><h2><h3><h4><h5><h6><strong><i><a><ul><li><ol><blockquote><table><thead><tbody><tr><th><td><br><pre><code>';

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * 获取Ckeditor对象
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
     * 获取编辑器格式化后内容
     * @param string $content 编辑器内容
     * @param bool $downloadImg 是否自动下载远程图片
     * @return string
     */
    public function getContent(string $content, bool $downloadImg = true): string
    {
        $content = $this->stripTags($content);
        $content = $this->paiban($content, $downloadImg);
        return $content;
    }

    /**
     * 去掉编辑器的不可信标签
     * @param string $content
     * @return string
     */
    private function stripTags(string $content): string
    {
        $content = preg_replace('/<p>([ 　]|&nbsp;)+<\/p>/iUu', '', $content);
        $content = str_ireplace('figure', 'p', $content);
        $content = preg_replace('/<figcaption>.*<\/figcaption>/iU', '', $content);
        //去掉样式标签
        $allowTagArr = explode('>', $this->allowTags);
        foreach ($allowTagArr as $v) {
            if (empty($v)) {
                continue;
            }
            if ($v === '<a') {
                $content = preg_replace('/<a [^>]*(href=[\'"][^\'"]+[\'"])[^>]*>/iU', '<a $1 target="_blank" rel="nofollow">', $content);
            } else if ($v === '<img') {
                $content = $this->replaceImg($content);
            } else if ($v === '<code') {
                $content = $this->replaceCode($content);
            } else if ($v === '<th') {
                $content = $this->replaceThTd($content);
            } else if ($v === '<td') {
                $content = $this->replaceThTd($content);
            } else if ($v === '<pre') {
                $content = preg_replace('/<pre[^>]*>/iU', '<p><pre>', $content);
                $content = preg_replace('/<\/pre>/iU', '</pre></p>', $content);
            } else {
                $content = preg_replace('/' . preg_quote($v, '/') . ' [^>]*>/iUs', $v . '>', $content);
            }
        }
        //多个换行替换为一个换行
        $content = preg_replace('/(<br[^>]*>){2,}/i', '<br>', $content);
        $content = str_ireplace('<p></p>', '', $content);
        // 把code、pre标签的内容提取出来
        if (preg_match_all('/<code[^>]*>(.*)<\/code>/Uis', $content, $mat)) {
            foreach ($mat[1] as $k => $v) {
                $key = $this->codeKey . $k;
                $this->codeData[$key] = $v;
                $content = str_ireplace($v, $key, $content);
            }
        }
        if (preg_match_all('/<pre>(.*)<\/pre>/Uis', $content, $mat)) {
            foreach ($mat[1] as $k => $v) {
                $key = $this->preKey . $k;
                $this->preData[$key] = $v;
                $content = str_ireplace($v, $key, $content);
            }
        }
        return myTrim(strip_tags($content, $this->allowTags));
    }

    /**
     * 替换内容中的图片
     * @param string $content 内容
     * @return string
     */
    private function replaceImg(string $content): string
    {
        $reg = '/<img[^>]*>/iU';
        if (preg_match_all($reg, $content, $mat)) {
            foreach ($mat[0] as $k => $v) {
                $repImg = '<img';
                $src = $this->getTagAttr($v, 'src');
                if (empty($src)) {
                    continue;
                }
                $repImg .= ' src="' . $src . '"';
                $height = $this->getTagAttr($v, 'height');
                if (!empty($height)) {
                    $repImg .= ' height="' . $height . '"';
                }
                $width = $this->getTagAttr($v, 'width');
                if (!empty($width)) {
                    $repImg .= ' width="' . $width . '"';
                }
                $repImg .= '>';
                $content = str_ireplace($v, $repImg, $content);
            }
        }
        return $content;
    }

    /**
     * 替换表格中的合并属性
     * @param string $content 内容
     * @return string
     */
    private function replaceThTd(string $content): string
    {
        $reg = '/<(th|td)[^>]*>/iU';
        if (preg_match_all($reg, $content, $mat)) {
            foreach ($mat[0] as $k => $v) {
                $repStr = '<' . $mat[1][$k];
                $colspan = $this->getTagAttr($v, 'colspan');
                if (!empty($colspan)) {
                    $repStr .= ' colspan="' . $colspan . '"';
                }
                $rowspan = $this->getTagAttr($v, 'rowspan');
                if (!empty($rowspan)) {
                    $repStr .= ' rowspan="' . $rowspan . '"';
                }
                $repStr .= '>';
                $content = str_ireplace($v, $repStr, $content);
            }
        }
        // 将th、td内的标签内容去掉
        $content = preg_replace_callback('/<(td|th)[^>]*>(.*)<\/\1>/isU', function ($mat) {
            return str_ireplace($mat[2], strip_tags($mat[2], '<img><br>'), $mat[0]);
        }, $content);
        return $content;
    }

    /**
     * 替换内容中的代码部分
     * @param string $content 内容
     * @return string
     */
    private function replaceCode(string $content): string
    {
        $reg = '/<code[^>]*>/iU';
        if (preg_match_all($reg, $content, $mat)) {
            foreach ($mat[0] as $k => $v) {
                $repCode = '<code';
                $class = $this->getTagAttr($v, 'class');
                if (!empty($class)) {
                    $repCode .= ' class="' . $class . '"';
                }
                $repCode .= '>';
                $content = str_ireplace($v, $repCode, $content);
            }
        }
        return $content;
    }

    /**
     * 获取标签的属性值
     * @param string $tag 标签字段值
     * @param string $attr 获取的属性名
     * @return string
     */
    private function getTagAttr(string $tag, string $attr): string
    {
        $reg = '/<[a-z0-9]+[^>]*' . $attr . '=[\'"]([^\'"]+)[\'"][^>]*>/iU';
        if (preg_match($reg, $tag, $mat)) {
            return myTrim($mat[1]);
        }
        return '';
    }

    /**
     * 对内容排版
     * @param string $str
     * @param bool $downloadImg 是否下载远程图片
     * @return string
     */
    private function paiban(string $str, bool $downloadImg = true): string
    {
        if (!preg_match('/<p>/i', $str)) {
            //没有匹配到p标签
            $strArr = explode(PHP_EOL, $str);
            $str = '';
            foreach ($strArr as $v) {
                $v = myTrim($v);
                if (empty($v)) {
                    continue;
                }
                $str .= $this->repPvalue($v, $downloadImg);
            }
        } else {
            $reg = '/<p>(.*)<\/p>/iUs';
            if (preg_match_all($reg, $str, $mat)) {
                foreach ($mat[0] as $v) {
                    $str = str_ireplace($v, $this->repPvalue($v, $downloadImg), $str);
                }
            }
        }
        return $this->afterPaiban($str);
    }

    /**
     * 排版后的内容处理
     * @param string $str
     * @return string
     */
    private function afterPaiban(string $str): string
    {
        $splitStr = '_@#@_';
        $finalStr = '';
        // 替换内容
        $str = preg_replace('/(<br[^>]*>){2,}/i', '<br>', $str);
        $str = preg_replace('/<p><br><\/p>/i', '', $str);
        // p 标签外的内容处理
        $reg = '/<p>(.*)<\/p>/iUs';
        $pArr = [];
        if (preg_match_all($reg, $str, $mat)) {
            $pArr = $mat[1];
        }
        $str = preg_replace($reg, $splitStr, $str);
        $strArr = explode($splitStr, $str);
        if (!empty($strArr)) {
            $curK = 0;
            foreach ($strArr as $v) {
                $v = myTrim($v);
                if (!empty($v)) {
                    $finalStr .= $this->getPBrValue($v);
                }
                if (isset($pArr[$curK])) {
                    $finalStr .= $this->getPBrValue($pArr[$curK]);
                    $curK++;
                }
            }
        }
        // 处理pre,code标签
        // code标签不转义
        $codeData = [];
        foreach ($this->preData as $k => $v) {
            $v = preg_replace_callback('/<code[^>]*>(.*)<\/code>/iUs', function ($mat) use (&$codeData) {
                $key = md5($mat[1]);
                $codeData[$key] = $mat[0];
                return $key;
            }, $v);
            $finalStr = str_ireplace($k, $v, $finalStr);
        }
        foreach ($codeData as $k => $v) {
            $finalStr = str_ireplace($k, $v, $finalStr);
        }
        foreach ($this->codeData as $k => $v) {
            $finalStr = str_ireplace($k, $v, $finalStr);
        }
        return $finalStr;
    }

    /**
     * 获取br分标签后的内容
     * @param string $pv
     * @return string
     */
    private function getPBrValue(string $pv): string
    {
        $str = '';
        $pv = myTrim($pv);
        if (!empty($pv)) {
            $brArr = explode('<br>', $pv);
            foreach ($brArr as $v) {
                $v = myTrim($v);
                if (empty($v)) {
                    continue;
                } else {
                    $v = preg_replace('/<([^>]+)>[\s ]+<\/\1>/iUsu', '', $v);
                    $str .= '<p>' . $v . '</p>';
                }
            }
        }
        return $str;
    }

    /**
     * 获取p标签字段的格式化内容
     * @param string $pv
     * @return string
     */
    private function getPValue(string $pv): string
    {
        return myTrim($pv);
    }

    /**
     * 正则替换p标签内容
     * @param string $pv
     * @param bool $downloadImg
     * @return string
     */
    private function repPvalue(string $pv, bool $downloadImg = true): string
    {
        $pv = str_ireplace('<p>', '', $pv);
        $pv = str_ireplace('</p>', '', $pv);
        $pv = preg_replace('/&[\w]+;/iU', '', $pv);
        $pv = myTrim($pv);
        if (!empty($pv)) {
            if (preg_match('/<img/i', $pv)) {
                if ($downloadImg) {
                    $srcReg = '/src=[\'"]([^\'"]*)[\'"]/iU';
                    if (preg_match_all($srcReg, $pv, $mat)) {
                        foreach ($mat[1] as $v) {
                            if (!preg_match('/' . getDomain() . '/i', $v)) {
                                $srcDir = downloadImg($v);
                                if ($srcDir) {
                                    $pv = str_ireplace($v, $srcDir, $pv);
                                } else {
                                    $pv = str_ireplace($v, '', $pv);
                                }
                            }
                        }
                    }
                }
                return '<p>' . $pv . '</p>';
            } else {
                return '<p>' . $this->getPValue($pv) . '</p>';
            }
        }
        return '';
    }
}