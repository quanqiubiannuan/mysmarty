<?php

namespace library\mysmarty;

/**
 * 富文本编辑器类
 * @package library\mysmarty
 */
class Tinymce
{
    private static ?self $obj = null;
    private string $allowTags = '<video><source><span><em><p><img><h1><h2><h3><h4><h5><h6><strong><i><a><ul><li><ol><blockquote><table><thead><tbody><tr><th><td><br><pre><code>';

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
        $content = $this->dealTag(strip_tags($content, $this->allowTags), $downloadImg);
        return $this->afterPaiban($content);
    }

    /**
     * 排版后的内容处理
     * @param string $str
     * @return string
     */
    private function afterPaiban(string $str): string
    {
        // 处理空格的p标签
        $str = preg_replace_callback('~<p>(.*)</p>~iUs', function ($mat) {
            $tmp = myTrim($mat[1]);
            if (empty($tmp)) {
                return '';
            }
            return '<p>' . $tmp . '</p>';
        }, $str);
        return formatHtml($str);
    }

    /**
     * 处理标签内容
     * @param string $content 编辑器内容
     * @param bool $downloadImg 是否自动下载远程图片
     * @return string
     */
    private function dealTag(string $content, bool $downloadImg = true): string
    {
        return preg_replace_callback('~<([a-z0-9]+)[^>]*>~ui', function ($mat) use ($downloadImg) {
            switch (strtolower($mat[1])) {
                case 'p':
                    return '<p>';
                case 'em':
                    return '<em>';
                case 'video':
                    $video = '<video';
                    $controls = $this->getTagAttr($mat[0], 'controls');
                    if (!empty($controls)) {
                        $video .= ' controls="' . $controls . '"';
                    }
                    $width = $this->getTagAttr($mat[0], 'width');
                    if (!empty($width)) {
                        $video .= ' width="' . $width . '"';
                    }
                    $height = $this->getTagAttr($mat[0], 'height');
                    if (!empty($height)) {
                        $video .= ' height="' . $height . '"';
                    }
                    return $video . '>';
                case 'source':
                    $source = '<source';
                    $src = $this->getTagAttr($mat[0], 'src');
                    if (!empty($src)) {
                        $source .= ' src="' . $src . '"';
                    }
                    return $source . '>';
                case 'span':
                    $span = '<span';
                    $style = $this->getTagAttr($mat[0], 'style');
                    if (!empty($style)) {
                        $span .= ' style="' . $style . '"';
                    }
                    return $span . '>';
                case 'h1':
                    return '<h1>';
                case 'h2':
                    return '<h2>';
                case 'h3':
                    return '<h3>';
                case 'h4':
                    return '<h4>';
                case 'h5':
                    return '<h5>';
                case 'h6':
                    return '<h6>';
                case 'strong':
                    return '<strong>';
                case 'i':
                    return '<i>';
                case 'a':
                    $a = '<a';
                    $href = $this->getTagAttr($mat[0], 'href');
                    if (empty($href)) {
                        return '';
                    }
                    $a .= ' href="' . $href . '"';
                    $title = $this->getTagAttr($mat[0], 'title');
                    if (!empty($title)) {
                        $a .= ' title="' . $title . '"';
                    }
                    $target = $this->getTagAttr($mat[0], 'target');
                    if (!empty($target)) {
                        $a .= ' target="' . $target . '"';
                    } else {
                        $a .= ' target="_blank"';
                    }
                    return $a . ' rel="nofollow">';
                case 'ul':
                    return '<ul>';
                case 'li':
                    return '<li>';
                case 'ol':
                    return '<ol>';
                case 'blockquote':
                    return '<blockquote>';
                case 'table':
                    return '<table>';
                case 'thead':
                    return '<thead>';
                case 'tbody':
                    return '<tbody>';
                case 'tr':
                    return '<tr>';
                case 'th':
                    $th = '<th';
                    $colspan = $this->getTagAttr($mat[0], 'colspan');
                    if (!empty($colspan)) {
                        $th .= ' colspan="' . $colspan . '"';
                    }
                    $rowspan = $this->getTagAttr($mat[0], 'rowspan');
                    if (!empty($rowspan)) {
                        $th .= ' rowspan="' . $rowspan . '"';
                    }
                    return $th . '>';
                case 'td':
                    $td = '<td';
                    $colspan = $this->getTagAttr($mat[0], 'colspan');
                    if (!empty($colspan)) {
                        $td .= ' colspan="' . $colspan . '"';
                    }
                    $rowspan = $this->getTagAttr($mat[0], 'rowspan');
                    if (!empty($rowspan)) {
                        $td .= ' rowspan="' . $rowspan . '"';
                    }
                    return $td . '>';
                case 'pre':
                    $pre = '<pre';
                    $class = $this->getTagAttr($mat[0], 'class');
                    if (!empty($class)) {
                        $pre .= ' class="' . $class . '"';
                    }
                    return $pre . '>';
                case 'code':
                    $code = '<code';
                    $class = $this->getTagAttr($mat[0], 'class');
                    if (!empty($class)) {
                        $code .= ' class="' . $class . '"';
                    }
                    return $code . '>';
                case 'br':
                    return '<br>';
                case 'img':
                    // 获取src属性
                    $src = $this->getTagAttr($mat[0], 'src');
                    if ($downloadImg) {
                        $src = downloadImg($src);
                    }
                    if (empty($src)) {
                        return '';
                    }
                    $result = '<img src="' . $src . '"';
                    // 获取alt属性
                    $alt = $this->getTagAttr($mat[0], 'alt');
                    if (!empty($alt)) {
                        $result .= ' alt="' . htmlspecialchars($alt) . '"';
                    } else {
                        $title = (string)input('title');
                        if (!empty($title)) {
                            $result .= ' alt="' . htmlspecialchars($title) . '"';
                        }
                    }
                    // 获取width属性
                    $width = $this->getTagAttr($mat[0], 'width');
                    if (!empty($width)) {
                        $result .= ' width="' . $width . '"';
                    }
                    // 获取height属性
                    $height = $this->getTagAttr($mat[0], 'height');
                    if (!empty($height)) {
                        $result .= ' height="' . $height . '"';
                    }
                    return $result . '>';
            }
            return $mat[0];
        }, $content);
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
}