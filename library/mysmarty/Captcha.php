<?php

namespace library\mysmarty;

use GdImage;

/**
 * 验证码类
 */
class Captcha extends Container
{

    /**
     * 图像高度
     * @var int
     */
    private int $height = 50;

    /**
     * 图像上显示的字符
     * @var string
     */
    private string $code = '';

    /**
     * 验证码类型
     * @var int 0 数字与字母，1 数字，2 字母，3 中文
     */
    private int $codeStyle = 0;

    /**
     * 验证码长度
     * @var int
     */
    private int $codeSize = 4;

    /**
     * 字体大小
     * @var int
     */
    private int $font = 25;

    /**
     * 字体文件
     * @var string
     */
    private string $fontFile = 'times.ttf';

    /**
     * 验证码session名称
     * @var string
     */
    private static string $sessionName = 'code';

    /**
     * 设置字体文件
     * @param string $fontFile 字体文件名称
     * @return static
     */
    public function setFontFile(string $fontFile): static
    {
        $this->fontFile = $fontFile;
        return $this;
    }

    /**
     * 设置字体大小
     * @param int $font
     * @return static
     */
    public function setFont(int $font): static
    {
        $this->font = $font;
        return $this;
    }

    /**
     * 设置验证码session名称
     * @param string $sesssionName
     * @return static
     */
    public function setSessionName(string $sesssionName): static
    {
        self::$sessionName = $sesssionName;
        return $this;
    }

    /**
     * 设置验证码高度
     * @param int $height
     * @return static
     */
    public function setHeight(int $height): static
    {
        $this->height = $height;
        return $this;
    }

    /**
     * 设置验证码字符
     * @param string $code
     * @return static
     */
    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    /**
     * 设置验证码类型
     * @param int $codeStyle 0 数字与字母，1 数字，2 字母，3 中文
     * @return  static
     */
    public function setCodeStyle(int $codeStyle): static
    {
        $this->codeStyle = $codeStyle;
        return $this;
    }

    /**
     * 设置验证码长度
     * @param int $codeSize
     * @return static
     */
    public function setCodeSize(int $codeSize): static
    {
        $this->codeSize = $codeSize;
        return $this;
    }

    /**
     * 静态调用方法
     * @param int $height 图像高度
     * @return static
     */
    public static function code(int $height = 50): static
    {
        return self::getInstance()->setHeight($height);
    }

    /**
     * 获取随机字符串
     * @return string
     */
    private function getCode(): string
    {
        $str = '0123456789qwertyuioplkjhgfdsazxcvbnm' . strtoupper('qwertyuioplkjhgfdsazxcvbnm');
        return $this->getCodeByStr($str);
    }

    /**
     * 获取随机一个字符串
     * @return string
     */
    private function getOneCode(): string
    {
        $str = '0123456789qwertyuioplkjhgfdsazxcvbnm' . strtoupper('qwertyuioplkjhgfdsazxcvbnm');
        return substr(str_shuffle($str), 0, 1);
    }

    /**
     * 获取指定的字符串数据
     * @param string $str
     * @return string
     */
    private function getCodeByStr(string $str): string
    {
        return substr(str_shuffle($str), 0, $this->codeSize);
    }

    /**
     * 获取数字验证码
     * @return string
     */
    private function getNumCode(): string
    {
        $str = '0123456789';
        return $this->getCodeByStr($str);
    }

    /**
     * 获取字母验证码
     * @return string
     */
    private function getLetterCode(): string
    {
        $str = 'qwertyuioplkjhgfdsazxcvbnm' . strtoupper('qwertyuioplkjhgfdsazxcvbnm');
        return $this->getCodeByStr($str);
    }

    /**
     * 获取中文验证码
     * @return string
     */
    private function getZhCode(): string
    {
        return getZhChar($this->codeSize);
    }

    /**
     * 设置字体
     * @param int $font
     * @return static
     */
    public function font(int $font): static
    {
        return $this->setFont($font);
    }

    /**
     * 输出验证码
     */
    public function output(): void
    {
        header('Content-Type: image/png');
        $im = $this->generateImage();
        imagepng($im);
        imagedestroy($im);
        exit();
    }

    /**
     * 生成验证码
     * @return false|GdImage
     */
    private function generateImage(): GdImage|bool
    {
        $kWidth = 20;
        if (empty($this->code)) {
            switch ($this->codeStyle) {
                case 0:
                    $this->code = $this->getCode();
                    break;
                case 1:
                    $this->code = $this->getNumCode();
                    break;
                case 2:
                    $this->code = $this->getLetterCode();
                    break;
                case 3:
                    $this->code = $this->getZhCode();
                    $kWidth = 30;
                    break;
                default:
                    $this->code = $this->getCode();
            }
        }
        Session::getInstance()->set(self::$sessionName, strtolower($this->code));
        // 判断验证码
        if (hasZh($this->code)) {
            $this->fontFile = 'zkklt.ttf';
        }
        // 创建画布
        $codeArr = preg_split('//u', $this->code);
        $codeLen = count($codeArr);
        $width = $codeLen * $kWidth;
        $im = @imagecreatetruecolor($width, $this->height);
        // 背景颜色
        $backgroundColor = imagecolorallocatealpha($im, 243, 251, 254, 0);
        imagefilledrectangle($im, 0, 0, $width - 1, $this->height - 1, $backgroundColor);
        // 计算坐标
        $imgInfo = imagettfbbox($this->font, 0, ROOT_DIR . '/extend/fonts/' . $this->fontFile, $this->code);
        //开始y位置
        $y = (int)(($this->height - $imgInfo[3] - $imgInfo[5]) / 2);
        // 写字符串
        for ($i = 0; $i < $codeLen; $i++) {
            $angle = mt_rand(-20, 20);
            $text_color = imagecolorallocate($im, mt_rand(0, 100), mt_rand(0, 100), mt_rand(0, 100));
            // 画验证码
            $x = (int)($i * $kWidth);
            imagefttext($im, $this->font, $angle, $x, $y, $text_color, ROOT_DIR . '/extend/fonts/' . $this->fontFile, $codeArr[$i]);
            $text_color = imagecolorallocate($im, mt_rand(150, 255), mt_rand(150, 255), mt_rand(150, 255));
            imagestring($im, 5, $x, $y + mt_rand(-1 * $y, 5), $this->getOneCode(), $text_color);
            // 画干扰线
            imageline($im, mt_rand(0, $width), mt_rand(0, $this->height), mt_rand(0, $width), mt_rand(0, $this->height), $text_color);
        }
        return $im;
    }

    /**
     * 生成验证码的base64编码
     * @return string
     */
    public function getBase64Image(): string
    {
        $im = $this->generateImage();
        $fileName = RUNTIME_DIR . '/captcha/captcha.png';
        createDirByFile($fileName);
        imagepng($im, $fileName);
        imagedestroy($im);
        $imgContent = file_get_contents($fileName);
        $imgEncode = base64_encode($imgContent);
        $imgInfo = getimagesize($fileName);
        return "data:{$imgInfo['mime']};base64," . $imgEncode;
    }

    /**
     * 验证验证码
     * @param string $code 输入的字符
     * @param string $sessionName 验证码session名称
     * @param bool $delete 是否删除验证码
     * @return bool
     */
    public static function check(string $code, string $sessionName = 'code', bool $delete = false): bool
    {
        if (getSession($sessionName) === strtolower($code)) {
            deleteSession($sessionName);
            return true;
        }
        if ($delete) {
            deleteSession($sessionName);
        }
        return false;
    }
}