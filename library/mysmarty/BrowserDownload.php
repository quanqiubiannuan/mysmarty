<?php

namespace library\mysmarty;
/**
 * 浏览器下载
 * @package library\mysmarty
 */
class BrowserDownload extends Container
{
    // 文件数据
    private string $data;
    // 文件类型
    private string $mimeType;
    // 响应过期时间
    private int $expire = 360;
    // 下载文件名
    private string $downloadFileName;
    private static ?self $obj = null;

    /**
     * 设置文件数据
     * @param string $data
     * @return static
     */
    public function setData(string $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 设置文件所在位置
     * @param string $file
     * @return static
     */
    public function setFile(string $file): static
    {
        if (!file_exists($file)) {
            error('文件不存在');
        }
        $this->data = file_get_contents($file);
        $this->mimeType = mime_content_type($file);
        $this->downloadFileName = pathinfo($file, PATHINFO_BASENAME);
        return $this;
    }

    /**
     * 设置文件类型
     * @param string $mimeType
     * @return static
     */
    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    /**
     * 设置响应过期时间
     * @param int $expire 单位：秒
     * @return static
     */
    public function setExpire(int $expire): static
    {
        $this->expire = $expire;
        return $this;
    }

    /**
     * 输出文件
     * @param string $downloadFileName 文件下载名
     */
    public function output(string $downloadFileName = ''): void
    {
        if (empty($downloadFileName)) {
            if (!empty($this->downloadFileName)) {
                $downloadFileName = $this->downloadFileName;
            } else {
                $downloadFileName = md5(time() . mt_rand(1000, 9999));
            }
        }
        if (empty($this->data)) {
            error('下载文件为空');
        }
        header_remove();
        header('Pragma: public');
        header('Content-Type: ' . ($this->mimeType ?? 'application/octet-stream'));
        header('Cache-control: max-age=' . $this->expire);
        header('Content-Disposition: attachment; filename="' . $downloadFileName . '"');
        header('Content-Length: ' . strlen($this->data));
        header('Content-Transfer-Encoding: binary');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $this->expire) . ' GMT');
        echo $this->data;
        $this->_flush();
        exit();
    }
}