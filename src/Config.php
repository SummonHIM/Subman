<?php
namespace Subman;

class Config
{
    private $data;

    public function __construct()
    {
        include('config.php');
        $this->data = $cfg;
    }

    /**
     * 获取配置数组
     * @param string $arrayName 数组键名
     * @return 配置数组
     */
    public function getArray(string $arrayName): array
    {
        return $this->data[$arrayName] ?? null;
    }

    /**
     * 获取配置值
     * @param string $arrayName 数组键名
     * @param string $valueName 数组值名
     */
    public function getValue(string $arrayName, string $valueName): string
    {
        return $this->data[$arrayName][$valueName] ?? null;
    }
}
